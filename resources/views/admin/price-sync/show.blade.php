@extends('layouts.admin')

@section('title', 'Preview Changes')
@section('content')
@php
  use App\Models\PriceSyncChange;

  $rp = fn (?int $amount) => $amount && $amount > 0 ? 'Rp'.number_format($amount, 0, ',', '.') : '—';
  $priceLine = fn (?array $snapshot) => $snapshot
    ? collect([
        'Q '.$rp($snapshot['price_quad'] ?? null),
        'T '.$rp($snapshot['price_triple'] ?? null),
        'D '.$rp($snapshot['price_double'] ?? null),
      ])->implode(' · ')
    : '—';
  $dateLabel = fn (?array $snapshot) => filled($snapshot['departure_date'] ?? null)
    ? \Carbon\Carbon::parse($snapshot['departure_date'])->translatedFormat('d M Y')
    : '—';
  $titleLabel = fn (?array $snapshot) => $snapshot['title'] ?? '—';
  $newDepartureCount = $changes->where('change_status', PriceSyncChange::STATUS_NEW_DEPARTURE)->count();
@endphp

<div class="page-head">
  <div>
    <h1>Preview Changes</h1>
    <p class="sub">{{ $run->reference() }} · {{ $run->created_at?->translatedFormat('d M Y H:i') }} WIB · {{ $run->user?->name }}</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.price-sync.index') }}">Riwayat Sync</a>
    @if($run->isWaiting())
      <form method="post" action="{{ route('admin.price-sync.store') }}">
        @csrf
        <button class="btn ghost" type="submit">Sync ulang</button>
      </form>
    @endif
  </div>
</div>

<div class="stat-row">
  <div class="stat"><div><div class="stat-label">Ditemukan</div><div class="stat-value">{{ $run->total_found }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Produk baru</div><div class="stat-value">{{ $run->total_new }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Keberangkatan baru</div><div class="stat-value">{{ $newDepartureCount }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Update harga</div><div class="stat-value">{{ $run->total_changed }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Tidak berubah</div><div class="stat-value">{{ $run->total_unchanged }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Tidak ditemukan</div><div class="stat-value">{{ $run->total_removed }}</div></div></div>
</div>

<form method="post" action="{{ route('admin.price-sync.apply', $run) }}" id="price-sync-form">
  @csrf
  <div class="filter-bar">
    <label class="check filter-check">
      <input type="checkbox" id="select-all-changes" @disabled(! $run->isWaiting())> Select All
    </label>
    <a class="btn gray" href="{{ route('admin.price-sync.show', ['priceSyncRun' => $run, 'only_changes' => request()->boolean('only_changes') ? null : 1]) }}">
      {{ request()->boolean('only_changes') ? 'Tampilkan semua' : 'Only show changes' }}
    </a>
    @if($run->isWaiting())
      <button class="btn" type="submit" id="update-selected-btn" disabled>Update Selected</button>
    @endif
  </div>

  <div class="panel">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            @if($run->isWaiting())<th></th>@endif
            <th>Periode (web)</th>
            <th>Judul (DB)</th>
            <th>Tanggal DB</th>
            <th>Tanggal web</th>
            <th>Harga DB</th>
            <th>Harga web</th>
            <th>Bedanya</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($changes as $change)
            @php
              $existing = $change->existing_snapshot;
              $incoming = $change->incoming_snapshot;
              $dateChanged = ($existing['departure_date'] ?? null) !== ($incoming['departure_date'] ?? null);
            @endphp
            <tr>
              @if($run->isWaiting())
                <td>
                  @if($change->isApplicable() && ! $change->applied_at)
                    <label class="check table-check">
                      <input type="checkbox" name="change_ids[]" value="{{ $change->id }}" class="change-checkbox" data-applicable="1">
                    </label>
                  @elseif($change->applied_at)
                    <span class="badge published">Applied</span>
                  @endif
                </td>
              @endif
              <td>
                <b>{{ $change->periodeLabel() }}</b>
                @if($change->source_key)<small>{{ $change->source_key }}</small>@endif
              </td>
              <td>{{ $titleLabel($existing) }}</td>
              <td class="{{ $dateChanged ? 'is-diff' : '' }}">{{ $dateLabel($existing) }}</td>
              <td class="{{ $dateChanged ? 'is-diff' : '' }}">{{ $dateLabel($incoming) }}</td>
              <td>{{ $priceLine($existing) }}</td>
              <td>{{ $priceLine($incoming) }}</td>
              <td class="sync-diff-cell">
                @forelse($change->diffDetails() as $detail)
                  <small>{{ $detail['detail'] }}</small>
                @empty
                  —
                @endforelse
              </td>
              <td><span class="badge {{ $change->change_status }}">{{ $change->statusLabel() }}</span></td>
            </tr>
          @empty
            <tr><td colspan="{{ $run->isWaiting() ? 9 : 8 }}" class="empty-state">Tidak ada data pada filter ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</form>

@if($run->isWaiting())
<script>
(function () {
  var form = document.getElementById('price-sync-form');
  var selectAll = document.getElementById('select-all-changes');
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.change-checkbox'));
  var submitBtn = document.getElementById('update-selected-btn');

  function syncSubmit() {
    var checked = boxes.filter(function (box) { return box.checked; }).length;
    if (submitBtn) submitBtn.disabled = checked === 0;
  }

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      boxes.forEach(function (box) { box.checked = selectAll.checked; });
      syncSubmit();
    });
  }

  boxes.forEach(function (box) {
    box.addEventListener('change', syncSubmit);
  });

  if (form) {
    form.addEventListener('submit', function (event) {
      var checked = boxes.filter(function (box) { return box.checked; }).length;
      if (!window.confirm('Anda akan memperbarui ' + checked + ' data berdasarkan hasil sync terbaru. Lanjutkan?')) {
        event.preventDefault();
      }
    });
  }
})();
</script>
<style>.is-diff { color: #b45309; font-weight: 700; }</style>
@endif
@endsection
