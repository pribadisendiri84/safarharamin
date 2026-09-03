@extends('layouts.admin')

@section('title', 'Master Maskapai')
@section('content')
<div class="page-head">
  <div>
    <h1>Master Maskapai</h1>
    <p class="sub">Daftar maskapai untuk dropdown di paket katalog dan keberangkatan.</p>
  </div>
</div>

@include('admin.partials.scope-tabs')

@unless($trashed)
<div class="panel form-narrow">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'plus']) Tambah maskapai</div>
  <form class="form form-pad" method="post" action="{{ route('admin.airlines.store') }}">
    @csrf
    <div class="row2">
      <label>Nama maskapai<input name="name" value="{{ old('name') }}" required placeholder="Contoh: Saudia"></label>
      <label>Urutan<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Tampil di pilihan</label>
    <button class="btn" type="submit">Tambah maskapai</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Maskapai</th>
          <th>Urutan</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($airlines as $airline)
          <tr class="{{ $airline->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($airline->trashed())
                <b>{{ $airline->name }}</b>
              @else
                <form method="post" action="{{ route('admin.airlines.update', $airline) }}" class="form user-edit">
                  @csrf
                  @method('PUT')
                  <input name="name" value="{{ old('name', $airline->name) }}" required>
                  <input type="number" name="sort_order" value="{{ old('sort_order', $airline->sort_order) }}" min="0">
                  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($airline->is_active)> Aktif</label>
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
              @endif
            </td>
            <td>{{ $airline->sort_order }}</td>
            <td><span class="badge {{ $airline->is_active && ! $airline->trashed() ? 'published' : 'draft' }}">{{ $airline->trashed() ? 'Terhapus' : ($airline->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $airline])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $airline,
                'destroy' => route('admin.airlines.destroy', $airline),
                'restore' => route('admin.airlines.restore', $airline),
                'confirm' => 'Hapus maskapai ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">{{ $trashed ? 'Tidak ada maskapai terhapus.' : 'Belum ada maskapai.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
