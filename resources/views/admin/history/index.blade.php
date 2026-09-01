@extends('layouts.admin')

@section('title', 'Riwayat')
@section('content')
<div class="page-head">
  <div>
    <h1>Riwayat data</h1>
    <p class="sub">Catatan dibuat, diubah, dihapus, dan dipulihkan. Hapus memakai soft delete — data masih bisa dikembalikan.</p>
  </div>
</div>

<form class="filter-bar" method="get">
  <select name="subject">
    <option value="">Semua data</option>
    @foreach($subjects as $type => $label)
      <option value="{{ $type }}" @selected(request('subject') === $type)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="action">
    <option value="">Semua aksi</option>
    @foreach($actions as $key => $label)
      <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <button class="btn gray" type="submit">@include('admin.partials.icon', ['name' => 'search']) Filter</button>
</form>

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Aksi</th>
          <th>Data</th>
          <th>Oleh</th>
          <th>Perubahan</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          <tr>
            <td class="time">{{ $log->created_at?->format('d M Y H:i') }}</td>
            <td><span class="pill {{ $log->action }}">{{ $log->actionLabel() }}</span></td>
            <td>
              <b>{{ $log->subject_label }}</b>
              <small>{{ $log->subjectTypeLabel() }} · #{{ $log->subject_id }}</small>
            </td>
            <td>{{ $log->actorLabel() }}</td>
            <td>
              @if($log->action === 'updated' && isset($log->properties['old'], $log->properties['new']))
                <small>
                  @foreach($log->properties['new'] as $field => $value)
                    {{ $field }}: {{ \Illuminate\Support\Str::limit(is_scalar($value) || $value === null ? (string) $value : json_encode($value), 40) }}{{ ! $loop->last ? ' · ' : '' }}
                  @endforeach
                </small>
              @elseif($log->action === 'created')
                <small>Data baru</small>
              @else
                <small>{{ $log->actionLabel() }}</small>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">Belum ada riwayat.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($logs->hasPages())
  <div class="pager">
    @if(! $logs->onFirstPage())
      <a class="btn gray" href="{{ $logs->previousPageUrl() }}">Sebelumnya</a>
    @endif
    @if($logs->hasMorePages())
      <a class="btn gray" href="{{ $logs->nextPageUrl() }}">Berikutnya</a>
    @endif
  </div>
@endif
@endsection
