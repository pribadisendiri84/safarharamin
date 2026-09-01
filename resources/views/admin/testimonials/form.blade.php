@extends('layouts.admin')

@section('title', $testimonial->exists ? 'Edit testimoni' : 'Tambah testimoni')
@section('content')
<div class="page-head">
  <div>
    <h1>{{ $testimonial->exists ? 'Edit testimoni' : 'Tambah testimoni' }}</h1>
    <p class="sub">Centang Tayang agar muncul di website.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ route('admin.testimonials.index') }}">Kembali</a>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}">
  @csrf
  @if($testimonial->exists) @method('PUT') @endif

  <label>Nama jamaah
    <input name="name" value="{{ old('name', $testimonial->name) }}" required>
  </label>
  <div class="row2">
    <label>Kota
      <input name="city" value="{{ old('city', $testimonial->city) }}">
    </label>
    <label>Urutan
      <input type="number" name="sort_order" value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}" min="0">
    </label>
  </div>
  <label>Paket yang diikuti
    <input name="package_title" value="{{ old('package_title', $testimonial->package_title) }}" placeholder="Umroh Hemat 9 Hari Jakarta">
  </label>
  <label>Testimoni
    <textarea name="quote" rows="5" required>{{ old('quote', $testimonial->quote) }}</textarea>
  </label>
  <label class="check">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $testimonial->exists ? $testimonial->is_published : true))>
    Tayang di website
  </label>
  <button class="btn" type="submit">Simpan</button>
</form>
@endsection
