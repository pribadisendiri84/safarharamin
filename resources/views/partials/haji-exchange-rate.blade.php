@php $compact = $compact ?? false; @endphp
@if(!empty($hajiExchangeRate))
<aside class="haji-kurs-card{{ $compact ? ' haji-kurs-card-compact' : '' }}" aria-label="Informasi kurs">
  <span class="haji-kurs-chip">Kurs {{ $hajiExchangeRate['currency'] }}</span>
  <p class="haji-kurs-value">{{ $hajiExchangeRate['formatted'] }}</p>
  <p class="haji-kurs-meta">Per 1 {{ $hajiExchangeRate['currency'] }}</p>
  @if(!empty($hajiExchangeRate['updated_at']))
    <time class="haji-kurs-updated" datetime="{{ $hajiExchangeRate['updated_at'] }}">
      Diperbarui {{ \Carbon\Carbon::parse($hajiExchangeRate['updated_at'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
    </time>
  @endif
</aside>
@endif
