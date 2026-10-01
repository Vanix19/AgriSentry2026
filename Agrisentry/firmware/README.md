# AgriSentry Collar â€” LoRaWAN Setup

Status: **nothing here has been verified on real hardware.** I don't have
your board, gateway, or a LoRaWAN network server account, so I couldn't flash
or test any of this. Treat `agrisentry-collar.ino` as a strong starting point
to compile and debug against your actual Heltec WiFi LoRa 32 V3, not
drop-in-and-done firmware. The pin numbers, in particular, are the commonly
published defaults for this board revision â€” confirm them against your
board's silkscreen before wiring the DS18B20/MPU6050/LEDs.

The backend side (`app/Http/Controllers/Api/LoraController.php`) already
existed and is ready to receive uplinks â€” nothing needed to change there.

## What you're wiring together

```
[ESP32 collar] --LoRa--> [Heltec indoor gateway] --Wi-Fi/Ethernet--> Internet
                                                                        |
                                                                        v
                                              The Things Network (TTN) console
                                                        |
                                              Payload formatter decodes bytes
                                                        |
                                              HTTP webhook integration
                                                        |
                                                        v
                                    POST https://your-domain/api/lorawan/uplink
```

Why TTN specifically: `LoraController` already parses a TTN v3-shaped
payload (`end_device_ids.device_id` / `.dev_eui`, `uplink_message.decoded_payload.*`)
â€” that's TTN's exact webhook format, so it's the path of least resistance.
It's also free for prototyping/thesis-scale traffic and doesn't require you
to stand up your own network server.

## 1. Gateway (Heltec Indoor Hotspot HT-M7603)

Set it up per its manual to forward to TTN â€” most Heltec/RAK indoor gateways
ship with a web UI where you pick "The Things Network" as the packet forwarder
target and enter the TTN router address for your region (Asia: `au1.cloud.thethings.network`
or the region TTN assigns you). Register the gateway's own EUI in the TTN
Console under **Gateways** first, using the frequency plan **AS923** (Philippines/
most of SE Asia â€” change in the firmware's `Region` constant if you're
actually elsewhere).

## 2. TTN Console â€” register the end device

1. Create/use an **Application** in the TTN Console.
2. **End devices > Register end device > Enter end device specifics manually**.
3. Frequency plan: AS923. LoRaWAN version: 1.0.3 or 1.0.4 (matches `NWK_KEY == APP_KEY` in the firmware).
4. Either let TTN generate a DevEUI/AppKey for you, or generate your own â€” either way, copy:
   - **DevEUI** â†’ `DEV_EUI` in `agrisentry-collar.ino`
   - **JoinEUI/AppEUI** â†’ `JOIN_EUI` (often fine as all-zeros)
   - **AppKey** â†’ `APP_KEY` (and copy the same bytes into `NWK_KEY`)
5. **Payload formatters > Uplink > Custom JavaScript formatter** â†’ paste in
   `ttn-payload-formatter.js` from this folder.
6. **Integrations > Webhooks > Add webhook > Custom webhook**:
   - Base URL: `https://<your-domain>/api/lorawan/uplink`
   - Additional header: `X-LoRaWAN-Secret: <your-LORAWAN_SHARED_SECRET>`
     (this matches `LORAWAN_SHARED_SECRET` already set in `.env` â€” rotate both together if you change it)
   - Enable the "Uplink message" event.

If your Laravel app isn't publicly reachable yet (e.g. still on XAMPP
localhost), TTN can't reach it directly â€” you'll need either a real deployment
or a tunnel (ngrok/Cloudflare Tunnel) pointed at your local server for testing.

## 3. AgriSentry â€” register the collar

In the app's **Collars** page, add a collar whose **Dev EUI** field exactly
matches the DevEUI you registered in TTN (this is how `LoraController` matches
an incoming uplink to a `Collar` row â€” see `Collar::where('dev_eui', $devEui)`).
Assign it to the goat wearing it.

## 4. Firmware â€” Arduino IDE setup

Board: **ESP32 Arduino core**, board **"Heltec WiFi LoRa 32(V3)"** if that
entry is installed (Heltec's board-support package), otherwise a generic
**"ESP32S3 Dev Module"** works too â€” just double check upload speed/PSRAM
settings match your board.

Libraries (Arduino IDE **Tools > Manage Libraries**):
- `RadioLib` (jgromes) â€” LoRa radio + LoRaWAN stack
- `U8g2` (olikraus) â€” OLED driver
- `OneWire` (Paul Stoffregen)
- `DallasTemperature` (Miles Burton)
- `Adafruit MPU6050` + `Adafruit Unified Sensor` + `Adafruit BusIO`

Open `agrisentry-collar.ino`, fill in `DEV_EUI` / `JOIN_EUI` / `APP_KEY` /
`NWK_KEY` from step 2, and flash.

### Wiring (per the pin `#define`s at the top of the sketch)

| Signal | GPIO | Notes |
|---|---|---|
| LoRa SX1262 | 8/9/10/11/12/13/14 | Built into the board â€” no wiring needed |
| OLED (built-in) | 17/18/21 | Built into the board â€” no wiring needed |
| Vext power rail | 36 | Built into the board, driven LOW in `setup()` |
| DS18B20 data | 7 | Needs a 4.7kÎ© pull-up to 3.3V if not already on your sensor breakout |
| MPU6050 SDA/SCL | 17/18 | Shares the OLED's I2C bus (different address, no conflict) |
| Status LED â€” Green/Red/Blue | 33/34/35 | Through a current-limiting resistor each |

**Pins 33/34/35 for the status LEDs are a placeholder** â€” pick whatever free
GPIOs match how you've actually wired your tri-color indicator, and update
the `#define`s accordingly.

### What the sketch does

- Reads DS18B20 temperature and classifies Normal/High/Low using the *same*
  38.5Â°C / 33.0Â°C thresholds as `App\Services\HealthAlertEvaluator`, and
  drives the 3-color LED to match.
- Reads MPU6050 acceleration magnitude and buckets it into a rough movement
  label (Normal/Active/Low Movement/Abnormal Orientation) â€” this is a crude
  heuristic, not tuned against your actual goats; expect to adjust the
  `deviation` thresholds in `readAndSend()` after watching real readings.
- Shows the current reading on the OLED.
- Joins TTN via OTAA, then sends a 6-byte uplink every 5 minutes
  (`UPLINK_INTERVAL_MS`) â€” don't lower this much further, AS923 has duty-cycle
  airtime limits and TTN's fair-use policy is ~30 uplinks/day per device.

### Known gaps / things to verify yourself

- I picked RadioLib's `LoRaWANNode` API as of its ~6.x release. If your
  installed RadioLib version has a different method signature for
  `beginOTAA`/`activateOTAA`/`sendReceive`, check **File > Examples >
  RadioLib > LoRaWAN** for the exact call shape on your installed version
  and adjust.
- Battery percent is a rough single-cell LiPo linear estimate off the
  Heltec V3's onboard divider (GPIO1/GPIO37) â€” replace with real calibration
  if you have a discharge curve for your actual battery.
- The MPU6050 motion buckets don't currently feed an "Abnormal Movement"
  Alert in the backend â€” `HealthAlertEvaluator` only evaluates temperature
  and battery today. The movement/severity values still get stored on each
  `HealthLog` row and show up in the Vitals Chart / Motion Anomaly Summary,
  they just don't trigger an SMS alert yet. Say the word if you want that
  wired up too.

