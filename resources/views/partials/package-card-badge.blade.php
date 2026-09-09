@if($package->hasCardBadge())
  @php
    $badgeIcon = $package->cardBadgeIcon();
    $badgeIconClass = \App\Support\PackageCardBadge::iconClass($badgeIcon);
    $badgeEmoji = \App\Support\PackageCardBadge::iconEmoji($badgeIcon);
  @endphp
  <span class="package-card-badge {{ $package->cardBadgePositionClass() }}">
    @if($badgeIconClass)
      <i class="bi {{ $badgeIconClass }}" aria-hidden="true"></i>
    @elseif($badgeEmoji)
      <span class="package-card-badge-emoji" aria-hidden="true">{{ $badgeEmoji }}</span>
    @endif
    {{ $package->cardBadgeText() }}
  </span>
@endif
