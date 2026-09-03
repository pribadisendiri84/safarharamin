@extends('layouts.admin')

@section('title', 'Grouping — '.$departure->program_name)
@section('content')
<div class="page-head">
  <div>
    <p class="eyebrow">{{ $departure->formattedDepartureDate() }} · {{ $departure->kindLabel() }}</p>
    <h1>Grouping Room</h1>
    <p class="sub">{{ $departure->program_name }}</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.operations.recap.show', $departure) }}">Rekap</a>
    <a class="btn gray" href="{{ route('admin.operations.pilgrims.create', ['departure_id' => $departure->id]) }}">Tambah jamaah</a>
    <form method="post" action="{{ route('admin.operations.grouping.auto', $departure) }}">
      @csrf
      <button class="btn" type="submit">Auto Group</button>
    </form>
  </div>
</div>

<div class="stat-row compact-stat-row">
  <div class="stat"><span>Total jamaah</span><b>{{ $stats['total_pilgrims'] }}</b></div>
  <div class="stat"><span>Room full</span><b>{{ $stats['rooms_full'] }}</b></div>
  <div class="stat warn-stat"><span>Room belum penuh</span><b>{{ $stats['rooms_incomplete'] }}</b></div>
  <div class="stat warn-stat"><span>Belum group</span><b>{{ $stats['pilgrims_ungrouped'] }}</b></div>
</div>

<div class="tabs room-tabs">
  @foreach($roomTypes as $key => $label)
    <a href="{{ route('admin.operations.grouping.index', [$departure, 'tab' => $key]) }}" class="{{ $activeTab === $key ? 'active' : '' }}">{{ strtoupper($label) }}</a>
  @endforeach
</div>

<div class="grouping-layout">
  <section class="grouping-main">
    <div class="grouping-toolbar">
      <form method="post" action="{{ route('admin.operations.grouping.rooms.store', $departure) }}" class="inline-form">
        @csrf
        <input type="hidden" name="room_type" value="{{ $activeTab }}">
        <button class="btn gray" type="submit">+ Buat room {{ strtoupper($activeTab) }}</button>
      </form>
    </div>

    <div class="room-grid">
      @forelse($rooms as $room)
        <article class="room-card room-card--{{ $room->statusClass() }}">
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
            @forelse($room->pilgrims as $member)
              <li>
                <span class="room-member-name">{{ $member->full_name }}</span>
                <form method="post" action="{{ route('admin.operations.grouping.remove', $departure) }}">
                  @csrf
                  <input type="hidden" name="pilgrim_id" value="{{ $member->id }}">
                  <button class="room-member-remove" type="submit" title="Keluarkan dari room">×</button>
                </form>
              </li>
            @empty
              <li class="room-members-empty">Belum ada jamaah</li>
            @endforelse
          </ul>

          <div class="room-card-actions">
            @if(! $room->isFull() && $ungrouped->isNotEmpty())
              <div class="room-action-block">
                <span class="room-action-label">Tambah jamaah</span>
                <form class="room-action-form" method="post" action="{{ route('admin.operations.grouping.assign', $departure) }}">
                  @csrf
                  <input type="hidden" name="room_id" value="{{ $room->id }}">
                  <select name="pilgrim_id" required>
                    <option value="">Pilih jamaah…</option>
                    @foreach($ungrouped as $pilgrim)
                      <option value="{{ $pilgrim->id }}">{{ $pilgrim->full_name }}</option>
                    @endforeach
                  </select>
                  <button class="btn gray sm full" type="submit">Tambah ke room</button>
                </form>
              </div>
            @endif

            @php
              $moveTargets = $rooms->where('id', '!=', $room->id)->filter(fn ($r) => ! $r->isFull());
            @endphp
            @if($room->pilgrims->isNotEmpty() && $moveTargets->isNotEmpty())
              <div class="room-action-block">
                <span class="room-action-label">Pindah room</span>
                <form class="room-action-form" method="post" action="{{ route('admin.operations.grouping.move', $departure) }}">
                  @csrf
                  <select name="pilgrim_id" required>
                    <option value="">Pilih jamaah…</option>
                    @foreach($room->pilgrims as $member)
                      <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                    @endforeach
                  </select>
                  <select name="room_id" required>
                    <option value="">Pilih room tujuan…</option>
                    @foreach($moveTargets as $target)
                      <option value="{{ $target->id }}">{{ $target->room_number }} ({{ $target->occupancyLine() }})</option>
                    @endforeach
                  </select>
                  <button class="btn gray sm full" type="submit">Pindahkan</button>
                </form>
              </div>
            @endif

            @if($room->isEmpty())
              <form method="post" action="{{ route('admin.operations.grouping.rooms.destroy', [$departure, $room]) }}" onsubmit="return confirm('Hapus room {{ $room->room_number }}?')">
                @csrf @method('DELETE')
                <button class="btn gray sm full danger" type="submit">Hapus room kosong</button>
              </form>
            @endif
          </div>
        </article>
      @empty
        <div class="empty-state panel form-pad">Belum ada room {{ strtoupper($activeTab) }}. Buat room atau jalankan Auto Group.</div>
      @endforelse
    </div>
  </section>

  <aside class="grouping-side panel form-pad">
    <h2>Belum group ({{ strtoupper($activeTab) }})</h2>
    <ul class="ungrouped-list">
      @forelse($ungrouped as $pilgrim)
        <li>
          <div>
            <b>{{ $pilgrim->full_name }}</b>
            <small>{{ $pilgrim->phone ?: '—' }}</small>
          </div>
        </li>
      @empty
        <li class="empty-cell">Semua jamaah {{ strtoupper($activeTab) }} sudah grouped.</li>
      @endforelse
    </ul>
  </aside>
</div>
@endsection
