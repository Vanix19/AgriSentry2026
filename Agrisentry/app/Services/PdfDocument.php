<?php
namespace App\Services;

/** Small, dependency-free text PDF writer with automatic page breaks. */
class PdfDocument
{
    public function render(string $title, array $lines): string
    {
        $wrapped = [];
        foreach ($lines as $line) {
            $line = iconv('UTF-8', 'Windows-1252//TRANSLIT', (string) $line);
            foreach (explode("\n", wordwrap(str_replace(["\r", "\t"], ['', '    '], $line), 80, "\n", true)) as $part) $wrapped[] = $part;
        }
        $pages = array_chunk($wrapped ?: ['No records available.'], 48);
        $objects = [1 => '', 2 => '', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $kids = [];
        foreach ($pages as $index => $page) {
            $pageId = 5 + $index * 2; $streamId = $pageId + 1; $kids[] = "$pageId 0 R";
            $stream = "0.086 0.45 0.2 rg BT /F2 18 Tf 44 792 Td (AgriSentry) Tj ET\n";
            $stream .= '0.08 0.12 0.16 rg BT /F2 12 Tf 44 766 Td ('.$this->escape(iconv('UTF-8', 'Windows-1252//TRANSLIT', $title)).") Tj ET\n";
            $stream .= "BT /F1 10 Tf 13.5 TL 44 738 Td\n";
            foreach ($page as $line) $stream .= '('.$this->escape($line).") Tj T*\n";
            $stream .= "ET\nBT /F1 9 Tf 44 38 Td (Page ".($index + 1).' of '.count($pages).") Tj ET\n";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $streamId 0 R >>";
            $objects[$streamId] = '<< /Length '.strlen($stream).">>\nstream\n".$stream.'endstream';
        }
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($pages).' >>';
        ksort($objects); $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $id => $object) { $offsets[$id] = strlen($pdf); $pdf .= "$id 0 obj\n$object\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($objects as $id => $object) $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }
    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value));
    }
}
