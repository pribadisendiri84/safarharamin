@extends('layouts.admin')

@section('title', 'Master Hotel')
@section('content')
<div class="page-head">
  <div>
    <h1>Master Hotel</h1>
    <p class="sub">Daftar hotel untuk dropdown di paket katalog dan keberangkatan.</p>
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
  <form class="form form-pad" method="post" action="{{ route('admin.hotels.store') }}">
    @csrf
    @if($location)<input type="hidden" name="location" value="{{ $location }}">@endif
    <div class="row3">
      <label>Nama hotel<input name="name" value="{{ old('name') }}" required placeholder="Contoh: Swissotel Makkah"></label>
      <label>Lokasi
        <select name="location" @if($location) disabled @endif required>
          @foreach(\App\Models\Hotel::LOCATIONS as $key => $label)
            <option value="{{ $key }}" @selected(old('location', $location ?: \App\Models\Hotel::LOCATION_MAKKAH) === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Urutan<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Tampil di pilihan</label>
    <button class="btn" type="submit">Tambah hotel</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Hotel</th>
          <th>Lokasi</th>
          <th>Urutan</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($hotels as $hotel)
          <tr class="{{ $hotel->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($hotel->trashed())
                <b>{{ $hotel->name }}</b>
              @else
                <form method="post" action="{{ route('admin.hotels.update', $hotel) }}" class="form user-edit">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="location" value="{{ $hotel->location }}">
                  <input name="name" value="{{ old('name', $hotel->name) }}" required>
                  <input type="number" name="sort_order" value="{{ old('sort_order', $hotel->sort_order) }}" min="0">
                  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($hotel->is_active)> Aktif</label>
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
              @endif
            </td>
            <td>{{ $hotel->locationLabel() }}</td>
            <td>{{ $hotel->sort_order }}</td>
            <td><span class="badge {{ $hotel->is_active && ! $hotel->trashed() ? 'published' : 'draft' }}">{{ $hotel->trashed() ? 'Terhapus' : ($hotel->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $hotel])</td>
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
          <tr><td colspan="6" class="empty-state">{{ $trashed ? 'Tidak ada hotel terhapus.' : 'Belum ada hotel.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
