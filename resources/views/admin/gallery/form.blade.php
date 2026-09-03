@extends('layouts.admin')

@section('title', $item->exists ? 'Edit foto' : 'Tambah foto')
@section('content')
<div class="page-head">
  <div>
    <h1>{{ $item->exists ? 'Edit foto' : 'Tambah foto galeri' }}</h1>
    <p class="sub">Unggah file atau tempel URL. Foto muncul di website setelah disimpan.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ route('admin.gallery.index') }}">Kembali</a>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.gallery.update', $item) : route('admin.gallery.store') }}">
  @csrf
  @if($item->exists) @method('PUT') @endif

  <label>Judul
    <input name="title" value="{{ old('title', $item->title) }}" required>
  </label>
  <label>Kategori
    <select name="category" required>
      @foreach(\App\Models\GalleryItem::categories() as $key => $label)
        <option value="{{ $key }}" @selected(old('category', $item->category ?: 'umroh') === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </label>
  <label>Grup (mis. Manasik, Bandara, Madinah)
    <input name="group_name" value="{{ old('group_name', $item->group_name) }}" placeholder="Opsional — foto dengan grup sama dikelompokkan">
  </label>
  <label>Keterangan
    <input name="caption" value="{{ old('caption', $item->caption) }}">
  </label>
  <label>Urutan halaman galeri
    <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
  </label>
  <p class="sub">Bisa juga diatur dengan drag-drop di daftar galeri admin.</p>
  <label class="check">
    <input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', $item->show_on_home))>
    Tampilkan di beranda (urutan drag-drop di daftar galeri)
  </label>
  @if($item->image)
    <p class="sub">Foto sekarang:</p>
    <img class="thumb large" src="{{ $item->image }}" alt="{{ $item->title }}">
  @endif
  <label>Unggah foto
    <input type="file" id="gallery-upload" name="photo" accept="image/*" data-upload-preview="gallery-upload">
  </label>
  <p class="sub">Foto otomatis dikecilkan saat disimpan. Pratinjau muncul setelah memilih file.</p>
  @include('admin.partials.image-upload-preview', ['inputId' => 'gallery-upload', 'hint' => 'Pratinjau foto baru'])
  <label>Atau URL foto
    <input name="image_url" value="{{ old('image_url', str_starts_with((string) $item->image, 'http') ? $item->image : '') }}" placeholder="https://…">
  </label>
  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>
@endsection
