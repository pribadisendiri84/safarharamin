@extends('layouts.admin')

@section('title', 'Master PIC')
@section('content')
<div class="page-head">
  <div>
    <h1>Master PIC</h1>
    <p class="sub">Daftar PIC untuk dropdown di data jamaah. Tidak perlu punya akun login.</p>
  </div>
</div>

@include('admin.partials.scope-tabs')

@unless($trashed)
<div class="panel form-narrow">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'plus']) Tambah PIC</div>
  <form class="form form-pad" method="post" action="{{ route('admin.pics.store') }}">
    @csrf
    <div class="row2">
      <label>Nama PIC<input name="name" value="{{ old('name') }}" required placeholder="Contoh: Yanti"></label>
      <label>Nomor HP<input name="phone" value="{{ old('phone') }}" placeholder="Opsional"></label>
    </div>
    <div class="row2">
      <label>Urutan<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></label>
      <label class="check" style="align-self:end"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Tampil di pilihan</label>
    </div>
    <button class="btn" type="submit">Tambah PIC</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>PIC</th>
          <th>HP</th>
          <th>Urutan</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($pics as $pic)
          <tr class="{{ $pic->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($pic->trashed())
                <b>{{ $pic->name }}</b>
              @else
                <form method="post" action="{{ route('admin.pics.update', $pic) }}" class="form user-edit">
                  @csrf
                  @method('PUT')
                  <input name="name" value="{{ old('name', $pic->name) }}" required>
                  <input name="phone" value="{{ old('phone', $pic->phone) }}" placeholder="HP">
                  <input type="number" name="sort_order" value="{{ old('sort_order', $pic->sort_order) }}" min="0">
                  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($pic->is_active)> Aktif</label>
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
              @endif
            </td>
            <td>{{ $pic->phone ?: '—' }}</td>
            <td>{{ $pic->sort_order }}</td>
            <td><span class="badge {{ $pic->is_active && ! $pic->trashed() ? 'published' : 'draft' }}">{{ $pic->trashed() ? 'Terhapus' : ($pic->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $pic])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $pic,
                'destroy' => route('admin.pics.destroy', $pic),
                'restore' => route('admin.pics.restore', $pic),
                'confirm' => 'Hapus PIC ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-state">{{ $trashed ? 'Tidak ada PIC terhapus.' : 'Belum ada PIC.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
