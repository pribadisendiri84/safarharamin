@extends('layouts.app')

@section('title', 'Haji Khusus Arminareka | Haji Plus')
@section('meta', ($page['hero']['title'] ?? 'Haji Khusus Arminareka').' — pilihan kamar Quad, Triple, Double, dan Double Plus dengan hotel strategis dan pembimbing berpengalaman.')

@section('content')
@php
  use App\Support\CompanyProfile;
  use App\Support\HajiPlusPage;
  use App\Support\HajiPlusProgram;

  $hero = $page['hero'];
  $rooms = $page['rooms'];
  $starting = HajiPlusPage::startingPrice($hero, $rooms);
  $deposit = HajiPlusProgram::initialDeposit();
@endphp

<div class="haji-landing">
  <section class="haji-hero">
    <div class="wrap haji-hero-grid">
      <div class="haji-hero-copy">
        <span class="haji-hero-badge">{{ $hero['badge'] }}@if(!empty($hero['season'])) · {{ $hero['season'] }}@endif</span>
        <h1>{{ $hero['title'] }}</h1>
        <p class="haji-hero-lead">{{ $hero['subtitle'] }}</p>

        <div class="haji-hero-meta">
          <div class="haji-price-pill">
            <span class="haji-price-prefix">{{ $starting['prefix'] }}</span>
            <span class="haji-price-value">{{ $starting['amount'] }}<small>{{ $starting['unit'] }}</small></span>
          </div>
          @if(($hero['show_quota'] ?? '0') === '1')
            <span class="haji-quota-badge"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Kuota terbatas</span>
          @endif
        </div>

        <div class="haji-hero-actions">
          <a class="btn haji-btn-consult" href="{{ HajiPlusProgram::whatsAppUrl() }}" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> Konsultasi
          </a>
          <a class="haji-hero-detail-link" href="#detail-program">Lihat detail program</a>
        </div>
      </div>

      <aside class="haji-hero-panel">
        @if(!empty($hajiExchangeRate))
          @include('partials.haji-exchange-rate', ['hajiExchangeRate' => $hajiExchangeRate, 'compact' => true])
        @endif
        <div class="haji-hero-panel-partners">
          <span class="haji-hero-panel-label">Didukung maskapai terbaik</span>
          <div class="haji-partner-logos">
            @foreach(HajiPlusPage::partnerAirlines($page) as $airline)
              @if($airline['logo'])
                <img src="{{ $airline['logo'] }}" alt="{{ $airline['name'] }}" loading="lazy">
              @else
                <span>{{ $airline['name'] }}</span>
              @endif
            @endforeach
          </div>
        </div>
      </aside>
    </div>
  </section>

  <section class="wrap haji-section" id="pilihan-kamar">
    <div class="haji-section-head">
      <span class="haji-section-badge">Pilihan kamar</span>
      <h2>Tipe kamar &amp; harga</h2>
      <p>Satu program Haji Plus — pilih tipe kamar sesuai kebutuhan. Bukan empat paket terpisah.</p>
    </div>

    <div class="haji-room-showcase">
      @foreach($rooms as $room)
        <article class="haji-room-showcase-card{{ ($room['is_featured'] ?? '0') === '1' ? ' is-featured' : '' }}">
          @if(($room['is_featured'] ?? '0') === '1')
            <span class="haji-room-featured-badge">Paling favorit</span>
          @endif
          <div class="haji-room-showcase-image">
            <img src="{{ $room['image'] }}" alt="{{ $room['label'] }}" loading="lazy">
          </div>
          <div class="haji-room-showcase-body">
            <h3>{{ $room['label'] }}</h3>
            <p class="haji-room-showcase-cap"><i class="bi bi-people" aria-hidden="true"></i> {{ $room['occupancy'] }}</p>
            <p class="haji-room-showcase-price">
              <strong>{{ $room['price_label'] }}</strong>
              <span>{{ $room['price_note'] }}</span>
            </p>
          </div>
        </article>
      @endforeach
    </div>
  </section>

  <section class="haji-benefits-band">
    <div class="wrap haji-benefits-grid">
      @foreach($page['benefits'] as $benefit)
        <article class="haji-benefit-item">
          <span class="haji-benefit-icon" aria-hidden="true"><i class="bi {{ $benefit['icon'] }}"></i></span>
          <b>{{ $benefit['title'] }}</b>
          <p>{{ $benefit['description'] }}</p>
        </article>
      @endforeach
    </div>
  </section>

  <section class="wrap haji-section" id="hotel">
    <div class="haji-section-head">
      <span class="haji-section-badge">Akomodasi</span>
      <h2>Hotel strategis di Madinah &amp; Makkah</h2>
    </div>
    <div class="haji-hotel-grid">
      @foreach($page['hotels'] as $hotel)
        <article class="haji-hotel-card">
          <div class="haji-hotel-image">
            <img src="{{ $hotel['image'] }}" alt="{{ $hotel['title'] }}" loading="lazy">
            <span class="haji-hotel-badge">{{ $hotel['badge'] }}</span>
          </div>
          <div class="haji-hotel-body">
            <span class="haji-hotel-city">{{ $hotel['city'] }}</span>
            <h3>{{ $hotel['title'] }}</h3>
            <p class="haji-hotel-distance">{{ $hotel['distance'] }}</p>
            <ul class="haji-hotel-features">
              @foreach(HajiPlusPage::lines($hotel['features_text'] ?? '') as $feature)
                <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ $feature }}</li>
              @endforeach
            </ul>
          </div>
        </article>
      @endforeach
    </div>
  </section>

  <section class="wrap haji-section haji-airline-section" id="penerbangan">
    <div class="haji-airline-grid">
      <div class="haji-airline-visual">
        <img src="{{ $page['airline']['image'] }}" alt="{{ $page['airline']['title'] }}" loading="lazy">
      </div>
      <div class="haji-airline-copy">
        <span class="haji-section-badge">Penerbangan</span>
        <h2>{{ $page['airline']['title'] }}</h2>
        @php $airlinePartners = HajiPlusPage::partnerAirlines($page); @endphp
        @if($airlinePartners !== [])
          <div class="haji-airline-logos">
            @foreach($airlinePartners as $airline)
              @if($airline['logo'])
                <img src="{{ $airline['logo'] }}" alt="{{ $airline['name'] }}" loading="lazy">
              @else
                <span>{{ $airline['name'] }}</span>
              @endif
            @endforeach
          </div>
        @endif
        <p>{{ $page['airline']['description'] }}</p>
        <ul class="checks haji-checks">
          @foreach(HajiPlusPage::lines($page['airline']['points_text'] ?? '') as $point)
            <li>{{ $point }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </section>

  <section class="wrap haji-section" id="pendaftaran">
    <div class="haji-section-head haji-section-head-center">
      <span class="haji-section-badge">Daftar Haji Plus</span>
      <h2>Proses pendaftaran mudah</h2>
      <p>{{ HajiPlusProgram::porsiProcessingEstimate() }}</p>
    </div>
    <div class="haji-flow">
      @foreach($page['flow'] as $index => $step)
        <article class="haji-flow-step">
          <span class="haji-flow-num">{{ $index + 1 }}</span>
          <b>{{ $step['title'] }}</b>
          <p>{{ $step['description'] }}</p>
        </article>
        @if(! $loop->last)
          <span class="haji-flow-arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
        @endif
      @endforeach
    </div>
    <div class="haji-flow-cta">
      <a class="btn light haji-btn-register" href="{{ HajiPlusProgram::registerUrl() }}">Daftar</a>
      <a class="btn haji-btn-consult" href="{{ HajiPlusProgram::whatsAppUrl() }}" target="_blank" rel="noopener">
        <i class="bi bi-whatsapp"></i> Konsultasi
      </a>
    </div>
  </section>

  <section class="wrap haji-section haji-detail-section" id="detail-program">
    <div class="haji-section-head">
      <span class="haji-section-badge">Detail program</span>
      <h2>Informasi lengkap Haji Plus</h2>
      <p>Setoran awal: {{ $deposit['summary'] }} ({{ $deposit['dp_idr_note'] }})</p>
    </div>

    <div class="haji-detail-grid">
      <div class="haji-detail-block">
        <h3>Fasilitas</h3>
        <ul class="checks haji-checks">
          @foreach(HajiPlusPage::facilities() as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
      <div class="haji-detail-block">
        <h3>Dokumen</h3>
        <ul class="checks haji-checks">
          @foreach(HajiPlusProgram::documents() as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
      <div class="haji-detail-block">
        <h3>Persyaratan</h3>
        <ul class="checks haji-checks">
          @foreach(HajiPlusProgram::requirements() as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    </div>

    @if($itineraries->isNotEmpty())
      <div class="haji-itinerary-block">
        @include('partials.haji-itinerary-list', ['items' => $itineraries])
        @include('partials.itinerary-pdf-viewer')
      </div>
    @endif

    <div class="haji-faq">
      @foreach(HajiPlusProgram::faq() as $item)
        <details class="haji-faq-item">
          <summary>{{ $item['question'] }}</summary>
          <p>{{ $item['answer'] }}</p>
        </details>
      @endforeach
    </div>

    <p class="haji-legal-note">
      {{ CompanyProfile::LEGAL_NAME }} · PIHK resmi · {{ HajiPlusProgram::CLOSING_LINE }}
    </p>
  </section>

  <section class="haji-final-cta">
    <div class="wrap haji-final-cta-inner">
      <div>
        <h2>{{ $page['cta']['title'] }}</h2>
        <p>{{ $page['cta']['description'] }}</p>
      </div>
      <div class="haji-final-cta-actions">
        <a class="btn light haji-btn-register" href="{{ HajiPlusProgram::registerUrl() }}">Daftar</a>
        <a class="btn haji-btn-consult haji-btn-consult-lg" href="{{ HajiPlusProgram::whatsAppUrl() }}" target="_blank" rel="noopener">
          <i class="bi bi-whatsapp"></i> Konsultasi
        </a>
      </div>
      <p class="haji-final-note">{{ $page['cta']['note'] }}</p>
    </div>
  </section>
</div>
@endsection
