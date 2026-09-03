@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('content')
<div class="page-head">
  <div>
    <h1>Pengaturan</h1>
    <p class="sub">Nama, logo, nomor WhatsApp, dan teks pesan otomatis. Ganti merek di sini, tanpa ubah kode.</p>
  </div>
</div>

<form class="form panel form-pad form-narrow settings-form" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
  @csrf
  @method('PUT')

  <fieldset>
    <legend>Merek & identitas</legend>
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
  </fieldset>

  <fieldset>
    <legend>WhatsApp</legend>
    <label>Nomor WhatsApp (kode negara, tanpa +)
      <input name="wa_number" value="{{ old('wa_number', $site->waNumber) }}" required>
    </label>
    <p class="sub">Dipakai semua tombol dan form WhatsApp di website.</p>

    <div class="settings-sub">
      <h3 class="settings-sub-title">Tombol header</h3>
      <p class="settings-sub-desc">Tombol &ldquo;Chat WhatsApp&rdquo; di kanan atas.</p>
      <label>Pesan saat diklik
        <textarea name="wa_msg_header" rows="2" required>{{ old('wa_msg_header', $waMessages['wa_msg_header']) }}</textarea>
      </label>
      <p class="placeholder-hint">Placeholder: <code>{site}</code> nama situs</p>
    </div>

    <div class="settings-sub">
      <h3 class="settings-sub-title">Tombol mengambang</h3>
      <p class="settings-sub-desc">Tombol hijau di kanan bawah.</p>
      <label class="check">
        <input type="checkbox" name="wa_float_enabled" value="1" @checked(old('wa_float_enabled', $waMessages['wa_float_enabled']) === '1')>
        Tampilkan tombol
      </label>
      <label>Teks tombol
        <input name="wa_float_label" value="{{ old('wa_float_label', $waMessages['wa_float_label']) }}" required>
      </label>
      <label>Pesan saat diklik
        <textarea name="wa_msg_float" rows="2" required>{{ old('wa_msg_float', $waMessages['wa_msg_float']) }}</textarea>
      </label>
      <p class="placeholder-hint">Placeholder: <code>{site}</code> nama situs</p>
    </div>

    <div class="settings-sub">
      <h3 class="settings-sub-title">Pesan dari form website</h3>
      <p class="settings-sub-desc">Teks otomatis saat jamaah kirim dari form tanya paket atau daftar.</p>
      <p class="placeholder-hint">
        Placeholder:
        <code>{site}</code> nama situs ·
        <code>{name}</code> nama jamaah ·
        <code>{phone}</code> nomor WA ·
        <code>{package_title}</code> judul paket ·
        <code>{package_price}</code> harga ·
        <code>{package_duration}</code> durasi (hari) ·
        <code>{package_departure}</code> jadwal berangkat ·
        <code>{package_part}</code> teks paket opsional ·
        <code>{pax}</code> jumlah jamaah ·
        <code>{city}</code> kota
      </p>
      <label>Form tanya paket
        <textarea name="wa_msg_package" rows="5" required>{{ old('wa_msg_package', $waMessages['wa_msg_package']) }}</textarea>
      </label>
      <label>Form daftar jamaah
        <textarea name="wa_msg_register" rows="3" required>{{ old('wa_msg_register', $waMessages['wa_msg_register']) }}</textarea>
      </label>
      <label>Balasan admin ke jamaah
        <textarea name="wa_msg_inquiry_reply" rows="2" required>{{ old('wa_msg_inquiry_reply', $waMessages['wa_msg_inquiry_reply']) }}</textarea>
      </label>
    </div>
  </fieldset>

  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>
@endsection
