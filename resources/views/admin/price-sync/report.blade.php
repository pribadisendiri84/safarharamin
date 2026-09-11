@extends('layouts.admin')

@section('title', 'Riwayat Perubahan Sync')
@section('content')
<div class="page-head">
  <div>
    <h1>Riwayat perubahan sync</h1>
    <p class="sub">Semua perubahan produk yang sudah diterapkan dari Arminareka — tanggal, tujuan, harga, seat, dan lainnya.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.price-sync.index') }}">Sync Harga</a>
  </div>
</div>

<div class="panel">
  <div class="panel-table-toolbar">
    <form class="table-filter-form" method="get" id="sync-report-filter-form">
      <div class="table-filter-search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari paket / ID Arminareka" data-filter-search>
      </div>
      <div class="table-filter-chips">
        @php
          $fieldFilter = request('field');
          $fieldLabel = filled($fieldFilter) ? ($fieldOptions[$fieldFilter] ?? $fieldFilter) : null;
        @endphp
        @component('admin.partials.filter-chip', [
          'label' => 'Field',
          'active' => filled($fieldFilter),
          'value' => $fieldLabel,
        ])
          <select name="field">
            <option value="">Semua perubahan</option>
            @foreach($fieldOptions as $key => $label)
              <option value="{{ $key }}" @selected($fieldFilter === $key)>{{ $label }}</option>
            @endforeach
          </select>
        @endcomponent
      </div>
      @if($hasActiveFilters)
        <div class="table-filter-actions">
          <a class="btn ghost compact" href="{{ route('admin.price-sync.report') }}">Reset</a>
        </div>
      @endif
    </form>
  </div>
  @if($changes->total() > 0)
    <p class="table-filter-meta">
      Menampilkan {{ $changes->firstItem() }}–{{ $changes->lastItem() }} dari {{ $changes->total() }} perubahan
    </p>
  @endif
  <div class="table-wrap sync-table-wrap">
    <table>
      <thead>
        <tr>
          <th>@include('admin.partials.table-sort-link', ['column' => 'applied_at', 'label' => 'Waktu apply', 'defaultSort' => 'applied_at', 'defaultDir' => 'desc'])</th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'title', 'label' => 'Paket', 'defaultSort' => 'applied_at', 'defaultDir' => 'desc'])</th>
          <th>Perubahan</th>
          <th>Sync</th>
        </tr>
      </thead>
      <tbody>
        @forelse($changes as $change)
          <tr @class(['sync-row-has-diff' => $change->bedanyaRows() !== []])>
            <td class="nowrap muted-compact">
              {{ $change->applied_at?->format('d M Y H:i') ?? '—' }}
              <small>{{ $change->appliedSummaryLabel() }}</small>
            </td>
            <td>
              <b>{{ $change->reportPackageTitle() }}</b>
              @if($change->source_key)<small>{{ $change->source_key }}</small>@endif
              @if($change->package)
                <small><a href="{{ route('admin.packages.edit', $change->package) }}">Edit paket</a></small>
              @endif
            </td>
            <td class="sync-diff-cell">
              @php $bedanyaRows = $change->bedanyaRows(); @endphp
              @if($bedanyaRows === [])
                <span class="muted-compact">—</span>
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
            <td class="nowrap">
              @if($change->run)
                <a href="{{ route('admin.price-sync.show', $change->run) }}">{{ $change->run->reference() }}</a>
                <small>{{ $change->run->user?->name ?? 'Sistem' }}</small>
              @else
                —
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">Belum ada perubahan sync yang diterapkan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($changes->hasPages())
  <div class="pager">{{ $changes->links('vendor.pagination.admin-compact') }}</div>
@endif

@include('admin.partials.table-filter-script')
@endsection
