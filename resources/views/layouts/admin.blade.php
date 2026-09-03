<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin') — {{ $site->name }}</title>
<link rel="icon" type="image/webp" href="{{ $site->logoUrl }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.default.min.css">
</head>
<body>
<div class="side-scrim" id="scrim" onclick="toggleSidebar(false)"></div>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="side-brand" href="{{ route('admin.dashboard') }}">
      <img class="brand-logo" src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
    </a>
    <nav class="side-nav">
      @php
        $menu = [
          ['route' => 'admin.dashboard', 'href' => route('admin.dashboard'), 'label' => 'Dashboard', 'icon' => 'home'],
          ['route' => 'admin.packages.*', 'href' => route('admin.packages.index'), 'label' => 'Paket', 'icon' => 'plane', 'ability' => 'manage-catalog'],
          ['route' => 'admin.cities.*', 'href' => route('admin.cities.index'), 'label' => 'Kota', 'icon' => 'pin', 'ability' => 'manage-catalog'],
          ['route' => 'admin.gallery.*', 'href' => route('admin.gallery.index'), 'label' => 'Galeri', 'icon' => 'image', 'ability' => 'manage-catalog'],
          ['route' => 'admin.testimonials.*', 'href' => route('admin.testimonials.index'), 'label' => 'Testimoni', 'icon' => 'quote', 'ability' => 'manage-catalog'],
          ['route' => 'admin.inquiries.*', 'href' => route('admin.inquiries.index'), 'label' => 'Pengajuan', 'icon' => 'inbox'],
          ['route' => 'admin.operations.*', 'href' => route('admin.operations.dashboard'), 'label' => 'Operasi jamaah', 'icon' => 'users'],
          ['route' => 'admin.operations.departures.*', 'href' => route('admin.operations.departures.index'), 'label' => 'Keberangkatan', 'icon' => 'calendar', 'match' => ['admin.operations.departures.*', 'admin.operations.grouping.*', 'admin.operations.recap.*']],
          ['route' => 'admin.operations.pilgrims.*', 'href' => route('admin.operations.pilgrims.index'), 'label' => 'Jamaah', 'icon' => 'box'],
          ['route' => 'admin.settings.*', 'href' => route('admin.settings.edit'), 'label' => 'Pengaturan', 'icon' => 'gear', 'ability' => 'manage-catalog'],
        ];
      @endphp
      @foreach($menu as $item)
        @if(empty($item['ability']) || auth()->user()->can($item['ability']))
        @php
          $activeRoutes = $item['match'] ?? [$item['route']];
          $isActive = collect($activeRoutes)->contains(fn ($pattern) => request()->routeIs($pattern));
        @endphp
        <a href="{{ $item['href'] }}" class="{{ $isActive ? 'active' : '' }}">
          @include('admin.partials.icon', ['name' => $item['icon']])
          {{ $item['label'] }}
        </a>
        @endif
      @endforeach
      @can('manage-users')
        <a href="{{ route('admin.history.index') }}" class="{{ request()->routeIs('admin.history.*') ? 'active' : '' }}">
          @include('admin.partials.icon', ['name' => 'history'])
          Riwayat
        </a>
        <a href="{{ route('admin.traffic.index') }}" class="{{ request()->routeIs('admin.traffic.*') ? 'active' : '' }}">
          @include('admin.partials.icon', ['name' => 'chart'])
          Trafik
        </a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
          @include('admin.partials.icon', ['name' => 'users'])
          Pengguna
        </a>
      @endcan
    </nav>
    <div class="side-foot">
      @auth
      <div class="side-user">
        <b>{{ auth()->user()->name }}</b>
        <span>{{ auth()->user()->resolvedRole()->label() }}</span>
      </div>
      @endauth
      <a class="side-link" href="{{ route('home') }}" target="_blank">
        @include('admin.partials.icon', ['name' => 'external'])
        Lihat website
      </a>
      <form method="post" action="{{ route('admin.logout') }}">
        @csrf
        <button class="btn gray full-btn" type="submit">Keluar</button>
      </form>
    </div>
  </aside>
  <div class="content">
    <div class="mobile-bar">
      <button class="btn gray" type="button" onclick="toggleSidebar()">☰ Menu</button>
      <img class="brand-logo" src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
    </div>
    <main class="wrap">
      @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
      @if(session('err'))<div class="alert err">{{ session('err') }}</div>@endif
      @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
      @yield('content')
    </main>
  </div>
</div>
<script>
function toggleSidebar(force) {
  var side = document.getElementById('sidebar');
  var scrim = document.getElementById('scrim');
  var open = typeof force === 'boolean' ? force : !side.classList.contains('open');
  side.classList.toggle('open', open);
  scrim.classList.toggle('on', open);
}
</script>
@include('admin.partials.searchable-select-script')
@include('admin.partials.password-toggle-script')
@include('admin.partials.rupiah-input-script')
@stack('scripts')
</body>
</html>
