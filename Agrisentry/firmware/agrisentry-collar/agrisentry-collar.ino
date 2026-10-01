/*
 * AgriSentry Smart Collar — Heltec WiFi LoRa 32 V3 (ESP32-S3 + SX1262)
 * ---------------------------------------------------------------------
 * Reads skin temperature (DS18B20) + motion (MPU6050), classifies the
 * goat's status the same way the AgriSentry backend does, shows it on
 * the onboard OLED, drives a 3-color status LED, and sends a compact
 * uplink over LoRaWAN (OTAA) every UPLINK_INTERVAL_MS.
 *
 * NOT FLASHED OR TESTED ON REAL HARDWARE. This was written without
 * access to the physical board — verify every pin against your actual
 * wiring before powering sensors, and be ready to debug the LoRaWAN
 * join against your specific RadioLib version. See ../README.md.
 *
 * Required libraries (Arduino IDE > Tools > Manage Libraries):
 *   - RadioLib          (jgromes)      — LoRa radio + LoRaWAN stack
 *   - U8g2               (olikraus)     — SSD1306 OLED driver
 *   - OneWire            (Paul Stoffregen)
 *   - DallasTemperature   (Miles Burton) — DS18B20 driver
 *   - Adafruit MPU6050 + Adafruit Unified Sensor + Adafruit BusIO
 *
 * Board package: esp32 by Espressif Systems, board "Heltec WiFi LoRa 32(V3)"
 * (or generic "ESP32S3 Dev Module" if the Heltec entry isn't installed).
 */

#include <RadioLib.h>
#include <U8g2lib.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>
#include <Wire.h>

// ============ FILL THESE IN FROM YOUR TTN END DEVICE ============
// TTN Console > Applications > <your app> > End devices > <this device> > Root keys.
// Byte order below is MSB-first, matching how TTN displays them.
uint64_t JOIN_EUI = 0x0000000000000000; // AppEUI/JoinEUI, often all zeros unless TTN assigned one
uint64_t DEV_EUI  = 0x0000000000000000; // <-- REQUIRED: this device's DevEUI
uint8_t APP_KEY[16] = { 0x00,0x00,0x00,0x00, 0x00,0x00,0x00,0x00, 0x00,0x00,0x00,0x00, 0x00,0x00,0x00,0x00 }; // <-- REQUIRED
uint8_t NWK_KEY[16] = { 0x00,0x00,0x00,0x00, 0x00,0x00,0x00,0x00, 0x00,0x00,0x00,0x00, 0x00,0x00,0x00,0x00 }; // LoRaWAN 1.0.x: same as APP_KEY

// Also register this exact DevEUI in AgriSentry's "Add Collar" form (Dev EUI
// field) so uplinks get matched to the right goat/collar row.

// ============ REGION ============
// Philippines / most of Southeast Asia uses AS923. Change if deploying elsewhere.
const LoRaWANBand_t Region = AS923;
const uint8_t SubBand = 0; // 0 = default; adjust only if your TTN region plan says otherwise

// ============ HELTEC WIFI LORA 32 V3 PINOUT ============
// Standard published pinout for this board revision — double-check against
// your board's silkscreen/datasheet before wiring anything external.
#define LORA_NSS   8
#define LORA_DIO1  14
#define LORA_RST   12
#define LORA_BUSY  13
#define LORA_SCK   9
#define LORA_MISO  11
#define LORA_MOSI  10

#define OLED_SDA   17
#define OLED_SCL   18
#define OLED_RST   21
#define VEXT_CTRL  36 // Heltec V3: pull LOW to power the OLED + external Vext rail

#define DS18B20_PIN 7   // free GPIO — move if it conflicts with your build
#define STATUS_LED_GREEN 33
#define STATUS_LED_RED   34
#define STATUS_LED_BLUE  35

// ============ THRESHOLDS (mirrors App\Services\HealthAlertEvaluator) ============
const float TEMP_WARNING_HIGH = 38.5;
const float TEMP_URGENT_HIGH = 39.5;
const float TEMP_WARNING_LOW  = 33.0;
const float TEMP_URGENT_LOW  = 32.0;

const unsigned long MOTION_SAMPLE_MS = 200;
const unsigned long INACTIVITY_LIMIT_MS = 90UL * 60UL * 1000UL;
const unsigned long EXCESSIVE_LIMIT_MS = 15UL * 1000UL;
const unsigned long MINOR_MOVEMENT_GRACE_MS = 10UL * 1000UL;

const unsigned long UPLINK_INTERVAL_MS = 5UL * 60UL * 1000UL; // 5 min — respect AS923 duty cycle, don't go faster

