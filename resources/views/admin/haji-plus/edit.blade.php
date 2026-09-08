@extends('layouts.admin')

@section('title', 'Halaman Haji Plus')
@section('content')
<div class="page-head">
  <div>
    <h1>Halaman Haji Plus</h1>
    <p class="sub">Atur kartu dan konten yang tampil di <a href="{{ route('haji') }}" target="_blank" rel="noopener">/haji-khusus</a>. Foto, harga, hotel, dan langkah pendaftaran bisa diubah tanpa sentuh kode.</p>
  </div>
</div>

<form class="form panel form-pad settings-form haji-admin-form" method="post" enctype="multipart/form-data" action="{{ route('admin.haji-plus.update') }}">
  @csrf
  @method('PUT')

  <fieldset>
    <legend>Hero</legend>
    <p class="sub">Bagian atas halaman: judul, harga mulai, dan foto latar.</p>
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
      <label>Harga mulai (opsional)
        <input name="hero[starting_price]" value="{{ old('hero.starting_price', $page['hero']['starting_price']) }}" placeholder="Kosongkan untuk pakai harga kamar pertama">
      </label>
      <label>Foto latar
        <input type="file" name="hero[image_file]" accept="image/*">
      </label>
    </div>
    <label class="check">
      <input type="checkbox" name="hero[show_quota]" value="1" @checked(old('hero.show_quota', $page['hero']['show_quota']) === '1')>
      Tampilkan badge “Kuota terbatas”
    </label>
    @if(!empty($page['hero']['image']))
      <p class="sub"><img class="haji-admin-thumb" src="{{ $page['hero']['image'] }}" alt="Hero"></p>
    @endif
  </fieldset>

  <fieldset>
    <legend>Kartu kamar</legend>
    <p class="sub">Empat tipe kamar dalam satu program Haji Plus. Bukan empat paket terpisah.</p>
    @foreach($page['rooms'] as $index => $room)
      <div class="haji-admin-card">
        <b>{{ $room['label'] }}</b>
        <div class="row2">
          <label>Nama tipe
            <input name="rooms[{{ $index }}][label]" value="{{ old('rooms.'.$index.'.label', $room['label']) }}" required>
          </label>
          <label>Kapasitas
            <input name="rooms[{{ $index }}][occupancy]" value="{{ old('rooms.'.$index.'.occupancy', $room['occupancy']) }}" required>
          </label>
        </div>
        <div class="row2">
          <label>Harga
            <input name="rooms[{{ $index }}][price_label]" value="{{ old('rooms.'.$index.'.price_label', $room['price_label']) }}" required>
          </label>
          <label>Satuan
            <input name="rooms[{{ $index }}][price_note]" value="{{ old('rooms.'.$index.'.price_note', $room['price_note']) }}" required>
          </label>
        </div>
        <label>Foto kamar
          <input type="file" name="rooms[{{ $index }}][image_file]" accept="image/*">
        </label>
        <label class="check">
          <input type="checkbox" name="rooms[{{ $index }}][is_featured]" value="1" @checked(old('rooms.'.$index.'.is_featured', $room['is_featured']) === '1')>
          Badge “Paling favorit”
        </label>
        @if(!empty($room['image']))
          <p class="sub"><img class="haji-admin-thumb" src="{{ $room['image'] }}" alt="{{ $room['label'] }}"></p>
        @endif
      </div>
    @endforeach
  </fieldset>

  <fieldset>
    <legend>Manfaat program</legend>
    @foreach($page['benefits'] as $index => $benefit)
      <div class="haji-admin-card">
        <div class="row2">
          <label>Judul
            <input name="benefits[{{ $index }}][title]" value="{{ old('benefits.'.$index.'.title', $benefit['title']) }}" required>
          </label>
          <label>Ikon Bootstrap
            <input name="benefits[{{ $index }}][icon]" value="{{ old('benefits.'.$index.'.icon', $benefit['icon']) }}" required>
          </label>
        </div>
        <label>Deskripsi
          <input name="benefits[{{ $index }}][description]" value="{{ old('benefits.'.$index.'.description', $benefit['description']) }}" required>
        </label>
      </div>
    @endforeach
    <p class="sub">Contoh ikon: <code>bi-building</code>, <code>bi-airplane</code>, <code>bi-person-badge</code>.</p>
  </fieldset>

  <fieldset>
    <legend>Kartu hotel</legend>
    <p class="sub">Tambah hotel dari master atau atur khusus untuk halaman Haji. Jarak, badge, dan fasilitas bisa disesuaikan per program.</p>
    <div id="haji-hotels-list">
      @foreach($page['hotels'] as $index => $hotel)
        @include('admin.haji-plus.partials.hotel-card', compact('index', 'hotel', 'hotelLocations'))
      @endforeach
    </div>
    <button class="btn gray" type="button" id="haji-hotel-add">+ Tambah hotel</button>
  </fieldset>

  <fieldset>
    <legend>Maskapai</legend>
    <p class="sub">Pilih maskapai dari master. Logo tampil di hero dan bagian penerbangan. Kelola master di menu Maskapai.</p>
    @if($airlines->isEmpty())
      <p class="sub">Belum ada maskapai di master. <a href="{{ route('admin.airlines.index') }}">Tambah maskapai</a> dulu.</p>
    @else
      <div class="haji-airline-picks">
        @foreach($airlines as $airline)
          <label class="check haji-airline-pick">
            <input
              type="checkbox"
              name="partner_airlines[]"
              value="{{ $airline->name }}"
              @checked(in_array($airline->name, old('partner_airlines', $page['partner_airlines']), true))
            >
            @if($airline->logo)
              <img src="{{ $airline->logo }}" alt="" class="haji-airline-pick-logo">
            @endif
            {{ $airline->name }}
          </label>
        @endforeach
      </div>
    @endif
  </fieldset>

  <fieldset>
    <legend>Penerbangan</legend>
    <label>Judul maskapai
      <input name="airline[title]" value="{{ old('airline.title', $page['airline']['title']) }}" required>
    </label>
    <label>Deskripsi
      <textarea name="airline[description]" rows="2" required>{{ old('airline.description', $page['airline']['description']) }}</textarea>
    </label>
    <label>Poin layanan (satu baris satu item)
      <textarea name="airline[points_text]" rows="3" required>{{ old('airline.points_text', $page['airline']['points_text']) }}</textarea>
    </label>
    <label>Foto pesawat
      <input type="file" name="airline[image_file]" accept="image/*">
    </label>
    @if(!empty($page['airline']['image']))
      <p class="sub"><img class="haji-admin-thumb" src="{{ $page['airline']['image'] }}" alt="Penerbangan"></p>
    @endif
  </fieldset>

  <fieldset>
    <legend>Langkah pendaftaran</legend>
    @foreach($page['flow'] as $index => $step)
      <div class="haji-admin-card">
        <b>Langkah {{ $index + 1 }}</b>
        <div class="row2">
          <label>Judul
            <input name="flow[{{ $index }}][title]" value="{{ old('flow.'.$index.'.title', $step['title']) }}" required>
          </label>
          <label>Ikon
            <input name="flow[{{ $index }}][icon]" value="{{ old('flow.'.$index.'.icon', $step['icon']) }}" required>
          </label>
        </div>
        <label>Deskripsi
          <input name="flow[{{ $index }}][description]" value="{{ old('flow.'.$index.'.description', $step['description']) }}" required>
        </label>
      </div>
    @endforeach
  </fieldset>

  <fieldset>
    <legend>CTA bawah</legend>
    <label>Judul
      <input name="cta[title]" value="{{ old('cta.title', $page['cta']['title']) }}" required>
    </label>
    <label>Deskripsi
      <textarea name="cta[description]" rows="2" required>{{ old('cta.description', $page['cta']['description']) }}</textarea>
    </label>
    <label>Catatan kecil
      <input name="cta[note]" value="{{ old('cta.note', $page['cta']['note']) }}" required>
    </label>
  </fieldset>

  <div class="form-actions">
    <button class="btn" type="submit">Simpan halaman</button>
    <a class="btn gray" href="{{ route('haji') }}" target="_blank" rel="noopener">Lihat halaman</a>
  </div>
</form>

<template id="haji-hotel-template">
  @include('admin.haji-plus.partials.hotel-card', [
    'index' => '__INDEX__',
    'hotel' => \App\Support\HajiPlusPage::blankHotel(),
    'hotelLocations' => $hotelLocations,
  ])
</template>
@endsection

@push('scripts')
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
    list.querySelectorAll('[data-hotel-card]').forEach(function (card, index) {
      card.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/hotels\[(\d+|__INDEX__)\]/, 'hotels[' + index + ']');
      });
      var num = card.querySelector('[data-hotel-num]');
      if (num) num.textContent = String(index + 1);
      card.querySelectorAll('[data-hotel-remove]').forEach(function (btn) {
        btn.hidden = list.querySelectorAll('[data-hotel-card]').length <= 1;
      });
    });
  }

  function bindCard(card) {
    card.querySelectorAll('[data-hotel-source]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        var master = card.querySelector('[data-hotel-master]');
        if (master) master.hidden = radio.value !== 'master';
      });
    });

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
