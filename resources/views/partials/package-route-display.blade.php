@php
  /** @var \App\Models\Package $package */
  $route = $package->catalogRouteCodes();
@endphp

<div class="catalog-route" aria-label="Rute {{ $package->routeLine() }}">
  <div class="catalog-route-end">
    <strong>{{ $route['from'] }}</strong>
  </div>
  <div class="catalog-route-icon" aria-hidden="true">
    <i class="bi bi-airplane"></i>
  </div>
  <div class="catalog-route-end is-arrival">
    <strong>{{ $route['to'] }}</strong>
  </div>
</div>
