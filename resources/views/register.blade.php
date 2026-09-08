@extends('layouts.app')

@section('title', 'Daftar sekarang')
@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Pendaftaran@if(!empty($isHajiProgram)) Haji Plus@endif</p>
    <h1>Daftar sekarang</h1>
    <p>@if(!empty($isHajiProgram))
      Isi data jamaah program Haji Plus. Tim kami konfirmasi seat dan dokumen via WhatsApp.
    @else
      Isi data jamaah. Tim kami konfirmasi seat dan dokumen via WhatsApp.
    @endif</p>
  </div>
</section>
<section class="wrap split">
  <div class="prose">
    <h2>Yang perlu disiapkan</h2>
    <ul class="checks">
      <li>Paspor masih berlaku minimal 6 bulan</li>
      <li>Vaksin meningitis (jika diminta musim itu)</li>
      <li>DP untuk mengunci seat</li>
      <li>Nama sesuai KTP untuk manifes</li>
    </ul>
  </div>
  @if(session('registration_success'))
    <div class="form registration-success">
      <div class="registration-success__icon" aria-hidden="true">✓</div>
      <h2>Pendaftaran berhasil</h2>
      <p>{{ session('ok') }}</p>
      <p>Silakan lanjutkan ke WhatsApp agar tim kami dapat segera mengonfirmasi data, seat, dan dokumen Anda.</p>
      @if(session('wa_url'))
        <a class="btn full" href="{{ session('wa_url') }}" target="_blank" rel="noopener">
          <i class="bi bi-whatsapp"></i> Lanjut ke WhatsApp
        </a>
      @endif
    </div>
  @else
  <form class="form" method="post" action="{{ route('register.store') }}">
    @csrf
    @if(!empty($isHajiProgram))
      <input type="hidden" name="program_kind" value="haji">
    @endif
    <label>Nama lengkap<input name="name" value="{{ old('name') }}" required></label>
    <label>WhatsApp<input name="phone" value="{{ old('phone') }}" required></label>
    <label>Email<input type="email" name="email" value="{{ old('email') }}"></label>
    <label>Kota asal
      @include('partials.city-select', [
        'name' => 'city',
        'empty' => 'Pilih kota',
        'required' => true,
        'placeholder' => 'Cari kota asal…',
      ])
    </label>
    @if(empty($isHajiProgram))
    <label>Paket
      <select name="package_id">
        <option value="">Belum tentukan</option>
        @foreach($packages as $package)
          <option value="{{ $package->id }}" @selected((string) old('package_id', $selectedPackageId ?? request('package_id')) === (string) $package->id)>{{ $package->title }} — {{ $package->formattedStartingPrice() }}</option>
        @endforeach
      </select>
    </label>
    @endif
    <label>Jumlah jamaah<input type="number" name="pax" value="{{ old('pax', 1) }}" min="1" max="20" required></label>
    <label>Catatan<textarea name="notes" rows="3">{{ old('notes', $defaultNotes ?? '') }}</textarea></label>
    <button class="btn" type="submit">Kirim pendaftaran</button>
  </form>
  @endif
</section>
@endsection
