<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Services\AccountAccess;
use App\Services\PdfDocument;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function reports(Request $request, ReportController $reports, PdfDocument $pdf)
    {
        $request->validate(['format' => 'sometimes|in:pdf,csv']);
        $data = $reports->index($request)->getData(true);
        $range = ($data['start_date'] ?? 'all').'_'.$data['end_date'];
        $rows = [['Period', ($data['start_date'] ?? 'All records').' to '.$data['end_date']], ['Timezone', config('app.timezone')]];
        foreach (['urgent_goats'=>'Urgent goats','monitoring_goats'=>'Monitoring goats','normal_goats'=>'Normal goats','medical_records_count'=>'Medical records','health_logs_count'=>'Health logs','temp_avg'=>'Average temperature (C)','temp_high'=>'Highest temperature (C)','temp_low'=>'Lowest temperature (C)'] as $key=>$label) $rows[] = [$label, $data[$key] ?? 'N/A'];
        AccountAccess::record($request->user(), 'Report exported', $request);
        foreach (['motion_readings_count' => 'Motion readings', 'prolonged_inactivity_count' => 'Prolonged Inactivity', 'excessive_movement_count' => 'Excessive Movement', 'other_motion_count' => 'Other motion readings'] as $key => $label) $rows[] = [$label, $data[$key] ?? 0];
        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $file = fopen('php://output', 'w'); fwrite($file, "\xEF\xBB\xBF");
                foreach ($rows as $row) fputcsv($file, $row, ',', '"', ''); fclose($file);
            }, "agrisentry-report-$range.csv", ['Content-Type'=>'text/csv; charset=UTF-8', 'Cache-Control'=>'private, no-store']);
        }
        return $this->download(app(\App\Services\AnalyticsReportPdf::class)->render($data), "agrisentry-report-$range.pdf");
    }

    public function medical(Request $request, PdfDocument $pdf)
    {
        $data = $request->validate(['goat_id'=>'nullable|integer|exists:goats,id']);
        $records = MedicalRecord::with('goat')->when($data['goat_id'] ?? null, fn ($q,$id)=>$q->where('goat_id',$id))->orderBy('goat_id')->orderBy('date_given')->orderBy('id')->get();
        $lines = ['Generated: '.now()->format('Y-m-d H:i').' '.config('app.timezone'), 'Includes all medical record types. Total records: '.$records->count(), ''];
        foreach ($records as $record) {
            $lines[] = 'Record #'.$record->id.' | '.$record->record_type;
            $lines[] = 'Goat: '.($record->goat?->name ?? 'Unknown').' | Ear Tag / ID: '.($record->goat?->ear_tag ?: $record->goat?->code);
            $lines[] = 'Title: '.$record->title;
            $lines[] = 'Date given: '.($record->date_given ?? 'Not recorded').' | Next due: '.($record->next_due_date ?? 'Not recorded');
            $lines[] = 'Administered by: '.($record->administered_by ?? 'Not recorded');
            $lines[] = 'Details: '.($record->description ?? 'Not recorded');
            if ($record->reference_photo_path) $lines[] = 'Reference attachment: '.basename($record->reference_photo_path).' (available in the system)';
            $lines[] = str_repeat('-',80); $lines[] = '';
        }
        if ($records->isEmpty()) $lines[] = 'No medical records available.';
        AccountAccess::record($request->user(), 'Medical records exported', $request);
        return $this->download($pdf->render('Complete Medical Records', $lines), 'agrisentry-medical-records'.(isset($data['goat_id']) ? '-goat-'.$data['goat_id'] : '').'.pdf');
    }
    private function download(string $pdf, string $filename)
    {
        return response($pdf)->header('Content-Type','application/pdf')->header('Content-Disposition','attachment; filename="'.$filename.'"')->header('Cache-Control','private, no-store');
    }
}
