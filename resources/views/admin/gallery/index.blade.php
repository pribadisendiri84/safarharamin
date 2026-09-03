@extends('layouts.admin')

@section('title', 'Galeri')
@section('content')
<div class="page-head">
  <div>
    <h1>Galeri</h1>
    <p class="sub">Centang <strong>Beranda</strong> (maks. {{ \App\Models\GalleryItem::homeLimit() }}). Drag untuk ubah urutan.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.gallery.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah foto</a>
  </div>
</div>

@include('admin.partials.scope-tabs')
@php
  $category = $category ?? '';
  $categoryQuery = request()->boolean('trashed') ? ['trashed' => 1] : [];
  $paginated = $items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp
<div class="tabs">
  <a class="{{ $category === '' ? 'active' : '' }}" href="{{ route('admin.gallery.index', $categoryQuery) }}">Semua</a>
  @foreach(\App\Models\GalleryItem::categories() as $key => $label)
    <a class="{{ $category === $key ? 'active' : '' }}" href="{{ route('admin.gallery.index', [...$categoryQuery, 'category' => $key]) }}">{{ $label }}</a>
  @endforeach
</div>

@if(! request()->boolean('trashed'))
<div class="panel form-pad sort-panel">
  <div class="sort-panel-head">
    <h2>Urutan beranda</h2>
    <p class="sub">Drag untuk ubah urutan tampil di homepage (maks. {{ \App\Models\GalleryItem::homeLimit() }} foto).</p>
  </div>
  <ul class="home-sort-list" id="home-sort-list" data-reorder-url="{{ route('admin.gallery.reorder') }}">
    @forelse($homeItems as $index => $item)
      <li class="home-sort-item" data-id="{{ $item->id }}" data-home-sort="{{ (int) ($item->home_sort ?? 0) }}">
        <span class="drag-handle" title="Drag untuk ubah urutan">⋮⋮</span>
        <img class="thumb" src="{{ $item->image }}" alt="{{ $item->title }}">
        <span class="home-sort-meta">
          <b>{{ $item->title }}</b>
          <small>Posisi {{ $item->home_sort }}</small>
        </span>
      </li>
    @empty
      <li class="empty-state">Belum ada foto beranda. Centang kolom Beranda di tabel bawah.</li>
    @endforelse
  </ul>
</div>
@endif

<div class="panel">
  <div class="table-wrap">
    @if(! request()->boolean('trashed'))
      <p class="sub table-hint">Drag kolom ⋮⋮ untuk urutan halaman <strong>/galeri</strong>.</p>
    @endif
    <table>
      <thead>
        <tr>
          @if(! request()->boolean('trashed'))<th></th>@endif
          <th>Beranda</th>
          <th>Foto</th>
          <th>Judul</th>
          <th>Kategori</th>
          <th>Grup</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="gallery-sort-table" @if(! request()->boolean('trashed')) data-reorder-url="{{ route('admin.gallery.reorder') }}" @endif>
        @forelse($items as $item)
          <tr class="{{ $item->trashed() ? 'is-deleted' : '' }}" @if(! $item->trashed() && ! request()->boolean('trashed')) data-id="{{ $item->id }}" @endif>
            @if(! request()->boolean('trashed'))
              <td>
                @if(! $item->trashed())
                  <button type="button" class="drag-handle" title="Drag untuk ubah urutan galeri">⋮⋮</button>
                @endif
              </td>
            @endif
            <td>
              @if(! $item->trashed())
                <label class="check table-check" title="Tampilkan di beranda">
                  <input type="checkbox"
                    data-gallery-home-toggle
                    data-id="{{ $item->id }}"
                    data-url="{{ route('admin.gallery.toggle-home', $item) }}"
                    @checked($item->show_on_home)>
                </label>
              @endif
            </td>
            <td><img class="thumb" src="{{ $item->image }}" alt="{{ $item->title }}"></td>
            <td>
              <b>{{ $item->title }}</b>
              @if($item->caption)<small>{{ $item->caption }}</small>@endif
            </td>
            <td>{{ $item->categoryLabel() }}</td>
            <td>{{ $item->group_name ?: '—' }}</td>
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
          <tr><td colspan="{{ request()->boolean('trashed') ? 7 : 8 }}" class="empty-state">{{ $trashed ? 'Tidak ada foto terhapus.' : 'Belum ada foto. Tambah dari tombol di atas.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($paginated && $items->hasPages())
  <div class="pager">
    @if(! $items->onFirstPage())
      <a class="btn gray" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
    @endif
    @if($items->hasMorePages())
      <a class="btn gray" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
    @endif
  </div>
@endif

@if(! request()->boolean('trashed'))
  @include('admin.partials.gallery-sort-script')
@endif
@endsection
