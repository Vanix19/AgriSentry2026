<?php
namespace Tests\Feature;

use App\Models\Goat;
use App\Models\HealthLog;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Services\PdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DownloadsAndEarTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_motion_report_counts_follow_selected_dates(): void
    {
        $goat = Goat::create(['name' => 'Motion goat', 'code' => 'M-1']);
        foreach ([['Normal', '2026-09-01'], ['Prolonged Inactivity', '2026-09-01'], ['Excessive Movement', '2026-09-02']] as [$movement, $date]) {
            $log = HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Telemetry', 'movement' => $movement]);
            $log->forceFill(['created_at' => $date.' 12:00:00'])->save();
        }
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $query = '?start_date=2026-09-01&end_date=2026-09-01';
        $this->getJson('/api/reports'.$query)->assertOk()->assertJsonPath('motion_readings_count', 2)->assertJsonPath('prolonged_inactivity_count', 1)->assertJsonPath('excessive_movement_count', 0)->assertJsonPath('other_motion_count', 1);
        $this->assertStringContainsString('Prolonged Inactivity: 1', $this->get('/api/reports/export'.$query)->assertOk()->getContent());
        $this->assertStringContainsString('"Prolonged Inactivity",1', $this->get('/api/reports/export'.$query.'&format=csv')->assertOk()->streamedContent());
    }

    public function test_ear_tag_is_the_single_identifier_and_duplicates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $result = $this->postJson('/api/goats', ['name'=>'Bituin','ear_tag'=>'EAR-17','code'=>'ignored']);
        $result->assertCreated()->assertJsonPath('goat.code','EAR-17')->assertJsonPath('goat.ear_tag','EAR-17');
        $this->postJson('/api/goats',['name'=>'Duplicate','ear_tag'=>'EAR-17'])->assertUnprocessable()->assertJsonValidationErrors('ear_tag');
        $this->postJson('/api/goats',['name'=>'Missing'])->assertUnprocessable()->assertJsonValidationErrors('ear_tag');
        $this->putJson('/api/goats/'.$result->json('goat.id'),['ear_tag'=>'018'])->assertOk()->assertJsonPath('goat.code','018');
    }

    public function test_motion_filter_separates_specific_anomalies(): void
    {
        $goat=Goat::create(['name'=>'Goat','code'=>'GT-001']);
        foreach (['Normal','Prolonged Inactivity','Excessive Movement'] as $movement) HealthLog::create(['goat_id'=>$goat->id,'event_type'=>'Telemetry','movement'=>$movement]);
        $this->actingAs(User::factory()->create(['role'=>'Staff']));
        $this->getJson('/api/health-logs?motion_anomaly=any')->assertOk()->assertJsonCount(2,'health_logs');
        $this->getJson('/api/health-logs?motion_anomaly=Prolonged%20Inactivity')->assertOk()->assertJsonCount(1,'health_logs')->assertJsonPath('health_logs.0.movement','Prolonged Inactivity');
        $this->getJson('/api/health-logs?motion_anomaly=none')->assertOk()->assertJsonCount(1,'health_logs');
    }

    public function test_report_download_uses_the_requested_dates(): void
    {
        $goat=Goat::create(['name'=>'Goat','code'=>'GT-001']);
        foreach ([['2026-09-01 12:00:00',32],['2026-09-02 12:00:00',39]] as [$date,$temp]) {
            $log=HealthLog::create(['goat_id'=>$goat->id,'event_type'=>'Telemetry','temperature'=>$temp]);$log->forceFill(['created_at'=>$date])->save();
        }
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $pdf=$this->get('/api/reports/export?start_date=2026-09-01&end_date=2026-09-01')->assertOk()->assertHeader('Content-Type','application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF-1.4',$pdf);
        $this->assertStringContainsString('Health logs: 1',$pdf);
        $this->assertStringContainsString('2026-09-01 to 2026-09-01',$pdf);
        $this->assertStringContainsString('/Subtype /Image', $pdf);
        $this->assertStringContainsString('Temperature summary', $pdf);
        preg_match('/startxref\n(\d+)/', $pdf, $xref);
        $this->assertSame('xref', substr($pdf, (int) $xref[1], 4));
        preg_match_all('/(\d{10}) 00000 n /', $pdf, $offsets);
        foreach ($offsets[1] as $index => $offset) $this->assertStringStartsWith(($index + 1).' 0 obj', substr($pdf, (int) $offset));
        $csv=$this->get('/api/reports/export?format=csv&start_date=2026-09-01&end_date=2026-09-01')->assertOk()->streamedContent();
        $this->assertStringContainsString('"Health logs",1',$csv);
        $this->getJson('/api/reports/export?start_date=2026-09-02&end_date=2026-09-01')->assertUnprocessable();
    }

    public function test_medical_pdf_includes_all_types_and_can_be_scoped_to_a_goat(): void
    {
        $goat=Goat::create(['name'=>'Goat','code'=>'GT-001']);
        foreach (['Vaccination','Deworming','Treatment','Checkup'] as $type) MedicalRecord::create(['goat_id'=>$goat->id,'record_type'=>$type,'title'=>$type.' record','description'=>'Complete details (with parentheses)']);
        $other=Goat::create(['name'=>'Other','code'=>'GT-002']);
        MedicalRecord::create(['goat_id'=>$other->id,'record_type'=>'Treatment','title'=>'Other goat only']);
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $all=$this->get('/api/medical-records/export')->assertOk()->getContent();
        foreach (['Vaccination','Deworming','Treatment','Checkup','Other goat only'] as $text) $this->assertStringContainsString($text,$all);
        $scoped=$this->get('/api/medical-records/export?goat_id='.$goat->id)->assertOk()->getContent();
        $this->assertStringNotContainsString('Other goat only',$scoped);
        $this->assertStringContainsString('Total records: 4',$scoped);
    }

    public function test_exports_obey_feature_permissions(): void
    {
        $this->getJson('/api/medical-records/export')->assertUnauthorized();
        DB::table('role_permissions')->insert(['role'=>'Caretaker','permissions'=>json_encode(['reports.read'=>false,'medical-records.read'=>false])]);
        $this->actingAs(User::factory()->create(['role'=>'Caretaker']));
        $this->get('/api/reports/export')->assertForbidden();
        $this->get('/api/medical-records/export')->assertForbidden();
    }

    public function test_pdf_paginates_and_has_valid_object_offsets(): void
    {
        $pdf=(new PdfDocument)->render('Long medical record',array_fill(0,120,'Treatment details (complete) \\ continued'));
        $this->assertStringContainsString('/Count 3',$pdf);
        preg_match('/startxref\n(\d+)/',$pdf,$match);
        $this->assertSame('xref',substr($pdf,(int)$match[1],4));
        preg_match_all('/(\d{10}) 00000 n /',$pdf,$offsets);
        foreach ($offsets[1] as $index=>$offset) $this->assertStringStartsWith(($index+1).' 0 obj',substr($pdf,(int)$offset));
    }
}
