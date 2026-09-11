@extends('layouts.admin')

@section('title', 'Paket')
@section('content')
<div class="page-head">
  <div>
    <h1>Paket keberangkatan</h1>
    <p class="sub">Centang <strong>Beranda</strong> (maks. {{ \App\Models\Package::homeLimit() }}). Drag untuk ubah urutan.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.price-sync.report') }}">Riwayat sync</a>
    <a class="btn gray" href="{{ route('admin.reports.closing') }}">Closing</a>
    <a class="btn gray" href="{{ route('admin.price-sync.index') }}">@include('admin.partials.icon', ['name' => 'upload']) Sync Harga</a>
    <a class="btn gray" href="{{ route('admin.packages.import') }}">@include('admin.partials.icon', ['name' => 'upload']) Import CSV</a>
    <a class="btn" href="{{ route('admin.packages.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah paket</a>
  </div>
</div>
@include('admin.partials.scope-tabs')
@if(session('import_errors'))
  <div class="alert err panel form-pad">
    <strong>Baris gagal diimport:</strong>
    <ul class="checks">
      @foreach(session('import_errors') as $error)
        <li>Baris {{ $error['row'] }}: {{ $error['message'] }}</li>
      @endforeach
    </ul>
  </div>
@endif

@if(! request()->boolean('trashed'))
<div class="panel form-pad sort-panel">
  <div class="sort-panel-head">
    <h2>Urutan beranda</h2>
    <p class="sub">Drag untuk ubah urutan tampil di homepage (maks. {{ \App\Models\Package::homeLimit() }} paket).</p>
  </div>
  <ul class="home-sort-list" id="package-home-sort-list" data-reorder-url="{{ route('admin.packages.reorder-home') }}">
    @forelse($homePackages as $index => $package)
      <li class="home-sort-item" data-id="{{ $package->id }}" data-home-sort="{{ (int) ($package->home_sort ?? 0) }}">
        <span class="drag-handle" title="Drag untuk ubah urutan">⋮⋮</span>
        @if($package->coverImage())
          <img class="thumb" src="{{ $package->coverImage() }}" alt="{{ $package->title }}">
        @else
          <span class="thumb thumb-empty">Flyer</span>
        @endif
        <span class="home-sort-meta">
          <b>{{ $package->title }}</b>
          <small>Posisi {{ $package->home_sort }} · {{ $package->departureLine() }} · {{ $package->formattedStartingPrice() }}</small>
        </span>
        <span class="home-sort-actions">
          <a class="btn gray compact" href="{{ route('admin.packages.edit', $package) }}">Edit</a>
          <button class="btn red compact" type="button" data-package-home-remove data-id="{{ $package->id }}" data-url="{{ route('admin.packages.toggle-featured', $package) }}">Hapus</button>
        </span>
      </li>
    @empty
      <li class="empty-state">Belum ada paket beranda. Centang kolom Beranda di tabel bawah.</li>
    @endforelse
  </ul>
</div>
@endif

@php
  $statusFilter = request('status');
  $statusFilterLabel = filled($statusFilter) ? (\App\Models\Package::STATUSES[$statusFilter] ?? $statusFilter) : null;
  $dataCompleteFilter = request('data_complete');
  $dataCompleteLabel = match ($dataCompleteFilter) {
    '1' => 'Lengkap',
    '0' => 'Belum lengkap',
    default => null,
  };
  $departureFrom = request('departure_from');
  $departureTo = request('departure_to');
  $departureFilterActive = filled($departureFrom) || filled($departureTo);
  $departureFilterLabel = $departureFilterActive
    ? trim(($departureFrom ?: '…').' – '.($departureTo ?: '…'), ' –')
    : null;
@endphp
@if(! request()->boolean('trashed'))
  <form class="bulk-bar" id="package-bulk-bar" method="post" action="{{ route('admin.packages.bulk-status') }}" hidden>
    @csrf
    <input type="hidden" name="status" value="published">
    <p class="sub"><span id="package-bulk-count">0</span> paket terpilih</p>
    <button class="btn compact" type="submit">Tayangkan terpilih</button>
    <button class="btn gray compact" type="button" id="package-bulk-clear">Batal</button>
  </form>
