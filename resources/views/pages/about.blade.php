@extends('layouts.app')

@section('title', 'About Arminareka | PT Arminareka Perdana')
@section('meta', 'Mengenal Arminareka dan PT Arminareka Perdana, penyelenggara perjalanan ibadah Umrah dan Haji Khusus dengan pengalaman melayani jamaah sejak 1990.')

@section('content')
@php
  $company = \App\Support\CompanyProfile::class;
  $legalName = $company::LEGAL_NAME;
  $brandName = $company::BRAND_NAME;
@endphp

<section class="about-hero">
  <div class="wrap about-hero-inner">
    <p class="eyebrow">Tentang kami</p>
    <h1>About Arminareka</h1>
    <p class="about-hero-lead">
      Mengenal Arminareka dan {{ $legalName }} sebagai penyelenggara perjalanan ibadah Umrah dan Haji Khusus.
    </p>
  </div>
</section>

<section class="wrap about-section" id="tentang">
  <h2>Tentang Arminareka</h2>
  <div class="about-intro-grid">
    <div class="about-prose">
      <p>
        <strong>{{ $brandName }}</strong> adalah brand layanan perjalanan ibadah Umrah dan Haji Khusus yang
        dioperasikan oleh <strong>{{ $legalName }}</strong>. Melalui brand ini, calon jamaah dapat
        mengenal program perjalanan, informasi paket, dan layanan pendampingan sebelum berangkat.
      </p>
      <p>
        {{ $legalName }} berfokus pada penyelenggaraan perjalanan ibadah dengan pendekatan yang
        profesional, transparan, dan berorientasi pada kebutuhan jamaah. Perusahaan telah beroperasi
        sejak {{ $company::FOUNDED_YEAR }} dan terus melayani jamaah Umrah serta Haji Khusus
        melalui berbagai program keberangkatan.
      </p>
      <p class="about-note">
        {{ $legalName }} merupakan penyelenggara perjalanan ibadah Umrah dan Haji Khusus.
        Perizinan dan legalitas perjalanan ibadah tercantum atas nama badan usaha tersebut.
      </p>
    </div>
    <div class="about-video-frame">
      <iframe
        src="https://www.youtube-nocookie.com/embed/{{ $company::YOUTUBE_VIDEO_ID }}?start={{ $company::YOUTUBE_START_SECONDS }}&rel=0"
        title="Video profil Arminareka"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
        loading="lazy"
        referrerpolicy="strict-origin-when-cross-origin"
      ></iframe>
    </div>
  </div>
</section>

<section class="wrap about-section about-section-soft" id="pengalaman">
  <h2>Pengalaman</h2>
  <p class="about-section-intro">
    Informasi berikut merangkum latar belakang operasional {{ $legalName }} berdasarkan data
    profil resmi perusahaan.
  </p>
  <ul class="about-facts">
    @foreach($company::experienceFacts() as $fact)
      <li>
        <span>{{ $fact['label'] }}</span>
        <b>{{ $fact['value'] }}</b>
      </li>
    @endforeach
  </ul>
</section>

<section class="wrap about-section" id="legalitas">
  <h2>Legalitas &amp; Perizinan</h2>
  <p class="about-section-intro">
    {{ $legalName }} memiliki legalitas sebagai penyelenggara perjalanan ibadah Umrah dan Haji Khusus.
    Nomor izin berikut merujuk pada data perizinan terbaru yang tercantum dalam profil resmi perusahaan.
  </p>
  <div class="about-card-grid">
    @foreach($company::licenses() as $item)
      <article class="about-card">
        <h3>{{ $item['label'] }}</h3>
        <p class="about-card-value">{{ $item['value'] }}</p>
        @if($item['note'])
          <p class="about-card-note">{{ $item['note'] }}</p>
        @endif
      </article>
    @endforeach
  </div>
  <p class="about-disclaimer">
    Izin PPIU dan PIHK di atas diterbitkan atas nama <strong>{{ $legalName }}</strong>.
    Website ini menampilkan informasi resmi perusahaan; bukan entitas hukum terpisah dari badan usaha tersebut.
    Informasi legalitas merujuk profil resmi
    <a href="{{ $company::OFFICIAL_PROFILE_URL }}" target="_blank" rel="noopener noreferrer">{{ $legalName }}</a>.
  </p>
</section>

<section class="wrap about-section about-section-soft" id="penghargaan">
  <h2>Penghargaan &amp; Pencapaian</h2>
  <p class="about-section-intro">
    Penghargaan berikut tercantum dalam profil resmi {{ $legalName }} dan dapat diverifikasi melalui penerbit masing-masing.
  </p>
  <div class="about-card-grid about-card-grid-2">
    @foreach($company::awards() as $award)
      <article class="about-card about-card-award">
        <div class="about-award-icon" aria-hidden="true"><i class="bi bi-award"></i></div>
        <h3>{{ $award['title'] }}</h3>
        <p class="about-card-meta">{{ $award['issuer'] }}@if($award['period']) · {{ $award['period'] }}@endif</p>
        <p>{{ $award['description'] }}</p>
      </article>
    @endforeach
  </div>
</section>

<section class="wrap about-section" id="komitmen">
  <h2>Komitmen Pelayanan</h2>
  <p class="about-section-intro">
    {{ $brandName }} berkomitmen memberikan pelayanan yang profesional, informatif, dan mendampingi jamaah
    sepanjang proses perjalanan ibadah.
  </p>
  <div class="about-commit-grid">
    @foreach($company::commitments() as $item)
      <article class="about-commit">
        <span class="about-commit-icon" aria-hidden="true"><i class="bi {{ $item['icon'] }}"></i></span>
        <h3>{{ $item['title'] }}</h3>
        <p>{{ $item['description'] }}</p>
      </article>
    @endforeach
  </div>
</section>

<section class="about-cta">
  <div class="wrap about-cta-box">
    <div>
      <h2>Butuh informasi paket?</h2>
      <p>Lihat program Umrah dan Haji Khusus, atau hubungi tim kami untuk konsultasi.</p>
    </div>
    <div class="about-cta-actions">
      <a class="btn light" href="{{ route('packages.index', ['kelompok' => 'umroh']) }}">Lihat Paket Umrah</a>
      <a class="btn" href="{{ route('go.whatsapp', ['from' => 'about-cta']) }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>Hubungi Kami</a>
    </div>
  </div>
</section>
@endsection
