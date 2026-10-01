const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const context = {};
vm.createContext(context);
vm.runInContext(fs.readFileSync(require('node:path').join(__dirname, '../firmware/agrisentry-collar/ttn-payload-formatter.js'), 'utf8'), context);
for (const [motion, battery, expected] of [[1,48,'Prolonged Inactivity'],[3,25,'Excessive Movement'],[0,0,'Normal']]) {
    const result = context.decodeUplink({bytes: [1,94,motion,0,battery]});
    assert.equal(result.data.temperature, 35);
    assert.equal(result.data.battery_level, battery);
    assert.equal(result.data.movement, expected);
    assert.equal(result.data.severity, motion ? 'Urgent' : 'Normal');
}
const offline = context.decodeUplink({bytes: [255,255,0,0x64,72]}).data;
assert.equal(offline.temperature, null);
assert.equal(offline.movement, null);
assert.equal(offline.battery_level,72);
assert.equal(context.decodeUplink({bytes:[1,94,42,4,0,2]}).data.movement,'Excessive Movement');
assert.ok(context.decodeUplink({bytes:[1]}).errors.length);
console.log('TTN formatter: five-byte, six-byte, sensor-offline and battery checks passed.');
