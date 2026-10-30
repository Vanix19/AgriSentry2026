@extends('auth.account-layout')
@section('content')
<div class="page-header"><div><h1 class="page-title">NANA history import</h1><p class="page-sub">Preview simulated readings for October 28–31, 2026.</p></div></div>
<section class="panel"><div class="panel-body">
<p>These are <strong>simulated demonstration readings</strong>, not measurements from NANA’s collar. They will appear in history and contribute to reports. Existing records are preserved.</p>
<p>Times use Asia/Shanghai (UTC+8). Full days have a target of 720 readings; October 31 ends before 3:00 PM with a target of 450. Each added record contains temperature and motion. Refresh the profile after import.</p>
<div class="table-wrap" style="overflow-x:auto"><table><thead><tr><th>Date</th><th>Existing records</th><th>To add</th><th>Total after import</th><th>Target</th></tr></thead><tbody>
@foreach($plan['days'] as $date => $day)
<tr><td>{{ $date }}</td><td>{{ $day['existing'] }}</td><td>{{ $day['adding'] }}</td><td>{{ $day['existing'] + $day['adding'] }}</td><td>{{ $day['limit'] }}</td></tr>
@endforeach
</tbody></table></div>
<p>Normal simulated temperatures are 35.5–37.2°C, with low spikes near 2 AM and high spikes near noon. Motion includes Prolonged Inactivity on October 28 and Excessive Movement at 7, 9, and 11 AM each day where space is available.</p>
<p>Existing readings occupy their two-minute slots and count toward the daily target. Days already above the target are left unchanged. Records after the October 31 cutoff are also preserved. Pause NANA’s live collar ingestion during import so new readings do not change the preview.</p>
@if($plan['skipped_motion'])<p class="message">{{ $plan['skipped_motion'] }} scheduled simulated motion events cannot be added because their slots are occupied or the day is full. Previously imported events also count here. Existing readings will not be overwritten.</p>@endif
@if(count($plan['rows']))
<form method="post" action="/admin/nana-history">@csrf
<label><input type="checkbox" name="acknowledge" value="1" required> I understand these are simulated readings and will be included in reports.</label>
<button class="btn btn-primary" type="submit">Import {{ count($plan['rows']) }} simulated readings</button>
</form>
@else<p class="message">No additional readings fit within the available slots and daily targets.</p>@endif
<div class="settings-links"><a class="btn" href="/goat/4/profile">View NANA’s profile</a><a class="btn" href="/admin/nana-history">Refresh preview</a></div>
</div></section>
@endsection
