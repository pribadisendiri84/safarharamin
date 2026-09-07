@if($site->packageCardStyle === \App\Support\SiteProfile::CARD_STYLE_CATALOG)
  @include('partials.package-card-catalog', ['package' => $package])
@else
  @include('partials.package-card-classic', ['package' => $package])
@endif
