@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('content')
<div class="page-head">
  <div>
    <h1>Pengaturan</h1>
    <p class="sub">Nama, logo, dan nomor WhatsApp. Ganti merek di sini, tanpa ubah kode.</p>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
  @csrf
  @method('PUT')
  <label>Nama situs
    <input name="site_name" value="{{ old('site_name', $site->name) }}" required>
  </label>
  <label>Tagline / deskripsi
    <input name="site_tagline" value="{{ old('site_tagline', $site->tagline) }}" required>
  </label>
  <label>Akhiran judul halaman
    <input name="site_title_suffix" value="{{ old('site_title_suffix', $site->titleSuffix) }}" required>
  </label>
  <label>Logo
    <input type="file" name="logo" accept="image/*">
  </label>
  <p class="sub"><img class="brand-logo" src="{{ $site->logoUrl }}" alt="{{ $site->name }}"></p>
  <label>Nomor WhatsApp (kode negara, tanpa +)
    <input name="wa_number" value="{{ old('wa_number', $site->waNumber) }}" required>
  </label>
  <div class="actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>
@endsection
