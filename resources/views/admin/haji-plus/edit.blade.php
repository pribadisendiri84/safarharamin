@extends('layouts.admin')

@section('title', 'Halaman Haji Plus')
@section('content')
<div class="admin-editor haji-editor">
  <div class="page-head">
    <div>
      <h1>Halaman Haji Plus</h1>
      <p class="sub">Atur konten <a href="{{ route('haji') }}" target="_blank" rel="noopener">/haji-khusus</a> — hero, kamar, hotel, maskapai, dan CTA.</p>
    </div>
    <div class="head-actions">
      <a class="btn ghost" href="{{ route('haji') }}" target="_blank" rel="noopener">Pratinjau</a>
    </div>
  </div>

  <form class="form admin-editor-form haji-admin-form" method="post" enctype="multipart/form-data" action="{{ route('admin.haji-plus.update') }}">
    @csrf
    @method('PUT')

    <nav class="admin-editor-nav haji-editor-nav" aria-label="Bagian halaman" data-editor-tabs>
      <button type="button" class="is-active" data-editor-tab="hero">Hero</button>
      <button type="button" data-editor-tab="rooms">Kartu kamar</button>
      <button type="button" data-editor-tab="benefits">Manfaat</button>
      <button type="button" data-editor-tab="travel">Hotel &amp; maskapai</button>
      <button type="button" data-editor-tab="flow">Alur daftar</button>
      <button type="button" data-editor-tab="cta">CTA</button>
    </nav>

    <div class="admin-editor-body haji-editor-body">
      <section class="admin-editor-section haji-section is-active" data-editor-section="hero">
        <div class="admin-panel haji-panel">
          <header class="admin-panel-head haji-panel-head">
            <h2 class="admin-panel-title haji-panel-title">Hero</h2>
            <p class="admin-panel-desc haji-panel-desc">Judul, harga mulai, dan foto bagian atas halaman.</p>
          </header>
          <div class="row2">
            <label>Badge
              <input name="hero[badge]" value="{{ old('hero.badge', $page['hero']['badge']) }}" required>
            </label>
            <label>Musim
              <input name="hero[season]" value="{{ old('hero.season', $page['hero']['season']) }}" required>
            </label>
          </div>
          <label>Judul
            <input name="hero[title]" value="{{ old('hero.title', $page['hero']['title']) }}" required>
          </label>
          <label>Deskripsi singkat
            <textarea name="hero[subtitle]" rows="2" required>{{ old('hero.subtitle', $page['hero']['subtitle']) }}</textarea>
          </label>
          <div class="row2">
            <label>Harga mulai <small class="haji-label-hint">kosongkan = pakai kamar pertama</small>
              <input name="hero[starting_price]" value="{{ old('hero.starting_price', $page['hero']['starting_price']) }}" placeholder="Rp 275 Jt">
            </label>
            <label class="haji-file-pick">
              <span>Foto latar</span>
              <input type="file" name="hero[image_file]" accept="image/*">
            </label>
          </div>
          <label class="check">
            <input type="checkbox" name="hero[show_quota]" value="1" @checked(old('hero.show_quota', $page['hero']['show_quota']) === '1')>
            Tampilkan badge “Kuota terbatas”
          </label>
          @if(!empty($page['hero']['image']))
            <img class="haji-media-preview" src="{{ $page['hero']['image'] }}" alt="Hero">
          @endif
        </div>
      </section>

      <section class="admin-editor-section haji-section" data-editor-section="rooms" hidden>
        <div class="admin-panel haji-panel">
          <header class="admin-panel-head haji-panel-head">
            <h2 class="admin-panel-title haji-panel-title">Kartu kamar</h2>
            <p class="admin-panel-desc haji-panel-desc">Empat tipe kamar dalam satu program — bukan empat paket terpisah.</p>
          </header>
          <div class="haji-room-grid">
            @foreach($page['rooms'] as $index => $room)
              @include('admin.haji-plus.partials.room-card', compact('index', 'room'))
            @endforeach
          </div>
        </div>
      </section>

      <section class="haji-section" data-editor-section="benefits" hidden>
        <div class="haji-panel">
          <header class="haji-panel-head">
            <h2 class="haji-panel-title">Manfaat program</h2>
            <p class="haji-panel-desc">Ikon Bootstrap, contoh: <code>bi-building</code>, <code>bi-airplane</code>.</p>
          </header>
          <div class="haji-benefit-list">
            @foreach($page['benefits'] as $index => $benefit)
              <div class="haji-benefit-row">
                <label>Ikon
                  <input name="benefits[{{ $index }}][icon]" value="{{ old('benefits.'.$index.'.icon', $benefit['icon']) }}" required placeholder="bi-building">
                </label>
                <label>Judul
                  <input name="benefits[{{ $index }}][title]" value="{{ old('benefits.'.$index.'.title', $benefit['title']) }}" required>
                </label>
                <label class="haji-benefit-desc">Deskripsi
                  <input name="benefits[{{ $index }}][description]" value="{{ old('benefits.'.$index.'.description', $benefit['description']) }}" required>
                </label>
              </div>
            @endforeach
          </div>
        </div>
      </section>

      <section class="haji-section" data-editor-section="travel" hidden>
        <div class="haji-panel">
          <header class="haji-panel-head">
            <h2 class="haji-panel-title">Hotel &amp; maskapai</h2>
            <p class="haji-panel-desc">Data dari <a href="{{ route('admin.hotels.index') }}">master hotel</a> &amp; <a href="{{ route('admin.airlines.index') }}">maskapai</a>.</p>
          </header>

          <div class="haji-subblock">
            <h3 class="haji-subblock-title">Kartu hotel</h3>
            <div id="haji-hotels-list" class="haji-hotel-admin-list">
              @foreach($page['hotels'] as $index => $hotel)
                @include('admin.haji-plus.partials.hotel-card', compact('index', 'hotel', 'hotelLocations'))
              @endforeach
            </div>
            <button class="btn gray compact" type="button" id="haji-hotel-add">+ Tambah hotel</button>
          </div>

          <div class="haji-subblock">
            <h3 class="haji-subblock-title">Maskapai</h3>
            @if($airlines->isEmpty())
              <p class="haji-panel-desc">Belum ada maskapai di master.</p>
            @else
              <div class="haji-airline-grid">
                @foreach($airlines as $airline)
                  <label class="haji-airline-chip">
                    <input
                      type="checkbox"
                      name="partner_airlines[]"
                      value="{{ $airline->name }}"
                      @checked(in_array($airline->name, old('partner_airlines', $page['partner_airlines']), true))
                    >
                    <span class="haji-airline-chip-body">
                      @if($airline->logo)
                        <img src="{{ $airline->logo }}" alt="" class="haji-airline-chip-logo">
                      @endif
                      <span>{{ $airline->name }}</span>
                    </span>
                  </label>
                @endforeach
              </div>
            @endif
            @if(!empty($page['partner_airlines']))
              <p class="haji-inline-note">Judul penerbangan: <strong>{{ implode(' / ', $page['partner_airlines']) }}</strong></p>
            @endif
          </div>

          <div class="haji-subblock">
            <h3 class="haji-subblock-title">Teks penerbangan</h3>
            <label>Deskripsi
              <textarea name="airline[description]" rows="2" required>{{ old('airline.description', $page['airline']['description']) }}</textarea>
            </label>
            <label>Poin layanan <small class="haji-label-hint">satu baris = satu item</small>
              <textarea name="airline[points_text]" rows="3" required>{{ old('airline.points_text', $page['airline']['points_text']) }}</textarea>
            </label>
          </div>
        </div>
      </section>

      <section class="haji-section" data-editor-section="flow" hidden>
        <div class="haji-panel">
          <header class="haji-panel-head">
            <h2 class="haji-panel-title">Langkah pendaftaran</h2>
            <p class="haji-panel-desc">Empat langkah alur dari konsultasi hingga berangkat.</p>
          </header>
          <div class="haji-flow-grid">
            @foreach($page['flow'] as $index => $step)
              <div class="haji-flow-card">
                <span class="haji-flow-step">Langkah {{ $index + 1 }}</span>
                <div class="row2">
                  <label>Judul
                    <input name="flow[{{ $index }}][title]" value="{{ old('flow.'.$index.'.title', $step['title']) }}" required>
                  </label>
                  <label>Ikon
                    <input name="flow[{{ $index }}][icon]" value="{{ old('flow.'.$index.'.icon', $step['icon']) }}" required placeholder="bi-chat-dots">
                  </label>
                </div>
                <label>Deskripsi
                  <input name="flow[{{ $index }}][description]" value="{{ old('flow.'.$index.'.description', $step['description']) }}" required>
                </label>
              </div>
            @endforeach
          </div>
        </div>
      </section>

      <section class="haji-section" data-editor-section="cta" hidden>
        <div class="haji-panel">
          <header class="haji-panel-head">
            <h2 class="haji-panel-title">CTA bawah</h2>
            <p class="haji-panel-desc">Ajakan daftar &amp; konsultasi di bagian akhir halaman.</p>
          </header>
          <label>Judul
            <input name="cta[title]" value="{{ old('cta.title', $page['cta']['title']) }}" required>
          </label>
          <label>Deskripsi
            <textarea name="cta[description]" rows="2" required>{{ old('cta.description', $page['cta']['description']) }}</textarea>
          </label>
          <label>Catatan kecil
            <input name="cta[note]" value="{{ old('cta.note', $page['cta']['note']) }}" required>
          </label>
        </div>
      </section>
    </div>

    <footer class="admin-editor-footer haji-editor-footer">
      <p class="admin-editor-footer-note haji-editor-footer-note">Perubahan langsung tampil di halaman publik setelah disimpan.</p>
      <div class="admin-editor-footer-actions haji-editor-footer-actions">
        <a class="btn ghost" href="{{ route('haji') }}" target="_blank" rel="noopener">Lihat halaman</a>
        <button class="btn" type="submit">Simpan</button>
      </div>
    </footer>
  </form>
