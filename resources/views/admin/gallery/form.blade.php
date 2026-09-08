@extends('layouts.admin')

@section('title', $item->exists ? 'Edit galeri' : 'Tambah galeri')
@section('content')
@php
  $mediaType = old('media_type', match (true) {
    $item->isUploadedVideo() => 'file',
    $item->isYoutubeVideo() => 'youtube',
    default => 'photo',
  });
  $maxVideoMb = \App\Services\GalleryVideoStore::MAX_MEGABYTES;
@endphp
<div class="page-head">
  <div>
    <h1>{{ $item->exists ? 'Edit galeri' : 'Tambah item galeri' }}</h1>
    <p class="sub">Foto, video YouTube, atau upload MP4. Video hanya dimuat saat pengunjung menekan thumbnail di website.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ route('admin.gallery.index') }}">Kembali</a>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.gallery.update', $item) : route('admin.gallery.store') }}" id="gallery-form">
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
    <input name="group_name" value="{{ old('group_name', $item->group_name) }}" placeholder="Opsional — item dengan grup sama dikelompokkan">
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

  <fieldset class="gallery-media-fieldset">
    <legend>Tipe media</legend>
    <div class="card-style-options gallery-media-type-options">
      <label class="card-style-option">
        <input type="radio" name="media_type" value="photo" @checked($mediaType === 'photo') data-gallery-media-type>
        <span><b>Foto</b><small>Hanya gambar di gallery.</small></span>
      </label>
      <label class="card-style-option">
        <input type="radio" name="media_type" value="youtube" @checked($mediaType === 'youtube') data-gallery-media-type>
        <span><b>YouTube</b><small>Link video — thumbnail YouTube otomatis jika poster kosong.</small></span>
      </label>
      <label class="card-style-option">
        <input type="radio" name="media_type" value="file" @checked($mediaType === 'file') data-gallery-media-type>
        <span><b>Upload video</b><small>MP4/WebM maks. {{ $maxVideoMb }} MB — wajib poster/thumbnail.</small></span>
      </label>
    </div>
  </fieldset>

  <div class="gallery-media-panel" data-gallery-panel="youtube" @if($mediaType !== 'youtube') hidden @endif>
    <label>Link video YouTube
      <input name="video_url" value="{{ old('video_url', $item->video_url) }}" placeholder="https://youtube.com/watch?v=…">
    </label>
  </div>

  <div class="gallery-media-panel" data-gallery-panel="file" @if($mediaType !== 'file') hidden @endif>
    @if($item->isUploadedVideo())
      <p class="sub">Video sekarang: <a href="{{ $item->video_path }}" target="_blank" rel="noopener">Lihat file</a></p>
      <label class="check">
        <input type="checkbox" name="delete_video_file" value="1" @checked(old('delete_video_file'))>
        Hapus video upload (ganti dengan file baru di bawah)
      </label>
    @endif
    <label>Unggah video MP4/WebM
      <input type="file" name="video_file" accept="video/mp4,video/webm,.mp4,.webm">
    </label>
    <p class="sub">Maksimal {{ $maxVideoMb }} MB per upload. Kompres video pendek (Reels-style) sebelum unggah agar halaman tetap ringan.</p>
  </div>

  @if($item->displayImage())
    <p class="sub">{{ $item->isVideo() ? 'Thumbnail:' : 'Gambar sekarang:' }}</p>
    <img class="thumb large" src="{{ $item->displayImage() }}" alt="{{ $item->title }}">
  @endif

  <label>Unggah thumbnail / foto
    <input type="file" id="gallery-upload" name="photo" accept="image/*" data-upload-preview="gallery-upload">
  </label>
  <p class="sub gallery-poster-note" data-gallery-poster-note @if($mediaType !== 'file') hidden @endif>Wajib untuk upload video — dipakai sebagai poster sebelum video diputar.</p>
  <p class="sub gallery-photo-note" data-gallery-photo-note @if($mediaType === 'file') hidden @endif>Foto otomatis dikecilkan di browser sebelum unggah, lalu dioptimalkan lagi di server.</p>
  @include('admin.partials.image-upload-preview', ['inputId' => 'gallery-upload', 'hint' => 'Pratinjau thumbnail'])
  <label>Atau URL foto/thumbnail
    <input name="image_url" value="{{ old('image_url', str_starts_with((string) $item->image, 'http') ? $item->image : '') }}" placeholder="https://…">
  </label>

  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('gallery-form');
  if (!form) return;

  var panels = form.querySelectorAll('[data-gallery-panel]');
  var posterNote = form.querySelector('[data-gallery-poster-note]');
  var photoNote = form.querySelector('[data-gallery-photo-note]');

  function syncMediaPanels() {
    var selected = form.querySelector('input[name="media_type"]:checked');
    var type = selected ? selected.value : 'photo';

    panels.forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-gallery-panel') !== type;
    });

    if (posterNote) posterNote.hidden = type !== 'file';
    if (photoNote) photoNote.hidden = type === 'file';
  }

  form.querySelectorAll('[data-gallery-media-type]').forEach(function (input) {
    input.addEventListener('change', syncMediaPanels);
  });

  syncMediaPanels();
})();
</script>
@endpush
