@extends('layouts.admin')

@section('title', 'Template Konten — '.$kind->name)
@section('content')
<div class="page-head">
  <div>
    <h1>Template konten: {{ $kind->name }}</h1>
    <p class="sub">Default cover katalog, deskripsi, hotel, fasilitas, dan tidak termasuk untuk paket baru dari sync Arminareka jika field tersebut masih kosong.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.package-kinds.index') }}">Kembali ke Tipe Paket</a>
  </div>
</div>

<form class="panel form-pad form-narrow" method="post" enctype="multipart/form-data" action="{{ route('admin.package-kinds.template.update', $kind) }}">
  @csrf
  @method('PUT')

  @include('admin.partials.catalog-cover-upload', [
    'cover' => old('remove_cover') ? null : $kind->cover_image,
  ])

  <div class="row2">
    <div>
      <label>Hotel Makkah
        @include('partials.hotel-select', [
          'name' => 'hotel_makkah',
          'location' => \App\Models\Hotel::LOCATION_MAKKAH,
          'selected' => old('hotel_makkah', $kind->hotel_makkah ?? ''),
        ])
      </label>
      <label class="check">
        <input type="checkbox" name="hotel_makkah_setaraf" value="1" @checked(old('hotel_makkah_setaraf', $kind->hotel_makkah_setaraf))>
        Hotel dapat diganti dengan yang setaraf
      </label>
    </div>
    <div>
      <label>Hotel Madinah
        @include('partials.hotel-select', [
          'name' => 'hotel_madinah',
          'location' => \App\Models\Hotel::LOCATION_MADINAH,
          'selected' => old('hotel_madinah', $kind->hotel_madinah ?? ''),
        ])
      </label>
      <label class="check">
        <input type="checkbox" name="hotel_madinah_setaraf" value="1" @checked(old('hotel_madinah_setaraf', $kind->hotel_madinah_setaraf))>
        Hotel dapat diganti dengan yang setaraf
      </label>
    </div>
  </div>

  <label>Fasilitas / include (satu baris satu item)
    <textarea name="facilities_text" rows="4">{{ old('facilities_text', implode("\n", $kind->facilities ?? [])) }}</textarea>
  </label>

  <label>Tidak termasuk (satu baris satu item)
    <textarea name="exclusions_text" rows="4">{{ old('exclusions_text', implode("\n", $kind->exclusions ?? [])) }}</textarea>
  </label>

  <label>Deskripsi
    <textarea name="description" rows="4" placeholder="{{ \App\Support\PackageKindContentTemplate::DEFAULT_DESCRIPTION }}">{{ old('description', $kind->description) }}</textarea>
  </label>
  <p class="sub">Gunakan placeholder <code>{nama}</code> — nilai diisi otomatis dari data paket saat sync. Contoh: <code>Paket {type_short} {package_kind} {duration_days} Hari dengan hotel bintang {hotel_stars}, maskapai {airline}, dan pendampingan muthawwif berbahasa Indonesia.</code></p>
  <ul class="checks template-placeholder-list">
    @foreach(\App\Support\PackageKindContentTemplate::PLACEHOLDERS as $key => $label)
      <li><code>{!! '{'.$key.'}' !!}</code> — {{ $label }}</li>
    @endforeach
  </ul>

  <button class="btn" type="submit">Simpan template</button>
</form>
@endsection
