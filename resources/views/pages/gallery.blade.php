@extends('layouts.app')

@section('title', 'Gallery')
@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Gallery</p>
    <h1>Jejak keberangkatan</h1>
    <p>Dokumentasi manasik, bandara, dan ibadah jamaah — dipisah umroh dan haji.</p>
  </div>
</section>
<section class="wrap section gallery-page-wrap">
@php
  $activeCategory = $activeCategory ?? \App\Models\GalleryItem::CATEGORY_UMROH;
  if (! array_key_exists($activeCategory, $categories)) {
      $activeCategory = \App\Models\GalleryItem::CATEGORY_UMROH;
  }
@endphp
<div class="gallery-tabs">
  @foreach($categories as $key => $label)
    <a class="{{ $activeCategory === $key ? 'on' : '' }}" href="{{ route('gallery', ['kategori' => $key]) }}">{{ $label }}</a>
  @endforeach
</div>

@php $groups = $grouped[$activeCategory] ?? collect(); @endphp

@if($groups->isEmpty())
  <p class="empty">Belum ada foto {{ strtolower($categories[$activeCategory]) }}.</p>
@else
  @foreach($groups as $groupName => $items)
    <div class="gallery-group">
      <h2>{{ $groupName }}</h2>
      <div class="gallery-home gallery-page gallery-zoom-grid">
        @foreach($items as $item)
          @include('partials.gallery-item', ['item' => $item])
        @endforeach
      </div>
    </div>
  @endforeach
@endif

@include('partials.gallery-lightbox')
</section>
@endsection
