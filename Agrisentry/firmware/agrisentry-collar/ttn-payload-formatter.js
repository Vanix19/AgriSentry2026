// TTN Console > Applications > <app> > End devices > <device> > Payload formatters
// > Uplink > Formatter type: "Custom JavaScript formatter" > paste this in.
//
// Decodes the 6-byte AgriSentry collar uplink into the field names
// App\Http\Controllers\Api\LoraController expects in uplink_message.decoded_payload.

function decodeUplink(input) {
  var bytes = input.bytes;
  if (!bytes || (bytes.length !== 5 && bytes.length !== 6)) {
    return { data: {}, errors: ['Expected 5-byte Heltec or 6-byte AgriSentry packet.'] };
  }

  var tempRaw = (bytes[0] << 8) | bytes[1];
  if (tempRaw & 0x8000) tempRaw -= 0x10000; // sign-extend int16
  var temperature = tempRaw / 10;

  if (bytes.length === 5) {
    var flags = bytes[3];
    if ((flags & 0x44) || tempRaw === -1) temperature = null;
    var motion = (flags & 0x20) ? null : ['Normal', 'Prolonged Inactivity', 'Active', 'Excessive Movement'][bytes[2]];
    if (motion === undefined || bytes[4] > 100) return {data: {}, errors: ['Invalid motion code or battery percentage.']};
    var urgent = motion === 'Prolonged Inactivity' || motion === 'Excessive Movement';
    return {data: {
      temperature: temperature, movement: motion, battery_level: bytes[4],
      led_status: temperature === null ? null : temperature < 33 ? 'Blue' : temperature > 38.5 ? 'Red' : 'Green',
      severity: urgent ? 'Urgent' : temperature === null ? null : temperature < 32 || temperature > 39.5 ? 'Urgent' : temperature < 33 || temperature > 38.5 ? 'Warning' : 'Normal'
    }, warnings: [], errors: []};
  }

  var battery = bytes[2];

  var movementMap = ["Normal", "Active", "Low Movement", "Prolonged Inactivity", "Excessive Movement"];
  var ledMap = ["Green", "Red", "Blue"];
  var severityMap = ["Normal", "Warning", "Urgent"];

  return {
    data: {
      temperature: temperature,
      battery_level: battery,
      movement: movementMap[bytes[3]] || "Unknown",
      led_status: ledMap[bytes[4]] || "Unknown",
      severity: severityMap[bytes[5]] || "Normal",
    },
    warnings: [],
    errors: [],
  };
}