</div>

<template id="haji-hotel-template">
  @include('admin.haji-plus.partials.hotel-card', [
    'index' => '__INDEX__',
    'hotel' => \App\Support\HajiPlusPage::blankHotel(),
    'hotelLocations' => $hotelLocations,
  ])
</template>
@endsection

@push('scripts')
@include('admin.partials.editor-tabs-script')
<script type="application/json" id="haji-hotel-options">@json($hotelOptionsByLocation)</script>
<script>
(function () {
  var list = document.getElementById('haji-hotels-list');
  var template = document.getElementById('haji-hotel-template');
  var addBtn = document.getElementById('haji-hotel-add');
  var optionsNode = document.getElementById('haji-hotel-options');
  var hotelOptions = optionsNode ? JSON.parse(optionsNode.textContent || '{}') : {};
  if (!list || !template || !addBtn) return;

  function replaceHotelOptions(select, location, selected) {
    if (!select) return;
    var options = hotelOptions[location] || {};
    var html = '<option value="">Pilih hotel…</option>';
    Object.keys(options).forEach(function (value) {
      html += '<option value="' + value.replace(/"/g, '&quot;') + '"' + (selected === value ? ' selected' : '') + '>' + options[value] + '</option>';
    });
    if (select.tomselect) {
      select.tomselect.destroy();
      select.tomselect = null;
    }
    select.innerHTML = html;
    if (window.initSearchableSelects) window.initSearchableSelects(select.parentElement || select);
  }

  function reindexCards() {
    var cards = list.querySelectorAll('[data-hotel-card]');
    cards.forEach(function (card, index) {
      card.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/hotels\[(\d+|__INDEX__)\]/, 'hotels[' + index + ']');
      });
      card.querySelectorAll('[data-hotel-num], [data-hotel-num-label]').forEach(function (el) {
        el.textContent = String(index + 1);
      });
      card.querySelectorAll('[data-hotel-remove]').forEach(function (btn) {
        btn.hidden = cards.length <= 1;
      });
    });
  }

  function bindCard(card) {
    var locationSelect = card.querySelector('[data-hotel-location]');
    var hotelSelect = card.querySelector('select[name*="[master_name]"]');
    if (locationSelect && hotelSelect) {
      locationSelect.addEventListener('change', function () {
        replaceHotelOptions(hotelSelect, locationSelect.value, '');
      });
    }

    var removeBtn = card.querySelector('[data-hotel-remove]');
    if (removeBtn) {
      removeBtn.addEventListener('click', function () {
        if (list.querySelectorAll('[data-hotel-card]').length <= 1) return;
        card.remove();
        reindexCards();
      });
    }
  }

  addBtn.addEventListener('click', function () {
    if (list.querySelectorAll('[data-hotel-card]').length >= 6) return;
    var index = list.querySelectorAll('[data-hotel-card]').length;
    var html = template.innerHTML.replace(/__INDEX__/g, String(index));
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var card = wrap.firstElementChild;
    list.appendChild(card);
    bindCard(card);
    reindexCards();
    if (window.initSearchableSelects) window.initSearchableSelects(card);
  });

  list.querySelectorAll('[data-hotel-card]').forEach(bindCard);
  reindexCards();
})();
</script>
@endpush
