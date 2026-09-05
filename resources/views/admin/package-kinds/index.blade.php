@extends('layouts.admin')

@section('title', 'Master Tipe Paket')
@section('content')
<div class="page-head">
  <div>
    <h1>Master Tipe Paket</h1>
    <p class="sub">Pilihan tipe paket (Arafah, Mina, Muzdalifah) untuk dropdown di katalog. Terpisah dari jenis Umroh/Haji.</p>
  </div>
</div>

@include('admin.partials.scope-tabs')

@unless($trashed)
<div class="panel form-narrow">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'plus']) Tambah tipe paket</div>
  <form class="form form-pad" method="post" action="{{ route('admin.package-kinds.store') }}">
    @csrf
    <div class="row2">
      <label>Nama<input name="name" value="{{ old('name') }}" required placeholder="Contoh: Arafah"></label>
      <label>Urutan<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Tampil di pilihan</label>
    <button class="btn" type="submit">Tambah tipe paket</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tipe paket</th>
          <th>Urutan</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($kinds as $kind)
          <tr class="{{ $kind->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($kind->trashed())
                <b>{{ $kind->name }}</b>
              @else
                <form method="post" action="{{ route('admin.package-kinds.update', $kind) }}" class="form user-edit">
                  @csrf
                  @method('PUT')
                  <input name="name" value="{{ old('name', $kind->name) }}" required>
                  <input type="number" name="sort_order" value="{{ old('sort_order', $kind->sort_order) }}" min="0">
                  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($kind->is_active)> Aktif</label>
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
              @endif
            </td>
            <td>{{ $kind->sort_order }}</td>
            <td><span class="badge {{ $kind->is_active && ! $kind->trashed() ? 'published' : 'draft' }}">{{ $kind->trashed() ? 'Terhapus' : ($kind->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $kind])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $kind,
                'destroy' => route('admin.package-kinds.destroy', $kind),
                'restore' => route('admin.package-kinds.restore', $kind),
                'confirm' => 'Hapus tipe paket ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">{{ $trashed ? 'Tidak ada tipe paket terhapus.' : 'Belum ada tipe paket.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
