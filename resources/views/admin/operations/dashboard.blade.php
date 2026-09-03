@extends('layouts.admin')

@section('title', 'Operasi Jamaah')
@section('content')
<div class="page-head">
  <div>
    <h1>Operasi Jamaah</h1>
    <p class="sub">Pencatatan jamaah, grouping kamar, dan pembukuan DP/pelunasan.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.operations.departures.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Keberangkatan</a>
    <a class="btn" href="{{ route('admin.operations.pilgrims.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Jamaah</a>
  </div>
</div>

<div class="stat-row">
  <div class="stat"><span>Total jamaah</span><b>{{ number_format($stats['total_pilgrims'], 0, ',', '.') }}</b></div>
  <div class="stat"><span>Quad</span><b>{{ number_format($stats['total_quad'], 0, ',', '.') }}</b></div>
  <div class="stat"><span>Triple</span><b>{{ number_format($stats['total_triple'], 0, ',', '.') }}</b></div>
  <div class="stat"><span>Double</span><b>{{ number_format($stats['total_double'], 0, ',', '.') }}</b></div>
  <div class="stat"><span>Total room</span><b>{{ number_format($stats['total_rooms'], 0, ',', '.') }}</b></div>
  <div class="stat"><span>Room full</span><b>{{ number_format($stats['rooms_full'], 0, ',', '.') }}</b></div>
  <div class="stat warn-stat"><span>Room belum penuh</span><b>{{ number_format($stats['rooms_incomplete'], 0, ',', '.') }}</b></div>
  <div class="stat warn-stat"><span>Belum group</span><b>{{ number_format($stats['pilgrims_ungrouped'], 0, ',', '.') }}</b></div>
</div>

<div class="stat-row stat-row--payment">
  <div class="stat tone-green"><span>Lunas</span><b>{{ number_format($stats['pilgrims_lunas'], 0, ',', '.') }}</b></div>
  <div class="stat tone-amber"><span>Cicilan</span><b>{{ number_format($stats['pilgrims_cicilan'], 0, ',', '.') }}</b></div>
  <div class="stat tone-rose"><span>Belum bayar</span><b>{{ number_format($stats['pilgrims_belum_bayar'], 0, ',', '.') }}</b></div>
  @if($stats['pilgrims_overpaid'] > 0)
    <div class="stat warn-stat"><span>Lebih bayar</span><b>{{ number_format($stats['pilgrims_overpaid'], 0, ',', '.') }}</b><small>Total {{ 'Rp '.number_format($stats['total_overpayment'], 0, ',', '.') }}</small></div>
  @endif
</div>

@if($overpaidPilgrims->isNotEmpty())
<div class="panel form-pad dashboard-overpaid">
  <div class="section-head-inline">
    <h2>Jamaah lebih bayar</h2>
    <span class="muted">{{ $overpaidPilgrims->count() }} jamaah · total {{ 'Rp '.number_format($stats['total_overpayment'], 0, ',', '.') }}</span>
  </div>
  <ul class="incomplete-room-list overpaid-list">
    @foreach($overpaidPilgrims as $pilgrim)
      <li>
        <div class="incomplete-room-main">
          <strong>{{ $pilgrim->full_name }}</strong>
          <span class="badge pay-over">Lebih bayar</span>
          <span class="room-occupancy">{{ $pilgrim->formattedOverpayment() }}</span>
        </div>
        <div class="incomplete-room-meta">
          <span>{{ $pilgrim->departure?->program_name ?: '—' }}</span>
          <span>{{ $pilgrim->formattedPaidAmount() }} / {{ $pilgrim->formattedPackagePrice() }}</span>
        </div>
        <a class="btn gray sm" href="{{ route('admin.operations.pilgrims.show', $pilgrim) }}">Detail</a>
      </li>
    @endforeach
  </ul>
</div>
@endif

@if($incompleteRooms->isNotEmpty())
<div class="panel form-pad dashboard-incomplete-rooms">
  <div class="section-head-inline">
    <h2>Detail room belum penuh</h2>
    <span class="muted">{{ $incompleteRooms->count() }} room perlu diisi</span>
  </div>
  <ul class="incomplete-room-list">
    @foreach($incompleteRooms as $room)
      <li>
        <div class="incomplete-room-main">
          <strong>{{ $room->room_number }}</strong>
          <span class="badge {{ $room->statusClass() }}">{{ $room->statusLabel() }}</span>
          <span class="room-occupancy">{{ $room->occupancyLine() }}</span>
        </div>
        <div class="incomplete-room-meta">
          <span>{{ strtoupper($room->typeLabel()) }}</span>
          <span>{{ $room->departure?->program_name }}</span>
        </div>
        @if($room->departure)
          <a class="btn gray sm" href="{{ route('admin.operations.grouping.index', [$room->departure, 'tab' => $room->room_type]) }}">Grouping</a>
        @endif
      </li>
    @endforeach
  </ul>
</div>
@endif

<div class="panel form-pad">
  <div class="section-head-inline">
    <h2>Keberangkatan terbaru</h2>
    <a href="{{ route('admin.operations.departures.index') }}">Lihat semua →</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Program</th>
          <th>Tanggal</th>
          <th>Jenis</th>
          <th>Jamaah</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($departures as $departure)
          <tr>
            <td><b>{{ $departure->program_name }}</b></td>
            <td>{{ $departure->formattedDepartureDate() }}</td>
            <td><span class="badge">{{ $departure->kindLabel() }}</span></td>
            <td>{{ $departure->pilgrims_count ?? $departure->pilgrims()->count() }}</td>
            <td class="row-actions-cell">
              <a class="btn gray sm" href="{{ route('admin.operations.grouping.index', $departure) }}">Grouping</a>
              <a class="btn gray sm" href="{{ route('admin.operations.recap.show', $departure) }}">Rekap</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-cell">Belum ada keberangkatan. Tambah dari menu Keberangkatan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
