@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('content')
<div class="admin-editor">
  <div class="page-head">
    <div>
      <h1>Pengaturan</h1>
      <p class="sub">Merek, WhatsApp, tampilan katalog, dan kurs haji — tanpa ubah kode.</p>
    </div>
    <div class="head-actions">
      <a class="btn ghost" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat website</a>
    </div>
  </div>

  <form class="form admin-editor-form" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <nav class="admin-editor-nav" aria-label="Bagian pengaturan" data-editor-tabs>
      <button type="button" class="is-active" data-editor-tab="brand">Merek</button>
      <button type="button" data-editor-tab="catalog">Tampilan katalog</button>
      <button type="button" data-editor-tab="whatsapp">WhatsApp</button>
      <button type="button" data-editor-tab="exchange">Kurs haji</button>
    </nav>

    <div class="admin-editor-body">
      <section class="admin-editor-section is-active" data-editor-section="brand">
        <div class="admin-panel">
          <header class="admin-panel-head">
            <h2 class="admin-panel-title">Merek &amp; identitas</h2>
            <p class="admin-panel-desc">Nama, tagline, logo, dan judul tab browser.</p>
          </header>
          <label>Nama situs
            <input name="site_name" value="{{ old('site_name', $site->name) }}" required>
          </label>
          <label>Tagline / deskripsi
            <input name="site_tagline" value="{{ old('site_tagline', $site->tagline) }}" required>
          </label>
          <label>Akhiran judul halaman
            <input name="site_title_suffix" value="{{ old('site_title_suffix', $site->titleSuffix) }}" required>
          </label>
          <div class="admin-brand-row">
            <label class="admin-file-pick">
              <span>Logo</span>
              <input type="file" name="logo" accept="image/*">
            </label>
            <img class="admin-logo-preview" src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
          </div>
        </div>
      </section>

      <section class="admin-editor-section" data-editor-section="catalog" hidden>
        <div class="admin-panel">
          <header class="admin-panel-head">
            <h2 class="admin-panel-title">Tampilan katalog</h2>
            <p class="admin-panel-desc">Susunan kartu di beranda, katalog paket, dan rekomendasi.</p>
          </header>
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
        </div>
      </section>

      <section class="admin-editor-section" data-editor-section="whatsapp" hidden>
        <div class="admin-panel">
          <header class="admin-panel-head">
            <h2 class="admin-panel-title">WhatsApp</h2>
            <p class="admin-panel-desc">Nomor dan teks pesan otomatis di seluruh website.</p>
          </header>

          <label>Nomor WhatsApp <small class="admin-label-hint">kode negara, tanpa +</small>
            <input name="wa_number" value="{{ old('wa_number', $site->waNumber) }}" required>
          </label>

          <div class="admin-subblock">
            <h3 class="admin-subblock-title">Tombol header</h3>
            <p class="admin-panel-desc">Tombol &ldquo;Chat WhatsApp&rdquo; di kanan atas.</p>
            <label>Pesan saat diklik
              <textarea name="wa_msg_header" rows="2" required>{{ old('wa_msg_header', $waMessages['wa_msg_header']) }}</textarea>
            </label>
            <p class="placeholder-hint">Placeholder: <code>{site}</code> nama situs</p>
          </div>

          <div class="admin-subblock">
            <h3 class="admin-subblock-title">Tombol mengambang</h3>
            <p class="admin-panel-desc">Tombol hijau di kanan bawah.</p>
            <label class="check">
              <input type="checkbox" name="wa_float_enabled" value="1" @checked(old('wa_float_enabled', $waMessages['wa_float_enabled']) === '1')>
              Tampilkan tombol
            </label>
            <div class="row2">
              <label>Teks tombol
                <input name="wa_float_label" value="{{ old('wa_float_label', $waMessages['wa_float_label']) }}" required>
              </label>
              <label>Pesan saat diklik
                <textarea name="wa_msg_float" rows="2" required>{{ old('wa_msg_float', $waMessages['wa_msg_float']) }}</textarea>
              </label>
            </div>
            <p class="placeholder-hint">Placeholder: <code>{site}</code> nama situs</p>
          </div>

          <div class="admin-subblock">
            <h3 class="admin-subblock-title">Pesan dari form website</h3>
            <p class="admin-panel-desc">Teks otomatis saat jamaah kirim dari form tanya paket atau daftar.</p>
            <p class="placeholder-hint">
              Placeholder:
              <code>{site}</code> ·
              <code>{name}</code> ·
              <code>{phone}</code> ·
              <code>{package_title}</code> ·
              <code>{package_price}</code> ·
              <code>{package_duration}</code> ·
              <code>{package_departure}</code> ·
              <code>{package_part}</code> ·
              <code>{pax}</code> ·
              <code>{city}</code>
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
        </div>
      </section>

      <section class="admin-editor-section" data-editor-section="exchange" hidden>
        <div class="admin-panel">
          <header class="admin-panel-head">
            <h2 class="admin-panel-title">Kurs haji</h2>
            <p class="admin-panel-desc">Ditampilkan di halaman Haji Khusus. Kurs {{ old('haji_exchange_rate_currency', $hajiExchangeRate['currency']) }} → IDR.</p>
          </header>

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

          <label>Nilai kurs <small class="admin-label-hint">IDR per 1 mata uang</small>
            <input type="number" name="haji_exchange_rate" value="{{ old('haji_exchange_rate', $hajiExchangeRate['rate']) }}" min="1" step="1" placeholder="17735">
          </label>

          @if($hajiExchangeRate['updated_at'])
            <p class="admin-inline-note">Terakhir diperbarui: {{ \Carbon\Carbon::parse($hajiExchangeRate['updated_at'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
          @endif
        </div>
      </section>
    </div>

    <footer class="admin-editor-footer">
      <p class="admin-editor-footer-note">Perubahan langsung berlaku di website setelah disimpan.</p>
      <div class="admin-editor-footer-actions">
        <button class="btn" type="submit">Simpan</button>
      </div>
    </footer>
  </form>

  @if(old('haji_exchange_rate_mode', $hajiExchangeRate['mode']) === \App\Support\HajiExchangeRate::MODE_AUTO)
    <div class="admin-panel admin-panel--aside">
      <header class="admin-panel-head">
        <h2 class="admin-panel-title">Perbarui kurs manual</h2>
        <p class="admin-panel-desc">Mode otomatis aktif. Tarik kurs terbaru tanpa menunggu pembaruan 6 jam.</p>
      </header>
      <form method="post" action="{{ route('admin.settings.refresh-exchange-rate') }}">
        @csrf
        <button class="btn gray" type="submit">Perbarui kurs sekarang</button>
      </form>
    </div>
  @endif
</div>
@endsection

@push('scripts')
  @include('admin.partials.editor-tabs-script')
@endpush
