@extends('layouts.app')

@section('title', 'Gallery')
@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Gallery</p>
    <h1>Jejak keberangkatan</h1>
    <p>Dokumentasi manasik, bandara, dan ibadah jamaah.</p>
  </div>
</section>
<section class="wrap section">
  <div class="gallery-home gallery-page">
    @forelse($items as $item)
      <figure>
        <img src="{{ $item->image }}" alt="{{ $item->title }}" loading="lazy">
        <figcaption>{{ $item->title }}@if($item->caption) — {{ $item->caption }}@endif</figcaption>
      </figure>
    @empty
      <p class="empty">Gallery masih kosong.</p>
    @endforelse
  </div>
</section>
@endsection
