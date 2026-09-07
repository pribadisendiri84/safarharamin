@extends('layouts.admin')

@section('title', 'Master Hotel')
@section('content')
<div class="page-head">
  <div>
    <h1>Master Hotel</h1>
    <p class="sub">Nama hotel boleh sama di Makkah dan Madinah. Bintang diisi di sini, bukan di form paket.</p>
  </div>
</div>

@include('admin.partials.scope-tabs')

<div class="tabs">
  <a class="{{ $location === '' ? 'active' : '' }}" href="{{ route('admin.hotels.index', request()->only('trashed')) }}">Semua</a>
  @foreach(\App\Models\Hotel::LOCATIONS as $key => $label)
    <a class="{{ $location === $key ? 'active' : '' }}" href="{{ route('admin.hotels.index', array_filter(['location' => $key, 'trashed' => request('trashed')])) }}">{{ $label }}</a>
  @endforeach
</div>

@unless($trashed)
<div class="panel form-narrow">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'plus']) Tambah hotel</div>
  <form class="form form-pad" method="post" enctype="multipart/form-data" action="{{ route('admin.hotels.store') }}">
    @csrf
    @if($location)<input type="hidden" name="location" value="{{ $location }}">@endif
    <div class="row3">
      <label>Nama hotel<input name="name" value="{{ old('name') }}" required placeholder="Contoh: Hilton"></label>
      <label>Lokasi
        <select name="location" @if($location) disabled @endif required>
          @foreach(\App\Models\Hotel::LOCATIONS as $key => $label)
            <option value="{{ $key }}" @selected(old('location', $location ?: \App\Models\Hotel::LOCATION_MAKKAH) === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Bintang
        <input type="number" name="stars" value="{{ old('stars', 4) }}" min="1" max="5" required>
      </label>
    </div>
    <label>Logo hotel
      <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
      <span class="sub">Opsional. Logo dipakai di kartu katalog, maksimal 2 MB.</span>
    </label>
    <label>Urutan<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Tampil di pilihan</label>
    <button class="btn" type="submit">Tambah hotel</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap master-table-wrap">
    <table class="master-table">
      <thead>
        <tr>
          <th>Hotel</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($hotels as $hotel)
          <tr class="{{ $hotel->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($hotel->trashed())
                <b>{{ $hotel->name }}</b>
                <small>{{ $hotel->locationLabel() }} · {{ $hotel->stars }}★</small>
                <span class="badge draft">Terhapus</span>
              @else
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.hotels.update', $hotel) }}" class="form master-row-edit">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="location" value="{{ $hotel->location }}">
                  @include('admin.partials.master-logo-field', ['logo' => $hotel->logo])
                  <input name="name" value="{{ old('name', $hotel->name) }}" required placeholder="Nama hotel">
                  <span class="master-row-tag">{{ $hotel->locationLabel() }}</span>
                  <input type="number" name="stars" value="{{ old('stars', $hotel->stars ?? 4) }}" min="1" max="5" required aria-label="Bintang">
                  <input type="number" name="sort_order" value="{{ old('sort_order', $hotel->sort_order) }}" min="0" aria-label="Urutan">
                  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($hotel->is_active)> Aktif</label>
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
              @endif
            </td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $hotel,
                'destroy' => route('admin.hotels.destroy', $hotel),
                'restore' => route('admin.hotels.restore', $hotel),
                'confirm' => 'Hapus hotel ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="2" class="empty-state">{{ $trashed ? 'Tidak ada hotel terhapus.' : 'Belum ada hotel.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
