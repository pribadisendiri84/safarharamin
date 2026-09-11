@extends('layouts.admin')

@section('title', 'Sync Harga Arminareka')
@section('content')
<div class="page-head">
  <div>
    <h1>Sync Harga Arminareka</h1>
    <p class="sub">Ambil jadwal terbaru, bandingkan dengan paket existing, lalu pilih data yang akan diperbarui.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.price-list.index') }}">Price List</a>
    <a class="btn gray" href="{{ route('admin.price-sync.schedule.edit') }}">Sync Schedule</a>
    <form method="post" action="{{ route('admin.price-sync.store') }}">
      @csrf
      <button class="btn" type="submit">@include('admin.partials.icon', ['name' => 'upload']) Sync Now</button>
    </form>
  </div>
</div>

@if($pendingRun)
  <div class="summary">
    <span class="bubble tone-gold">@include('admin.partials.icon', ['name' => 'inbox'])</span>
    <div>
      <b>{{ $pendingRun->total_new + $pendingRun->total_changed + $pendingRun->total_removed }} perubahan menunggu approval</b>
      <p>Sync {{ $pendingRun->reference() }} · {{ $pendingRun->created_at?->translatedFormat('d M Y H:i') }} WIB</p>
    </div>
    <a class="btn gray" href="{{ route('admin.price-sync.show', $pendingRun) }}">Review Changes</a>
  </div>
@endif

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Sync</th>
          <th>Trigger</th>
          <th>Status</th>
          <th>Hasil</th>
          <th>Di-update</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($runs as $run)
          <tr>
            <td><b>{{ $run->reference() }}</b><small>{{ $run->user?->name ?? 'Sistem' }}</small></td>
            <td>{{ $run->trigger === 'scheduled' ? 'Scheduled' : 'Manual' }}</td>
            <td><span class="badge {{ $run->status }}">{{ str_replace('_', ' ', $run->status) }}</span></td>
            <td>
              {{ $run->total_found }} data ·
              {{ $run->total_new }} baru ·
              {{ $run->total_changed }} berubah ·
              {{ $run->total_removed }} hilang
            </td>
            <td>{{ $run->total_applied }}</td>
            <td>{{ $run->created_at?->translatedFormat('d M Y H:i') }}</td>
            <td><a class="btn gray compact" href="{{ route('admin.price-sync.show', $run) }}">View Changes</a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">Belum ada riwayat sync.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{ $runs->links() }}
@endsection
