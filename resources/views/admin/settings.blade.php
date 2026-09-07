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
    <legend>Tampilan katalog</legend>
    <p class="sub">Pilih susunan kartu yang dipakai di beranda, katalog paket, dan rekomendasi paket.</p>
    <div class="card-style-options">
      <label class="card-style-option">
        <input type="radio" name="package_card_style" value="{{ \App\Support\SiteProfile::CARD_STYLE_CLASSIC }}"
          @checked(old('package_card_style', $site->packageCardStyle) === \App\Support\SiteProfile::CARD_STYLE_CLASSIC)>
        <span>
          <b>Klasik</b>
          <small>Flyer tinggi dengan informasi ringkas dan grid ikon.</small>
        </span>
      </label>
      <label class="card-style-option">
        <input type="radio" name="package_card_style" value="{{ \App\Support\SiteProfile::CARD_STYLE_CATALOG }}"
          @checked(old('package_card_style', $site->packageCardStyle) === \App\Support\SiteProfile::CARD_STYLE_CATALOG)>
        <span>
          <b>Katalog</b>
          <small>Harga dan judul menonjol, dilengkapi logo maskapai serta hotel.</small>
        </span>
      </label>
    </div>
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

  <fieldset>
    <legend>Kurs haji</legend>
    <p class="sub">Ditampilkan di halaman Haji Plus. Bisa diisi manual atau diperbarui otomatis dari kurs {{ old('haji_exchange_rate_currency', $hajiExchangeRate['currency']) }} → IDR.</p>
    <label class="check">
      <input type="checkbox" name="haji_exchange_rate_enabled" value="1" @checked(old('haji_exchange_rate_enabled', $hajiExchangeRate['enabled']) === '1')>
      Tampilkan kurs di halaman haji
    </label>
    <label>Mata uang
      <input name="haji_exchange_rate_currency" value="{{ old('haji_exchange_rate_currency', $hajiExchangeRate['currency']) }}" maxlength="8" required>
    </label>
    <div class="card-style-options">
      <label class="card-style-option">
        <input type="radio" name="haji_exchange_rate_mode" value="{{ \App\Support\HajiExchangeRate::MODE_MANUAL }}"
          @checked(old('haji_exchange_rate_mode', $hajiExchangeRate['mode']) === \App\Support\HajiExchangeRate::MODE_MANUAL)>
        <span>
          <b>Manual</b>
          <small>Admin mengisi nilai kurs sendiri.</small>
        </span>
      </label>
      <label class="card-style-option">
        <input type="radio" name="haji_exchange_rate_mode" value="{{ \App\Support\HajiExchangeRate::MODE_AUTO }}"
          @checked(old('haji_exchange_rate_mode', $hajiExchangeRate['mode']) === \App\Support\HajiExchangeRate::MODE_AUTO)>
        <span>
          <b>Otomatis</b>
          <small>Diperbarui otomatis setiap 6 jam saat halaman haji dibuka.</small>
        </span>
      </label>
    </div>
    <label>Nilai kurs (IDR per 1 mata uang)
      <input type="number" name="haji_exchange_rate" value="{{ old('haji_exchange_rate', $hajiExchangeRate['rate']) }}" min="1" step="1" placeholder="17735">
    </label>
    @if($hajiExchangeRate['updated_at'])
      <p class="sub">Terakhir diperbarui: {{ \Carbon\Carbon::parse($hajiExchangeRate['updated_at'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
    @endif
  </fieldset>

  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>

@if(old('haji_exchange_rate_mode', $hajiExchangeRate['mode']) === \App\Support\HajiExchangeRate::MODE_AUTO)
<form method="post" action="{{ route('admin.settings.refresh-exchange-rate') }}" class="panel form-pad form-narrow settings-refresh-form">
  @csrf
  <p class="sub">Mode otomatis aktif. Klik untuk tarik kurs terbaru tanpa menunggu pembaruan 6 jam.</p>
  <button class="btn gray" type="submit">Perbarui kurs sekarang</button>
</form>
@endif
@endsection
