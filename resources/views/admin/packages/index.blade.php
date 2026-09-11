@extends('layouts.admin')

@section('title', 'Paket')
@section('content')
<div class="page-head">
  <div>
    <h1>Paket keberangkatan</h1>
    <p class="sub">Centang <strong>Beranda</strong> (maks. {{ \App\Models\Package::homeLimit() }}). Drag untuk ubah urutan.</p>
  </div>
  <div class="actions head-actions">
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

<form class="filter-bar" method="get">
  @if($trashed)<input type="hidden" name="trashed" value="1">@endif
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul paket">
  <select name="status">
    <option value="">Semua status</option>
    @foreach(\App\Models\Package::STATUSES as $key => $label)
      <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="data_complete">
    <option value="">Kelengkapan data</option>
    <option value="1" @selected(request('data_complete') === '1')>Lengkap</option>
    <option value="0" @selected(request('data_complete') === '0')>Belum lengkap</option>
  </select>
  <input type="date" name="departure_from" value="{{ request('departure_from') }}" aria-label="Tanggal berangkat dari">
  <input type="date" name="departure_to" value="{{ request('departure_to') }}" aria-label="Tanggal berangkat sampai">
  <label class="check filter-check">
    <input type="checkbox" name="featured" value="1" @checked(request()->boolean('featured'))> Beranda
  </label>
  <label class="check filter-check">
    <input type="checkbox" name="needs_flyer" value="1" @checked(request()->boolean('needs_flyer'))> Perlu flyer
  </label>
  <button class="btn gray" type="submit">@include('admin.partials.icon', ['name' => 'search']) Filter</button>
  @if($hasActiveFilters ?? false)
    <a class="btn ghost" href="{{ route('admin.packages.index', request()->boolean('trashed') ? ['trashed' => 1] : []) }}">Reset</a>
  @endif
</form>
@if($packages->total() > 0)
  <p class="sub table-hint">
    Menampilkan {{ $packages->firstItem() }}–{{ $packages->lastItem() }} dari {{ $packages->total() }} paket
    @if($hasActiveFilters ?? false)
      · filter aktif
    @endif
  </p>
@endif
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
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          @if(! request()->boolean('trashed'))
            <th class="table-select">
              <label class="check table-check" title="Pilih semua di halaman ini">
                <input type="checkbox" id="package-select-all">
              </label>
            </th>
            <th>Beranda</th>
          @endif
          <th>Paket</th><th>Berangkat</th><th>Harga</th><th>Closing</th><th>Status</th><th>Waktu</th><th></th>
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
              <td>
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
            <td>
              <b>{{ $package->title }}</b>
              @if($package->isPastDeparture())
                <span class="badge past-departure">Periode lewat</span>
              @endif
              @if($package->isSeatsFull())
                <span class="badge seats-full">Seat penuh</span>
              @endif
              @if($package->needsFlyer())
                <span class="badge draft">Perlu flyer</span>
              @endif
              <small>{{ $package->catalogTypeLine() }} · {{ $package->duration_days }} hari</small>
            </td>
            <td>{{ $package->departureLine() }}</td>
            <td>{{ $package->formattedStartingPrice() }}<small>{{ $package->seatsLine() }}</small></td>
            <td>{{ (int) $package->sold_pax }} jamaah</td>
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
            <td>@include('admin.partials.timestamps', ['model' => $package])</td>
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
          <tr><td colspan="{{ request()->boolean('trashed') ? 7 : 9 }}" class="empty-state">{{ $trashed ? 'Tidak ada paket terhapus.' : 'Belum ada paket.' }}</td></tr>
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
@endsection
