@extends('layouts.app')

@section('title', 'Testimoni')
@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Testimoni</p>
    <h1>Kata jamaah yang sudah berangkat</h1>
    <p>Cerita singkat dari yang sudah berangkat.</p>
  </div>
</section>
<section class="wrap section">
  <div class="quote-grid">
    @forelse($testimonials as $item)
      <blockquote>
        <p>“{{ $item->quote }}”</p>
        <cite>{{ $item->name }}@if($item->city), {{ $item->city }}@endif @if($item->package_title) · {{ $item->package_title }}@endif</cite>
      </blockquote>
    @empty
      <p class="empty">Belum ada testimoni.</p>
    @endforelse
  </div>
</section>
@endsection
