@extends('layouts.admin')

@section('title', 'Import paket CSV')
@section('content')
<div class="page-head">
  <div>
    <h1>Import paket CSV</h1>
    <p class="sub">Semua field diisi lewat CSV. Flyer di-upload manual setelah import (filter <strong>Belum lengkap</strong>).</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.packages.import.template') }}">@include('admin.partials.icon', ['name' => 'upload']) Unduh contoh CSV</a>
    <a class="btn ghost" href="{{ route('admin.packages.index') }}">Kembali</a>
  </div>
</div>

<div class="panel form-pad">
  <p class="sub"><strong>Kolom wajib:</strong> judul, jenis, tipe_paket, embarkasi, tanggal, durasi, harga_quad, harga_triple, harga_double, seat_total, seat_sisa.</p>
  <p class="sub"><strong>Opsional:</strong> maskapai, hotel_makkah, hotel_madinah, hotel_makkah_setaraf, hotel_madinah_setaraf, catatan_harga, fasilitas, exclude, deskripsi, itinerary, status, unggulan, kuota_terbatas.</p>
  <p class="sub">Jenis: <code>umroh</code>, <code>umroh_plus</code>, <code>haji_plus</code>. Tipe paket: Arafah, Mina, atau Muzdalifah. Setaraf: <code>1</code>/<code>ya</code> jika hotel boleh diganti yang sekelas. Bintang hotel diambil dari master hotel, bukan dari CSV.</p>
  <p class="sub">Fasilitas &amp; exclude: pisah dengan <code>|</code> (pipe). Embarkasi = slug kota, mis. <code>jakarta</code>.</p>
</div>

<form class="form panel form-pad form-narrow" method="post" enctype="multipart/form-data" action="{{ route('admin.packages.import.store') }}">
  @csrf
  <label>File CSV<input type="file" name="csv" accept=".csv,text/csv" required></label>
  <button class="btn" type="submit">Import</button>
</form>
@endsection