SX1262 radio = new Module(LORA_NSS, LORA_DIO1, LORA_RST, LORA_BUSY);
LoRaWANNode node(&radio, &Region, SubBand);

U8G2_SSD1306_128X64_NONAME_F_HW_I2C oled(U8G2_R0, /* reset=*/ OLED_RST, /* clock=*/ OLED_SCL, /* data=*/ OLED_SDA);

OneWire oneWire(DS18B20_PIN);
DallasTemperature ds18b20(&oneWire);

Adafruit_MPU6050 mpu;
bool mpuOk = false;

unsigned long lastUplink = 0;
unsigned long lastMotionSample = 0;
unsigned long lowMovementSince = 0;
unsigned long excessiveSince = 0;
unsigned long minorMovementSince = 0;
uint8_t movementCode = 0;
const char* movementLabel = "Normal";

void setup() {
  Serial.begin(115200);
  delay(1500);
  Serial.println(F("AgriSentry collar booting..."));

  pinMode(VEXT_CTRL, OUTPUT);
  digitalWrite(VEXT_CTRL, LOW); // power the OLED / sensor rail

  pinMode(STATUS_LED_GREEN, OUTPUT);
  pinMode(STATUS_LED_RED, OUTPUT);
  pinMode(STATUS_LED_BLUE, OUTPUT);
  setStatusLed('-');

  oled.begin();
  showMessage("AgriSentry", "Booting...", "");

  ds18b20.begin();

  Wire.begin(OLED_SDA, OLED_SCL);
  mpuOk = mpu.begin();
  if (mpuOk) {
    mpu.setAccelerometerRange(MPU6050_RANGE_8_G);
    mpu.setGyroRange(MPU6050_RANGE_500_DEG);
    mpu.setFilterBandwidth(MPU6050_BAND_21_HZ);
  } else {
    Serial.println(F("MPU6050 not found — check wiring/address."));
  }

  int state = radio.begin();
  if (state != RADIOLIB_ERR_NONE) {
    Serial.print(F("Radio init failed, code ")); Serial.println(state);
    showMessage("AgriSentry", "Radio init", "FAILED");
    while (true) { delay(1000); }
  }

  node.beginOTAA(JOIN_EUI, DEV_EUI, NWK_KEY, APP_KEY);

  Serial.println(F("Joining LoRaWAN network..."));
  showMessage("AgriSentry", "Joining", "network...");
  state = node.activateOTAA();
  if (state != RADIOLIB_LORAWAN_NEW_SESSION) {
    Serial.print(F("Join failed, code ")); Serial.println(state);
    showMessage("AgriSentry", "Join FAILED", "check keys");
    // Keep retrying forever — a caretaker won't be watching Serial output.
    while (state != RADIOLIB_LORAWAN_NEW_SESSION) {
      delay(30000);
      state = node.activateOTAA();
    }
  }
  Serial.println(F("Joined!"));
  showMessage("AgriSentry", "Joined", "network");
  delay(1500);
}

void loop() {
  updateMotionState();
  if (millis() - lastUplink >= UPLINK_INTERVAL_MS || lastUplink == 0) {
    lastUplink = millis();
    readAndSend();
  }
  delay(200);
}

void updateMotionState() {
  if (!mpuOk || millis() - lastMotionSample < MOTION_SAMPLE_MS) return;
  lastMotionSample = millis();
  sensors_event_t a, g, sensorTemp;
  mpu.getEvent(&a, &g, &sensorTemp);
  float magnitude = sqrt(a.acceleration.x * a.acceleration.x + a.acceleration.y * a.acceleration.y + a.acceleration.z * a.acceleration.z);
  float deviation = fabs(magnitude - 9.8);
  unsigned long now = millis();

  if (deviation >= 4.0) {
    if (excessiveSince == 0) excessiveSince = now;
    if (now - excessiveSince > EXCESSIVE_LIMIT_MS) { movementCode = 4; movementLabel = "Excessive Movement"; }
  } else {
    excessiveSince = 0;
    if (movementCode == 4) { movementCode = 0; movementLabel = "Normal"; }
  }

  if (deviation < 0.3) {
    if (lowMovementSince == 0) lowMovementSince = now;
    minorMovementSince = 0;
    if (now - lowMovementSince >= INACTIVITY_LIMIT_MS) { movementCode = 3; movementLabel = "Prolonged Inactivity"; }
    else if (movementCode != 4) { movementCode = 2; movementLabel = "Low Movement"; }
  } else if (deviation < 1.5) {
    // Normal small adjustments get a grace window, so one minor movement does not erase 90 minutes of history.
    if (minorMovementSince == 0) minorMovementSince = now;
    if (now - minorMovementSince >= MINOR_MOVEMENT_GRACE_MS) {
      lowMovementSince = 0;
      if (movementCode == 3) { movementCode = 0; movementLabel = "Normal"; }
    }
    if (movementCode != 4 && movementCode != 3) { movementCode = 0; movementLabel = "Normal"; }
  } else {
    lowMovementSince = 0;
    minorMovementSince = 0;
    if (movementCode != 4) { movementCode = 1; movementLabel = "Active"; }
  }
}