@endif
<div class="panel">
  <div class="panel-table-toolbar">
    <form class="table-filter-form" method="get" id="packages-filter-form">
      @if($trashed)<input type="hidden" name="trashed" value="1">@endif
      <div class="table-filter-search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul paket" data-filter-search>
      </div>
      <div class="table-filter-chips">
        @component('admin.partials.filter-chip', [
          'label' => 'Status',
          'active' => filled($statusFilter),
          'value' => $statusFilterLabel,
        ])
          <select name="status">
            <option value="">Semua status</option>
            @foreach(\App\Models\Package::STATUSES as $key => $label)
              <option value="{{ $key }}" @selected($statusFilter === $key)>{{ $label }}</option>
            @endforeach
          </select>
        @endcomponent

        @component('admin.partials.filter-chip', [
          'label' => 'Data',
          'active' => filled($dataCompleteFilter),
          'value' => $dataCompleteLabel,
        ])
          <select name="data_complete">
            <option value="">Semua</option>
            <option value="1" @selected($dataCompleteFilter === '1')>Lengkap</option>
            <option value="0" @selected($dataCompleteFilter === '0')>Belum lengkap</option>
          </select>
        @endcomponent

        @component('admin.partials.filter-chip', [
          'label' => 'Tanggal',
          'active' => $departureFilterActive,
          'value' => $departureFilterLabel,
        ])
          <label for="departure_from">Dari</label>
          <input type="date" id="departure_from" name="departure_from" value="{{ $departureFrom }}">
          <label for="departure_to">Sampai</label>
          <input type="date" id="departure_to" name="departure_to" value="{{ $departureTo }}">
        @endcomponent

        <label class="filter-toggle">
          <input type="checkbox" name="featured" value="1" @checked(request()->boolean('featured'))>
          <span>Beranda</span>
        </label>
        <label class="filter-toggle">
          <input type="checkbox" name="needs_flyer" value="1" @checked(request()->boolean('needs_flyer'))>
          <span>Perlu flyer</span>
        </label>
      </div>
      @if($hasActiveFilters ?? false)
        <div class="table-filter-actions">
          <a class="btn ghost compact" href="{{ route('admin.packages.index', request()->boolean('trashed') ? ['trashed' => 1] : []) }}">Reset</a>
        </div>
      @endif
    </form>
  </div>
  @if($packages->total() > 0)
    <p class="table-filter-meta">
      Menampilkan {{ $packages->firstItem() }}–{{ $packages->lastItem() }} dari {{ $packages->total() }} paket
      @if($hasActiveFilters ?? false)
        · filter aktif
      @endif
    </p>
  @endif
  <div class="table-wrap">
    <table class="packages-table-compact">
      <thead>
        <tr>
          @if(! request()->boolean('trashed'))
            <th class="table-select">
              <label class="check table-check" title="Pilih semua di halaman ini">
                <input type="checkbox" id="package-select-all">
              </label>
            </th>
            <th class="table-icon-col" title="Beranda">⌂</th>
          @endif
          <th class="package-col-combined">
            <span class="table-sort-group">
              @include('admin.partials.table-sort-link', ['column' => 'title', 'label' => 'Paket', 'defaultSort' => 'updated_at', 'defaultDir' => 'desc'])
              @include('admin.partials.table-sort-link', ['column' => 'departure_date', 'label' => 'Berangkat', 'defaultSort' => 'updated_at', 'defaultDir' => 'desc'])
            </span>
          </th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'seats_left', 'label' => 'Seat', 'defaultSort' => 'updated_at', 'defaultDir' => 'desc'])</th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'status', 'label' => 'Status', 'defaultSort' => 'updated_at', 'defaultDir' => 'desc'])</th>
          <th>@include('admin.partials.table-sort-link', ['column' => 'updated_at', 'label' => 'Diupdate', 'defaultSort' => 'updated_at', 'defaultDir' => 'desc'])</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($packages as $package)
          <tr class="{{ collect([$package->trashed() ? 'is-deleted' : null, $package->isPastDeparture() ? 'is-past-departure' : null, $package->isSeatsFull() ? 'is-seats-full' : null])->filter()->implode(' ') }}">
            @if(! request()->boolean('trashed'))
              <td class="table-select">
                @if(! $package->trashed())
                  <label class="check table-check" title="Pilih paket">
                    <input type="checkbox" class="package-select" name="package_ids[]" value="{{ $package->id }}" form="package-bulk-bar">
                  </label>
                @endif
              </td>
              <td class="table-icon-col">
                @if(! $package->trashed())
                  <label class="check table-check" title="Tampilkan di beranda">
                    <input type="checkbox"
                      data-package-home-toggle
                      data-id="{{ $package->id }}"
                      data-url="{{ route('admin.packages.toggle-featured', $package) }}"
                      @checked($package->is_featured)>
                  </label>
                @endif
              </td>
            @endif
            <td class="package-title-cell">
              <div class="package-title-line">
                <a class="package-title-link" href="{{ route('admin.packages.edit', $package) }}">{{ $package->title }}</a>
                <span class="package-row-date">{{ $package->departure_date?->translatedFormat('d M Y') ?? '—' }}</span>
                <span class="package-row-badges">
                  @if($package->isPastDeparture())<span class="badge past-departure">Lewat</span>@endif
                  @if($package->isSeatsFull())<span class="badge seats-full">Penuh</span>@endif
                  @if($package->needsFlyer())<span class="badge draft">Flyer</span>@endif
                </span>
              </div>
            </td>
            <td class="nowrap">{{ (int) $package->seats_left }}/{{ (int) $package->seats_total }}</td>
            <td>
              @if(! $package->trashed())
                <select class="status-select {{ $package->status }}"
                  data-package-status
                  data-id="{{ $package->id }}"
                  data-url="{{ route('admin.packages.update-status', $package) }}">
                  @foreach(\App\Models\Package::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected($package->status === $key)>{{ $label }}</option>
                  @endforeach
                </select>
              @else
                <span class="badge {{ $package->status }}">{{ \App\Models\Package::STATUSES[$package->status] ?? $package->status }}</span>
              @endif
            </td>
            <td class="nowrap muted-compact">{{ $package->updated_at?->format('d M Y H:i') ?? '—' }}</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $package,
                'edit' => route('admin.packages.edit', $package),
                'duplicate' => route('admin.packages.duplicate', $package),
                'destroy' => route('admin.packages.destroy', $package),
                'restore' => route('admin.packages.restore', $package),
                'confirm' => 'Hapus paket ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ request()->boolean('trashed') ? 5 : 7 }}" class="empty-state">{{ $trashed ? 'Tidak ada paket terhapus.' : 'Belum ada paket.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($packages->hasPages())
  <div class="pager">{{ $packages->links('vendor.pagination.admin-compact') }}</div>
@endif

@if(! request()->boolean('trashed'))
  @include('admin.partials.package-home-sort-script')
  @include('admin.partials.package-status-script')
  @include('admin.partials.package-bulk-script')
@endif
@include('admin.partials.table-filter-script')
@endsection
