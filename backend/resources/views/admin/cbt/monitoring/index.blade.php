@extends('layouts.app')

@section('title', 'Monitoring CBT')
@section('breadcrumb', 'Monitoring')

@section('content')
<div class="page-header"><h1 class="page-title">Monitoring: {{ $exam->name }}</h1><p class="page-subtitle"><span id="summary" style="font-size:13px;"></span> • <a href="{{ route('admin.cbt.violations.csv', $exam) }}" class="text-primary" style="font-size:12px;">Export Violations CSV</a></p></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus p-0"><div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
<thead><tr><th>Siswa</th><th>Status</th><th>Sisa</th><th>Online</th><th>Pelanggaran</th><th>Jawab</th><th>Skor</th><th>Aksi</th></tr></thead>
<tbody id="monitoring-body"><tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">Memuat...</td></tr></tbody>
</table></div></div></div>
@endsection

@push('scripts')
<script>
function refreshMonitoring(){
  fetch("{{ route('admin.cbt.monitoring.data', $exam) }}").then(r=>r.json()).then(data=>{
    document.getElementById('summary').textContent = `Ongoing ${data.summary.ongoing} • Finished ${data.summary.finished} • Disqualified ${data.summary.disqualified} • Soal ${data.total_questions}`;
    const tbody=document.getElementById('monitoring-body');
    tbody.innerHTML='';
    data.sessions.forEach(s=>{
      const tr=document.createElement('tr');
      const online = s.is_online ? '<span class="badge-nexus badge-info">Online</span>' : '<span class="badge-nexus">Offline</span>';
      const sisa = s.remaining_seconds!==null ? Math.floor(s.remaining_seconds/60)+'m' : '—';
      tr.innerHTML=`<td>${s.student_name}</td><td><span class="badge-nexus badge-info">${s.status}</span></td><td>${sisa}</td><td>${online}</td><td>${s.violation_count}</td><td>${s.answered_count}</td><td>${s.score ?? '—'}</td><td>${s.status==='ongoing' ? `<form method="POST" action="/admin/cbt/exams/${s.exam_id ?? {{ $exam->id }}}/sessions/${s.id}/force" class="d-inline"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="btn btn-nexus-outline btn-sm text-danger" data-confirm="Hentikan sesi ini?">Force</button></form>` : ''}</td>`;
      tbody.appendChild(tr);
    });
  });
}
refreshMonitoring(); setInterval(refreshMonitoring, 5000);
</script>
@endpush
