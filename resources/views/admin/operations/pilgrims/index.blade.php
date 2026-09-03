@extends('layouts.admin')

@section('title', 'Jamaah')
@section('content')
<div class="page-head">
  <div>
    <h1>Jamaah</h1>
    <p class="sub">Data jamaah per keberangkatan, tipe kamar, dan status grouping.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.operations.pilgrims.create', request()->only('departure_id')) }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah jamaah</a>
  </div>
</div>

@include('admin.partials.scope-tabs')

<form class="filter-bar filter-bar--wide" method="get">
  @if($trashed)<input type="hidden" name="trashed" value="1">@endif
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, HP, ID/porsi haji">
  <select name="departure_id">
    <option value="">Semua keberangkatan</option>
    @foreach($departures as $departure)
      <option value="{{ $departure->id }}" @selected((int) request('departure_id') === $departure->id)>{{ $departure->program_name }}</option>
    @endforeach
  </select>
  <select name="room_type">
    <option value="">Semua kamar</option>
    @foreach(\App\Enums\RoomType::labels() as $key => $label)
      <option value="{{ $key }}" @selected(request('room_type') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="group">
    <option value="">Semua group</option>
    <option value="grouped" @selected(request('group') === 'grouped')>Grouped</option>
    <option value="ungrouped" @selected(request('group') === 'ungrouped')>Belum group</option>
  </select>
  <select name="payment">
    <option value="">Semua pembayaran</option>
    <option value="lunas" @selected(request('payment') === 'lunas')>Lunas</option>
    <option value="cicilan" @selected(request('payment') === 'cicilan')>Cicilan</option>
    <option value="belum" @selected(request('payment') === 'belum')>Belum bayar</option>
  </select>
  <button class="btn gray filter-submit" type="submit">@include('admin.partials.icon', ['name' => 'search']) Filter</button>
</form>

<div class="panel">
  <div class="table-wrap">
    <table class="ops-table pilgrim-table">
      <thead>
        <tr>
          <th class="col-name">Nama</th>
          <th class="col-hp">HP</th>
          <th class="col-departure">Keberangkatan</th>
          <th class="col-room">Kamar</th>
          <th class="col-status">Group</th>
          <th class="col-status">Bayar</th>
          <th class="col-actions"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($pilgrims as $pilgrim)
          <tr>
            <td class="col-name"><b>{{ $pilgrim->full_name }}</b></td>
            <td class="col-hp"><span class="cell-mono">{{ $pilgrim->phone ?: '—' }}</span></td>
            <td class="col-departure">
              <span class="cell-truncate" title="{{ $pilgrim->departure?->program_name }}">{{ $pilgrim->departure?->program_name ?: '—' }}</span>
            </td>
            <td class="col-room">
              <span class="room-chip">{{ $pilgrim->roomTypeLabel() }}</span>
              @if($pilgrim->room)
                <span class="room-id">{{ $pilgrim->room->room_number }}</span>
              @else
                <span class="room-id muted">—</span>
              @endif
            </td>
            <td class="col-status">
              <span class="badge badge-stat {{ $pilgrim->groupingStatusClass() }}">{{ $pilgrim->groupingStatusLabel() }}</span>
            </td>
            <td class="col-status">
              <span class="badge badge-stat badge-pay {{ $pilgrim->paymentStatusClass() }}">{{ $pilgrim->paymentStatusLabel() }}</span>
              @if($hint = $pilgrim->paymentStatusHint())
                <small class="pay-hint pay-hint-over">{{ $hint }}</small>
              @endif
            </td>
            <td class="col-actions">
              <div class="row-actions-cell">
                @unless($trashed)
                  <a class="btn gray sm" href="{{ route('admin.operations.pilgrims.show', $pilgrim) }}">Detail</a>
                  <a class="btn gray sm" href="{{ route('admin.operations.pilgrims.edit', $pilgrim) }}">Edit</a>
                  @if($pilgrim->departure)
                    <a class="btn gray sm" href="{{ route('admin.operations.grouping.index', [$pilgrim->departure, 'tab' => $pilgrim->room_type]) }}">Group</a>
                  @endif
                @else
                  <form method="post" action="{{ route('admin.operations.pilgrims.restore', $pilgrim->id) }}">@csrf<button class="btn gray sm" type="submit">Pulihkan</button></form>
                @endunless
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-cell">Belum ada jamaah.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($pilgrims->hasPages())
    <div class="pager">{{ $pilgrims->links() }}</div>
  @endif
</div>
@endsection
