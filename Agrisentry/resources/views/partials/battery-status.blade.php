<script>
function liveBatteryStatus(level) {
    const value = level == null || level === '' ? NaN : Number(String(level).replace('%', ''));
    const valid = Number.isFinite(value) && value >= 0 && value <= 100;
    const color = valid && value <= 20 ? '#dc2626' : '#16a34a';
    return `<div style="display:grid;gap:5px"><strong style="color:${color}">${valid ? Math.round(value) + '%' : 'No reading'}</strong><div style="width:90px;height:7px;background:#cbd5e1;border-radius:4px;overflow:hidden"><div style="width:${valid ? value : 0}%;height:100%;background:${color}"></div></div></div>`;
}
</script>
