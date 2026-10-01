<?php
namespace App\Services;

/** Branded A4 summary; keeps PDF text selectable and charts vector-based. */
class AnalyticsReportPdf
{
    private string $content = '';

    public function render(array $data): string
    {
        $this->content = '';
        $ink = '0.08 0.16 0.20';
        $muted = '0.36 0.43 0.48';
        $green = '0.07 0.47 0.24';
        $period = ($data['start_date'] ?? 'All records').' to '.$data['end_date'];
        $this->rect(0, 0, 595, 8, $green);
        $logo = file_get_contents(public_path('images/agrisentry-report-logo.jpg'));
        $this->content .= "q 76 0 0 76 36 724 cm /Logo Do Q\n";
        $this->text(130, 35, 23, 'AgriSentry', true, $green);
        $this->text(130, 65, 11, 'LIVESTOCK HEALTH & MONITORING', false, $muted);
        $this->text(130, 86, 19, 'Report Analytics', true);
        $this->rect(38, 123, 519, 66, '0.95 0.97 0.96');
        $this->text(52, 133, 9, 'REPORTING PERIOD', true, $green);
        $this->text(52, 150, 12, $period, true);
        $this->text(52, 171, 9, 'Generated: '.now()->format('d M Y, H:i').'  |  Timezone: '.config('app.timezone'), false, $muted);

        $this->text(38, 211, 13, '01  Herd health overview', true);
        $total = (int) $data['urgent_goats'] + (int) $data['monitoring_goats'] + (int) $data['normal_goats'];
        $cards = [
            ['Urgent', $data['urgent_goats'], '0.72 0.16 0.20', '0.99 0.94 0.94'],
            ['Monitoring', $data['monitoring_goats'], '0.60 0.37 0.08', '0.99 0.97 0.91'],
            ['Normal', $data['normal_goats'], $green, '0.92 0.97 0.94'],
        ];
        foreach ($cards as $index => [$label, $count, $color, $background]) {
            $x = 38 + $index * 177;
            $this->rect($x, 239, 165, 111, $background);
            $this->rect($x, 239, 165, 3, $color);
            $this->text($x + 13, 254, 11, $label, true, $color);
            $this->text($x + 13, 274, 29, number_format($count), true, $ink);
            $this->text($x + 13, 312, 9, $total ? round($count / $total * 100).'% of classified goats' : 'No classified goats', false, $muted);
            $this->rect($x + 13, 333, 139, 4, '0.84 0.88 0.86');
            if ($total && $count) $this->rect($x + 13, 333, 139 * $count / $total, 4, $color);
        }
        $this->text(38, 362, 9, 'Based on each goat\'s latest health record within the reporting period.', false, $muted);

        $this->text(38, 398, 13, '02  Temperature summary', true);
        $this->rect(38, 426, 519, 25, $green);
        $this->text(52, 433, 9, 'METRIC', true, '1 1 1');
        $this->text(382, 433, 9, 'RECORDED TEMPERATURE', true, '1 1 1');
        foreach (['Average temperature' => 'temp_avg', 'Highest temperature' => 'temp_high', 'Lowest temperature' => 'temp_low'] as $label => $key) {
            $index = array_search($key, ['temp_avg', 'temp_high', 'temp_low']);
            $top = 451 + $index * 30;
            $this->rect(38, $top, 519, 30, $index % 2 ? '1 1 1' : '0.96 0.97 0.98');
            $this->text(52, $top + 8, 11, $label);
            $value = isset($data[$key]) ? number_format((float) $data[$key], 1).' '.chr(176).'C' : 'Not available';
            $this->text(414, $top + 8, 11, $value, true);
        }

        $this->text(38, 562, 13, '03  Records included', true);
        $this->rect(38, 590, 253, 46, '0.95 0.97 0.96');
        $this->rect(303, 590, 254, 46, '0.95 0.97 0.96');
        $this->text(52, 605, 12, 'Health logs: '.number_format($data['health_logs_count']), true);
        $this->text(317, 605, 12, 'Medical records: '.number_format($data['medical_records_count']), true);

        $this->text(38, 652, 13, '04  Motion readings & findings', true);
        $motionRows = [
            ['Motion readings', $data['motion_readings_count'] ?? 0],
            ['Prolonged Inactivity', $data['prolonged_inactivity_count'] ?? 0],
            ['Excessive Movement', $data['excessive_movement_count'] ?? 0],
            ['Other motion readings', $data['other_motion_count'] ?? 0],
        ];
        foreach ($motionRows as $index => [$label, $count]) {
            $x = 38 + ($index % 2) * 265;
            $top = 678 + intdiv($index, 2) * 24;
            $this->text($x, $top, 10, $label.': '.number_format($count));
        }
        $this->text(38, 736, 9, 'Motion totals count events, not goats. Other readings include normal movement.', false, $muted);
        $this->text(38, 752, 9, 'Medical records use the date given, or creation date when undated.', false, $muted);
        $this->text(38, 768, 9, 'Missing temperatures are excluded; no readings are shown as Not available.', false, $muted);
        $this->rect(38, 786, 519, 1, '0.84 0.89 0.87');
        $this->text(38, 799, 9, 'AgriSentry  |  Cooperative monitoring report', false, $muted);
        $this->text(504, 799, 9, 'Page 1 / 1', false, $muted);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [5 0 R] /Count 1 >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Logo 7 0 R >> >> /Contents 6 0 R >>',
            '<< /Length '.strlen($this->content).">>\nstream\n".$this->content.'endstream',
            '<< /Type /XObject /Subtype /Image /Width 360 /Height 360 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($logo).">>\nstream\n".$logo."\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $id = $index + 1;
            $offsets[] = strlen($pdf);
            $pdf .= "$id 0 obj\n$object\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 8\n0000000000 65535 f \n";
        foreach ($offsets as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf."trailer << /Size 8 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private function text(float $x, float $top, int $size, string $text, bool $bold = false, string $color = '0.08 0.16 0.20'): void
    {
        $text = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $text);
        $font = $bold ? 'F2' : 'F1';
        $y = 842 - $top - $size;
        $this->content .= "$color rg BT /$font $size Tf $x $y Td ($text) Tj ET\n";
    }

    private function rect(float $x, float $top, float $width, float $height, string $color): void
    {
        $y = 842 - $top - $height;
        $this->content .= "$color rg $x $y $width $height re f\n";
    }
}
