@php
  $rooms = $rooms ?? [];
  $compact = $compact ?? false;
  $detailed = $detailed ?? false;
@endphp
@if($rooms !== [])
<div class="haji-room-grid{{ $compact ? ' haji-room-grid-compact' : '' }}{{ $detailed ? ' haji-room-grid-detailed' : '' }}">
  @foreach($rooms as $room)
    <article class="haji-room-card">
      <div class="haji-room-card-head">
        <span class="haji-room-type">{{ $room['label'] }}</span>
        @if(!empty($room['room_label']))
          <span class="haji-room-subtitle">{{ $room['room_label'] }}</span>
        @endif
        @if($room['occupancy_label'])
          <span class="haji-room-capacity"><i class="bi bi-people" aria-hidden="true"></i> {{ $room['occupancy_label'] }}</span>
        @endif
      </div>
      @if($detailed)
        <ul class="haji-room-notes">
          @if(!empty($room['hotel_note']))
            <li><i class="bi bi-building" aria-hidden="true"></i> {{ $room['hotel_note'] }}</li>
          @endif
          @if(!empty($room['transit_note']))
            <li><i class="bi bi-bus-front" aria-hidden="true"></i> {{ $room['transit_note'] }}</li>
          @endif
        </ul>
      @endif
      @if(!empty($room['display_price']))
        <strong class="haji-room-price" @if(!empty($room['formatted_price'])) title="{{ $room['formatted_price'] }}" @elseif(!empty($room['price_usd'])) title="Referensi flyer {{ $room['price_usd'] }} / pax" @endif>
          {{ $room['display_price'] }}<small>{{ $room['display_price_note'] ?? '/jamaah' }}</small>
        </strong>
        @if(!empty($room['formatted_price_short']) && !empty($room['price_usd']))
          <span class="haji-room-ref">Referensi flyer {{ $room['price_usd'] }} / pax</span>
        @endif
      @else
        <span class="haji-room-price-muted">Hubungi kami</span>
      @endif
    </article>
  @endforeach
</div>
@endif
