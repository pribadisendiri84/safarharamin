@extends('layouts.admin')

@section('title', 'Tambah pengajuan')
@section('content')
<div class="page-head">
  <div>
    <h1>Tambah pengajuan</h1>
    <p class="sub">{{ auth()->user()->isStaff() ? 'Catat jamaah yang Anda follow-up.' : 'Input jamaah dari follow-up tim. PIC wajib diisi.' }}</p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ route('admin.inquiries.index') }}">Kembali</a>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" action="{{ route('admin.inquiries.store') }}">
  @csrf
  <label>Jenis
    <select name="kind" required>
      <option value="daftar" @selected(old('kind', 'daftar') === 'daftar')>Pendaftaran</option>
      <option value="tanya" @selected(old('kind') === 'tanya')>Tanya paket</option>
    </select>
  </label>
  @if(auth()->user()->canSeeLeadSources())
  <label>PIC
    <select name="pic_id" class="js-searchable" data-placeholder="Pilih PIC…" required>
      @foreach($pics as $pic)
        <option value="{{ $pic->id }}" @selected((string) old('pic_id', auth()->id()) === (string) $pic->id)>{{ $pic->name }}</option>
      @endforeach
    </select>
  </label>
  @else
    <input type="hidden" name="pic_id" value="{{ auth()->id() }}">
  @endif
  <div class="row2">
    <label>Nama jamaah<input name="name" value="{{ old('name') }}" required></label>
    <label>WhatsApp<input name="phone" value="{{ old('phone') }}" required></label>
  </div>
  <label>Email<input type="email" name="email" value="{{ old('email') }}"></label>
  <label>Kota asal
    @include('partials.city-select', [
      'name' => 'city',
      'empty' => 'Pilih kota',
      'placeholder' => 'Cari kota…',
    ])
  </label>
  <label>Paket
    <select name="package_id" class="js-searchable" data-placeholder="Cari paket…">
      <option value="">Belum tentukan</option>
      @foreach($packages as $package)
        <option value="{{ $package->id }}" @selected((string) old('package_id') === (string) $package->id)>
          {{ $package->title }} — Rp {{ number_format($package->price, 0, ',', '.') }}
        </option>
      @endforeach
    </select>
  </label>
  <label>Jumlah jamaah<input type="number" name="pax" value="{{ old('pax', 1) }}" min="1" max="20" required></label>
  <label>Catatan<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
  <button class="btn" type="submit">Simpan pengajuan</button>
</form>
@endsection
