@extends('layouts.admin')

@section('title', 'Preview Changes')
@section('content')
@php
  use App\Models\PriceSyncChange;

  $newDepartureCount = $changes->where('change_status', PriceSyncChange::STATUS_NEW_DEPARTURE)->count();
  $tableColumns = $run->isWaiting() ? 5 : 4;
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
    <form method="post" action="{{ route('admin.price-sync.destroy', $run) }}" onsubmit="return confirm('Hapus riwayat sync {{ $run->reference() }}?')">
      @csrf
      @method('DELETE')
      <button class="btn red" type="submit">Hapus riwayat</button>
    </form>
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
  <div class="panel">
    <div class="panel-table-toolbar">
      <div class="table-filter-chips">
        @if($run->isWaiting())
          <label class="filter-toggle">
            <input type="checkbox" id="select-all-changes">
            <span>Pilih semua</span>
          </label>
        @endif
        <a class="btn gray compact" href="{{ route('admin.price-sync.show', ['priceSyncRun' => $run, 'only_changes' => request()->boolean('only_changes') ? null : 1]) }}">
          {{ request()->boolean('only_changes') ? 'Tampilkan semua' : 'Hanya perubahan' }}
        </a>
      </div>
      @if($run->isWaiting())
        <div class="table-filter-actions">
          <button class="btn compact" type="submit" id="update-selected-btn" disabled>Update terpilih</button>
        </div>
      @endif
    </div>
    <div class="table-wrap sync-table-wrap">
      <table>
        <thead>
          <tr>
            @if($run->isWaiting())<th></th>@endif
            <th>Periode (DB)</th>
            <th>Tanggal (DB)</th>
            <th>Bedanya</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($changes as $change)
            <tr @class(['sync-row-has-diff' => $change->highlightsDiff()])>
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
                <b>{{ $change->dbPeriodeLabel() }}</b>
                @if($change->source_key)
                  <small>{{ $change->source_key }}</small>
                @endif
                @if($change->showsWebPeriodeHint())
                  <small>Web: {{ $change->periodeLabel() }}</small>
                @endif
              </td>
              <td>{{ $change->dbDepartureDateLabel() }}</td>
              <td class="sync-diff-cell">
                @php $bedanyaRows = $change->bedanyaRows(); @endphp
                @if($bedanyaRows === [])
                  —
                @else
                  <ul class="sync-diff-list">
                    @foreach($bedanyaRows as $row)
                      @if(($row['type'] ?? '') === 'diff')
                        <li class="sync-diff-row">
                          <span class="sync-diff-label">{{ $row['label'] }}</span>
                          <span class="sync-diff-values">
                            <span class="from">{{ $row['from'] }}</span>
                            <span class="arrow">→</span>
                            <span class="to">{{ $row['to'] }}</span>
                          </span>
                        </li>
                      @else
                        <li><p class="sync-diff-note">{{ $row['text'] ?? '' }}</p></li>
                      @endif
                    @endforeach
                  </ul>
                @endif
              </td>
              <td><span class="badge {{ $change->change_status }}">{{ $change->statusLabel() }}</span></td>
            </tr>
          @empty
            <tr><td colspan="{{ $tableColumns }}" class="empty-state">Tidak ada data pada filter ini.</td></tr>
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
@endif
@endsection
