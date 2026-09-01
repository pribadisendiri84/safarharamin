@extends('layouts.admin')

@section('title', 'Galeri')
@section('content')
<div class="page-head">
  <div>
    <h1>Galeri</h1>
    <p class="sub">Foto yang tampil di beranda dan halaman /galeri.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.gallery.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah foto</a>
  </div>
</div>

@include('admin.partials.scope-tabs')
<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Foto</th>
          <th>Judul</th>
          <th>Urutan</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
          <tr class="{{ $item->trashed() ? 'is-deleted' : '' }}">
            <td><img class="thumb" src="{{ $item->image }}" alt="{{ $item->title }}"></td>
            <td>
              <b>{{ $item->title }}</b>
              @if($item->caption)<small>{{ $item->caption }}</small>@endif
            </td>
            <td>{{ $item->sort_order }}</td>
            <td>@include('admin.partials.timestamps', ['model' => $item])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $item,
                'edit' => route('admin.gallery.edit', $item),
                'destroy' => route('admin.gallery.destroy', $item),
                'restore' => route('admin.gallery.restore', $item),
                'confirm' => 'Hapus foto ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">{{ $trashed ? 'Tidak ada foto terhapus.' : 'Belum ada foto. Tambah dari tombol di atas.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($items->hasPages())
  <div class="pager">
    @if(! $items->onFirstPage())
      <a class="btn gray" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
    @endif
    @if($items->hasMorePages())
      <a class="btn gray" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
    @endif
  </div>
@endif
@endsection