void readAndSend() {
  ds18b20.requestTemperatures();
  float temperature = ds18b20.getTempCByIndex(0);
  if (temperature == DEVICE_DISCONNECTED_C) {
    Serial.println(F("DS18B20 read failed, skipping this cycle."));
    return;
  }

  uint8_t ledCode; // 0=Green,1=Red,2=Blue
  uint8_t severityCode; // 0=Normal,1=Medium,2=High
  char ledChar;
  if (temperature > TEMP_URGENT_HIGH) { ledCode = 1; severityCode = 2; ledChar = 'R'; }
  else if (temperature < TEMP_URGENT_LOW) { ledCode = 2; severityCode = 2; ledChar = 'B'; }
  else if (temperature > TEMP_WARNING_HIGH) { ledCode = 1; severityCode = 1; ledChar = 'R'; }
  else if (temperature < TEMP_WARNING_LOW) { ledCode = 2; severityCode = 1; ledChar = 'B'; }
  else { ledCode = 0; severityCode = 0; ledChar = 'G'; }
  setStatusLed(ledChar);

  uint8_t batteryPercent = readBatteryPercent();

  // 6-byte payload: [tempX10 hi][tempX10 lo][battery][movementCode][ledCode][severityCode]
  int16_t tempX10 = (int16_t) round(temperature * 10.0);
  uint8_t payload[6];
  payload[0] = (tempX10 >> 8) & 0xFF;
  payload[1] = tempX10 & 0xFF;
  payload[2] = batteryPercent;
  payload[3] = movementCode;
  payload[4] = ledCode;
  payload[5] = severityCode;

  Serial.print(F("Uplink: temp=")); Serial.print(temperature);
  Serial.print(F(" movement=")); Serial.print(movementLabel);
  Serial.print(F(" battery=")); Serial.println(batteryPercent);

  int state = node.sendReceive(payload, sizeof(payload));
  if (state < RADIOLIB_ERR_NONE) {
    Serial.print(F("Uplink failed, code ")); Serial.println(state);
  }

  char tempLine[24];
  snprintf(tempLine, sizeof(tempLine), "TEMP %.1f C", temperature);
  const char* statusLabel = (ledCode == 1) ? "HIGH TEMP" : (ledCode == 2) ? "LOW TEMP" : "NORMAL";
  showMessage("AGRISENTRY", tempLine, statusLabel);
}

uint8_t readBatteryPercent() {
  // Heltec V3 battery sense needs ADC_CTRL (GPIO37) driven low to enable the
  // divider before reading GPIO1. Returns a rough 0-100 estimate off a
  // typical single-cell LiPo curve (3.3V=0%, 4.2V=100%) — replace with your
  // own calibration if you have one.
  pinMode(37, OUTPUT);
  digitalWrite(37, LOW);
  delay(10);
  int raw = analogRead(1);
  digitalWrite(37, HIGH);

  float voltage = (raw / 4095.0) * 3.3 * 2.0; // board has a 2:1 divider on this pin
  float percent = (voltage - 3.3) / (4.2 - 3.3) * 100.0;
  if (percent < 0) percent = 0;
  if (percent > 100) percent = 100;
  return (uint8_t) percent;
}

void setStatusLed(char color) {
  digitalWrite(STATUS_LED_GREEN, color == 'G' ? HIGH : LOW);
  digitalWrite(STATUS_LED_RED, color == 'R' ? HIGH : LOW);
  digitalWrite(STATUS_LED_BLUE, color == 'B' ? HIGH : LOW);
}

void showMessage(const char* line1, const char* line2, const char* line3) {
  oled.clearBuffer();
  oled.setFont(u8g2_font_7x13B_tr);
  oled.drawStr(0, 14, line1);
  oled.setFont(u8g2_font_7x13_tr);
  oled.drawStr(0, 34, line2);
  oled.drawStr(0, 52, line3);
  oled.sendBuffer();
}
