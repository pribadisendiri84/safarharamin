@php
  $seoTitle = $seoTitle ?? '';
  $seoDescription = $seoDescription ?? '';
  $seoCanonical = $seoCanonical ?? url()->current();
  $seoImage = $seoImage ?? url($site->logoUrl);
  $seoType = $seoType ?? 'website';
  $seoJsonLd = $seoJsonLd ?? null;
  $seoFullTitle = $seoTitle !== '' ? $seoTitle.' — '.$site->titleSuffix : $site->name.' — '.$site->titleSuffix;
@endphp
<link rel="canonical" href="{{ $seoCanonical }}">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:site_name" content="{{ $site->name }}">
<meta property="og:title" content="{{ $seoFullTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:locale" content="id_ID">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $seoFullTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@if($seoJsonLd)
<script type="application/ld+json">{!! json_encode($seoJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
