@extends('layouts.admin')

@section('title', 'Kota embarkasi')
@section('content')
<div class="page-head">
  <div>
    <h1>Kota embarkasi</h1>
    <p class="sub">{{ $cities->count() }} kota di master. Dipakai di paket, filter website, dan form daftar.</p>
  </div>
</div>

@include('admin.partials.scope-tabs')

@unless($trashed)
<div class="panel form-narrow">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'plus']) Tambah kota</div>
  <form class="form form-pad" method="post" action="{{ route('admin.cities.store') }}">
    @csrf
    <div class="row2">
      <label>Nama kota<input name="name" value="{{ old('name') }}" required placeholder="Contoh: Cilegon"></label>
      <label>Urutan<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Tampil di pilihan</label>
    <button class="btn" type="submit">Tambah kota</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Kota</th>
          <th>Urutan</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($cities as $city)
          <tr class="{{ $city->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($city->trashed())
                <b>{{ $city->name }}</b>
                <small>{{ $city->slug }}</small>
              @else
                <form method="post" action="{{ route('admin.cities.update', $city) }}" class="form user-edit city-edit">
                  @csrf
                  @method('PUT')
                  <input name="name" value="{{ old('name', $city->name) }}" required>
                  <input type="number" name="sort_order" value="{{ old('sort_order', $city->sort_order) }}" min="0">
                  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($city->is_active)> Aktif</label>
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
                <small>{{ $city->slug }}</small>
              @endif
            </td>
            <td>{{ $city->sort_order }}</td>
            <td><span class="badge {{ $city->is_active && ! $city->trashed() ? 'published' : 'draft' }}">{{ $city->trashed() ? 'Terhapus' : ($city->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $city])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $city,
                'destroy' => route('admin.cities.destroy', $city),
                'restore' => route('admin.cities.restore', $city),
                'confirm' => 'Hapus kota ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">{{ $trashed ? 'Tidak ada kota terhapus.' : 'Belum ada kota.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
