@php
  $dashboard = [
    'route' => 'admin.dashboard',
    'href' => route('admin.dashboard'),
    'label' => 'Dashboard',
    'icon' => 'home',
  ];

  $groups = [
    [
      'id' => 'website',
      'label' => 'Website',
      'items' => [
        ['route' => 'admin.packages.*', 'href' => route('admin.packages.index'), 'label' => 'Paket', 'icon' => 'plane', 'ability' => 'manage-catalog'],
        ['route' => 'admin.gallery.*', 'href' => route('admin.gallery.index'), 'label' => 'Galeri', 'icon' => 'image', 'ability' => 'manage-catalog'],
        ['route' => 'admin.testimonials.*', 'href' => route('admin.testimonials.index'), 'label' => 'Testimoni', 'icon' => 'quote', 'ability' => 'manage-catalog'],
      ],
    ],
    [
      'id' => 'pendaftaran',
      'label' => 'Pendaftaran',
      'items' => [
        ['route' => 'admin.inquiries.*', 'href' => route('admin.inquiries.index'), 'label' => 'Pengajuan', 'icon' => 'inbox'],
      ],
    ],
    [
      'id' => 'operasional',
      'label' => 'Operasional',
      'items' => [
        ['route' => 'admin.operations.dashboard', 'href' => route('admin.operations.dashboard'), 'label' => 'Ringkasan', 'icon' => 'chart'],
        ['route' => 'admin.operations.departures.*', 'href' => route('admin.operations.departures.index'), 'label' => 'Keberangkatan', 'icon' => 'calendar', 'match' => ['admin.operations.departures.*', 'admin.operations.grouping.*', 'admin.operations.recap.*']],
        ['route' => 'admin.operations.pilgrims.*', 'href' => route('admin.operations.pilgrims.index'), 'label' => 'Jamaah', 'icon' => 'box'],
      ],
    ],
    [
      'id' => 'master-data',
      'label' => 'Master Data',
      'items' => [
        ['route' => 'admin.cities.*', 'href' => route('admin.cities.index'), 'label' => 'Kota Embarkasi', 'icon' => 'pin', 'ability' => 'manage-catalog'],
        ['route' => 'admin.hotels.*', 'href' => route('admin.hotels.index'), 'label' => 'Hotel', 'icon' => 'pin', 'ability' => 'manage-catalog'],
        ['route' => 'admin.airlines.*', 'href' => route('admin.airlines.index'), 'label' => 'Maskapai', 'icon' => 'plane', 'ability' => 'manage-catalog'],
        ['route' => 'admin.pics.*', 'href' => route('admin.pics.index'), 'label' => 'PIC', 'icon' => 'users', 'ability' => 'manage-catalog'],
        ['route' => 'admin.package-kinds.*', 'href' => route('admin.package-kinds.index'), 'label' => 'Tipe Paket', 'icon' => 'box', 'ability' => 'manage-catalog'],
      ],
    ],
    [
      'id' => 'sistem',
      'label' => 'Sistem',
      'items' => [
        ['route' => 'admin.settings.*', 'href' => route('admin.settings.edit'), 'label' => 'Pengaturan', 'icon' => 'gear', 'ability' => 'manage-catalog'],
        ['route' => 'admin.history.*', 'href' => route('admin.history.index'), 'label' => 'Log Aktivitas', 'icon' => 'history', 'ability' => 'manage-users'],
        ['route' => 'admin.traffic.*', 'href' => route('admin.traffic.index'), 'label' => 'Trafik Website', 'icon' => 'chart', 'ability' => 'manage-users'],
        ['route' => 'admin.users.*', 'href' => route('admin.users.index'), 'label' => 'Pengguna', 'icon' => 'users', 'ability' => 'manage-users'],
      ],
    ],
  ];

  $canSeeItem = fn (array $item): bool => empty($item['ability']) || auth()->user()->can($item['ability']);
  $isItemActive = function (array $item): bool {
    $activeRoutes = $item['match'] ?? [$item['route']];

    return collect($activeRoutes)->contains(fn ($pattern) => request()->routeIs($pattern));
  };
@endphp

@php
  $dashboardActive = $isItemActive($dashboard);
@endphp
<a href="{{ $dashboard['href'] }}" class="side-nav-top {{ $dashboardActive ? 'active' : '' }}">
  @include('admin.partials.icon', ['name' => $dashboard['icon']])
  <span>{{ $dashboard['label'] }}</span>
</a>

@foreach($groups as $group)
  @php
    $visibleItems = collect($group['items'])->filter($canSeeItem)->values();
    $groupHasActive = $visibleItems->contains(fn ($item) => $isItemActive($item));
  @endphp
  @if($visibleItems->isNotEmpty())
    <div class="nav-group {{ $groupHasActive ? 'is-open is-active' : '' }}" data-nav-group="{{ $group['id'] }}">
      <button
        type="button"
        class="nav-group-toggle"
        aria-expanded="{{ $groupHasActive ? 'true' : 'false' }}"
        aria-controls="nav-group-{{ $group['id'] }}"
      >
        <span>{{ $group['label'] }}</span>
      </button>
      <div class="nav-group-items" id="nav-group-{{ $group['id'] }}">
        @foreach($visibleItems as $item)
          <a href="{{ $item['href'] }}" class="{{ $isItemActive($item) ? 'active' : '' }}">
            @include('admin.partials.icon', ['name' => $item['icon']])
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </div>
    </div>
  @endif
@endforeach
