@extends('layouts.admin')

@php
  $isEdit = isset($inquiry) && $inquiry->exists;
@endphp

@section('title', $isEdit ? 'Edit pengajuan' : 'Tambah pengajuan')
@section('content')
<div class="page-head">
  <div>
    <h1>{{ $isEdit ? 'Edit pengajuan' : 'Tambah pengajuan' }}</h1>
    <p class="sub">
      @if($isEdit)
        Perbarui data jamaah. Status dan closing diatur di halaman follow-up.
      @elseif(auth()->user()->isStaff())
        Catat jamaah yang Anda follow-up.
      @else
        Input jamaah dari follow-up tim. PIC wajib diisi.
      @endif
    </p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ $isEdit ? route('admin.inquiries.show', $inquiry) : route('admin.inquiries.index') }}">Kembali</a>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" action="{{ $isEdit ? route('admin.inquiries.edit.update', $inquiry) : route('admin.inquiries.store') }}">
  @csrf
  @if($isEdit) @method('PUT') @endif
  <label>Jenis
    <select name="kind" required>
      <option value="daftar" @selected(old('kind', $isEdit ? $inquiry->kind : 'daftar') === 'daftar')>Pendaftaran</option>
      <option value="tanya" @selected(old('kind', $isEdit ? $inquiry->kind : '') === 'tanya')>Tanya paket</option>
    </select>
  </label>
  @if(auth()->user()->canSeeLeadSources())
  <label>PIC
    <select name="pic_id" class="js-searchable" data-placeholder="Pilih PIC…" @unless($isEdit) required @endunless>
      <option value="">Belum ada PIC</option>
      @foreach($pics as $pic)
        <option value="{{ $pic->id }}" @selected((string) old('pic_id', $isEdit ? $inquiry->pic_id : auth()->id()) === (string) $pic->id)>{{ $pic->name }}</option>
      @endforeach
    </select>
  </label>
  @elseif(! $isEdit)
    <input type="hidden" name="pic_id" value="{{ auth()->id() }}">
  @endif
  <div class="row2">
    <label>Nama jamaah<input name="name" value="{{ old('name', $isEdit ? $inquiry->name : '') }}" required></label>
    <label>WhatsApp<input name="phone" value="{{ old('phone', $isEdit ? $inquiry->phone : '') }}" required></label>
  </div>
  <label>Email<input type="email" name="email" value="{{ old('email', $isEdit ? $inquiry->email : '') }}"></label>
  <label>Kota asal
    @include('partials.city-select', [
      'name' => 'city',
      'selected' => old('city', $isEdit ? $inquiry->city : ''),
      'empty' => 'Pilih kota',
      'placeholder' => 'Cari kota…',
    ])
  </label>
  <label>Paket
    <select name="package_id" class="js-searchable" data-placeholder="Cari paket…">
      <option value="">Belum tentukan</option>
      @foreach($packages as $package)
        <option value="{{ $package->id }}" @selected((string) old('package_id', $isEdit ? $inquiry->package_id : '') === (string) $package->id)>
          {{ $package->title }} — {{ $package->formattedStartingPrice() }}
        </option>
      @endforeach
    </select>
  </label>
  <label>Jumlah jamaah<input type="number" name="pax" value="{{ old('pax', $isEdit ? $inquiry->pax : 1) }}" min="1" max="20" required></label>
  <label>Catatan<textarea name="notes" rows="3">{{ old('notes', $isEdit ? $inquiry->notes : '') }}</textarea></label>
  <button class="btn" type="submit">{{ $isEdit ? 'Simpan perubahan' : 'Simpan pengajuan' }}</button>
</form>
@endsection
