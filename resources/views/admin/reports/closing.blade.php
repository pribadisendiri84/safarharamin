@extends('layouts.admin')

@section('title', 'Laporan Closing')
@section('content')
<div class="page-head">
  <div>
    <h1>Laporan closing paket</h1>
    <p class="sub">Rekap jamaah closing per paket keberangkatan dari pengajuan berstatus sold.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.packages.index') }}">Daftar paket</a>
  </div>
</div>

<div class="panel">
  <div class="panel-table-toolbar">
    <form class="table-filter-form" method="get" id="closing-report-filter-form">
      <div class="table-filter-search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul paket" data-filter-search>
      </div>
      <div class="table-filter-chips">
        @php
          $departureFrom = request('departure_from');
          $departureTo = request('departure_to');
          $departureFilterActive = filled($departureFrom) || filled($departureTo);
          $departureFilterLabel = $departureFilterActive
            ? trim(($departureFrom ?: '…').' – '.($departureTo ?: '…'), ' –')
            : null;
        @endphp
        @component('admin.partials.filter-chip', [
          'label' => 'Tanggal',
          'active' => $departureFilterActive,
          'value' => $departureFilterLabel,
        ])
          <label for="closing_departure_from">Dari</label>
          <input type="date" id="closing_departure_from" name="departure_from" value="{{ $departureFrom }}">
          <label for="closing_departure_to">Sampai</label>
          <input type="date" id="closing_departure_to" name="departure_to" value="{{ $departureTo }}">
        @endcomponent
      </div>
      @if($hasActiveFilters)
        <div class="table-filter-actions">
          <a class="btn ghost compact" href="{{ route('admin.reports.closing') }}">Reset</a>
        </div>
      @endif
    </form>
  </div>
  @if($packages->total() > 0)
    <p class="table-filter-meta">
      Menampilkan {{ $packages->firstItem() }}–{{ $packages->lastItem() }} dari {{ $packages->total() }} paket dengan closing
    </p>
  @endif
  <div class="table-wrap">
    <table class="packages-table-compact">
      <thead>
        <tr>
          <th class="package-col-combined">
            <span class="table-sort-group">
              @include('admin.partials.table-sort-link', ['column' => 'title', 'label' => 'Paket', 'defaultSort' => 'departure_date', 'defaultDir' => 'asc'])
              @include('admin.partials.table-sort-link', ['column' => 'departure_date', 'label' => 'Berangkat', 'defaultSort' => 'departure_date', 'defaultDir' => 'asc'])
            </span>
          </th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'sold_pax', 'label' => 'Jamaah closing', 'defaultSort' => 'departure_date', 'defaultDir' => 'asc'])</th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'sold_amount', 'label' => 'Nilai closing', 'defaultSort' => 'departure_date', 'defaultDir' => 'asc'])</th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'seats_left', 'label' => 'Seat tersisa', 'defaultSort' => 'departure_date', 'defaultDir' => 'asc'])</th>
        </tr>
      </thead>
      <tbody>
        @forelse($packages as $package)
          <tr>
            <td class="package-title-cell">
              <div class="package-title-line">
                <a class="package-title-link" href="{{ route('admin.packages.edit', $package) }}">{{ $package->title }}</a>
                <span class="package-row-date">{{ $package->departure_date?->translatedFormat('d M Y') ?? '—' }}</span>
              </div>
            </td>
            <td class="nowrap"><b>{{ (int) $package->sold_pax }}</b> jamaah</td>
            <td class="nowrap">
              @if((int) $package->sold_amount > 0)
                Rp{{ number_format((int) $package->sold_amount, 0, ',', '.') }}
              @else
                —
              @endif
            </td>
            <td class="nowrap">{{ (int) $package->seats_left }}/{{ (int) $package->seats_total }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">Belum ada closing tercatat.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($packages->hasPages())
  <div class="pager">{{ $packages->links('vendor.pagination.admin-compact') }}</div>
@endif

@include('admin.partials.table-filter-script')
@endsection
