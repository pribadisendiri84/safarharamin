@extends('layouts.admin')

@section('title', 'Rekap — '.$departure->program_name)
@section('content')
<div class="page-head">
  <div>
    <p class="eyebrow">{{ $departure->formattedDepartureDate() }} · {{ $departure->kindLabel() }}</p>
    <h1>Rekap Keberangkatan</h1>
    <p class="sub">{{ $departure->program_name }}</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.operations.grouping.index', $departure) }}">Grouping room</a>
    <a class="btn gray" href="{{ route('admin.operations.departures.edit', $departure) }}">Edit keberangkatan</a>
  </div>
</div>

<div class="stat-row">
  <div class="stat"><span>Total jamaah</span><b>{{ $stats['total_pilgrims'] }}</b></div>
  <div class="stat"><span>Total room</span><b>{{ $stats['total_rooms'] }}</b></div>
  <div class="stat"><span>Quad</span><b>{{ $stats['total_quad'] }}</b></div>
  <div class="stat"><span>Triple</span><b>{{ $stats['total_triple'] }}</b></div>
  <div class="stat"><span>Double</span><b>{{ $stats['total_double'] }}</b></div>
  @if($departure->isHaji())
    <div class="stat"><span>Double Plus</span><b>{{ $stats['total_double_plus'] }}</b></div>
  @endif
  <div class="stat"><span>Room full</span><b>{{ $stats['rooms_full'] }}</b></div>
  <div class="stat warn-stat"><span>Room belum full</span><b>{{ $stats['rooms_incomplete'] }}</b></div>
  <div class="stat warn-stat"><span>Belum group</span><b>{{ $stats['pilgrims_ungrouped'] }}</b></div>
</div>

@foreach(\App\Enums\RoomType::labelsFor($departure->program_kind) as $typeKey => $typeLabel)
  @php
    $typeRooms = $departure->rooms->where('room_type', $typeKey);
    $typeUngrouped = $departure->pilgrims->where('room_type', $typeKey)->whereNull('room_id');
  @endphp
  <section class="panel form-pad recap-section">
    <div class="section-head-inline">
      <h2>{{ strtoupper($typeLabel) }}</h2>
      <span class="muted">{{ $typeRooms->count() }} room · {{ $departure->pilgrims->where('room_type', $typeKey)->count() }} jamaah</span>
    </div>

    <div class="recap-room-grid">
      @foreach($typeRooms as $room)
        <article class="room-card room-card--{{ $room->statusClass() }} compact">
          <header class="room-card-top">
            <div class="room-card-title">
              <strong>{{ $room->room_number }}</strong>
              <span>{{ strtoupper($room->typeLabel()) }}</span>
            </div>
            <div class="room-card-status">
              <span class="room-occupancy">{{ $room->occupancyLine() }}</span>
              <span class="badge {{ $room->statusClass() }}">{{ $room->statusLabel() }}</span>
            </div>
          </header>
          <ul class="room-members">
            @foreach($room->pilgrims as $member)
              <li>
                <span class="room-member-name">{{ $member->full_name }}</span>
                <span class="room-member-pay">
                  <span class="badge {{ $member->paymentStatusClass() }}">{{ $member->paymentStatusLabel() }}</span>
                  @if($hint = $member->paymentStatusHint())
                    <small class="pay-hint pay-hint-over">{{ $hint }}</small>
                  @endif
                </span>
              </li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>

    @if($typeUngrouped->isNotEmpty())
      <div class="recap-ungrouped">
        <h3>Belum group</h3>
        <ul class="checks">
          @foreach($typeUngrouped as $pilgrim)
            <li>{{ $pilgrim->full_name }}@if($pilgrim->phone) · {{ $pilgrim->phone }}@endif</li>
          @endforeach
        </ul>
      </div>
    @endif
  </section>
@endforeach
@endsection
