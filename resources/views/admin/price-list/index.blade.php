@extends('layouts.admin')

@section('title', 'Price List')
@section('content')
<div class="page-head">
  <div>
    <h1>Price List</h1>
    <p class="sub">Daftar harga paket per jenis dan maskapai. Periode diambil dari judul paket.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.price-sync.schedule.edit') }}">Sync Schedule</a>
    <a class="btn gray" href="{{ route('admin.price-sync.index') }}">@include('admin.partials.icon', ['name' => 'upload']) Sync Harga</a>
  </div>
</div>

<div class="stat-row">
  <div class="stat"><div><div class="stat-label">Last Sync</div><div class="stat-value" style="font-size:1rem">{{ $lastSync?->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Next Scheduled</div><div class="stat-value" style="font-size:1rem">{{ $nextScheduledSync?->translatedFormat('d M Y H:i') ?? '—' }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Pending Changes</div><div class="stat-value">{{ $stats['pending_changes'] }}</div></div></div>
  <div class="stat"><div><div class="stat-label">Total / Tayang</div><div class="stat-value">{{ $stats['total'] }} / {{ $stats['published'] }}</div></div></div>
</div>

@if($pendingRun && $stats['pending_changes'] > 0)
  <div class="summary">
    <span class="bubble tone-gold">@include('admin.partials.icon', ['name' => 'inbox'])</span>
    <div>
      <b>{{ $stats['pending_changes'] }} changes waiting for approval</b>
      <p>{{ $pendingRun->reference() }} · {{ $schedule['enabled'] ? \App\Support\PriceSyncSchedule::summaryLabel() : 'Scheduler nonaktif' }}</p>
    </div>
    <a class="btn" href="{{ route('admin.price-sync.show', $pendingRun) }}">Review Changes</a>
  </div>
@endif

<form class="filter-bar" method="get">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari periode / judul">
  <select name="type">
    <option value="">Semua jenis</option>
    @foreach($filters['types'] as $key => $label)
      <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="airline">
    <option value="">Semua maskapai</option>
    @foreach($filters['airlines'] as $value => $label)
      <option value="{{ $value }}" @selected(request('airline') === $value)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="status">
    <option value="">Semua status</option>
    @foreach($filters['statuses'] as $key => $label)
      <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <input type="date" name="departure_from" value="{{ request('departure_from') }}">
  <input type="date" name="departure_to" value="{{ request('departure_to') }}">
  <label class="check filter-check">
    <input type="checkbox" name="only_arminareka" value="1" @checked(request()->boolean('only_arminareka'))> Hanya Arminareka
  </label>
  <button class="btn gray" type="submit">@include('admin.partials.icon', ['name' => 'search']) Filter</button>
</form>

@forelse($groups as $typeLabel => $airlineGroups)
  <div class="panel form-pad">
    <h2>{{ $typeLabel }}</h2>
    @foreach($airlineGroups as $airline => $packages)
      <h3 style="margin-top:1rem">{{ $airline }}</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Periode</th>
              <th>Embarkasi → Tujuan</th>
              <th>Tgl Berangkat</th>
              <th>Harga</th>
              <th>Seat</th>
              <th>Status Tayang</th>
              <th>Last Update</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($packages as $package)
              <tr class="{{ collect([$package->isPastDeparture() ? 'is-past-departure' : null, $package->isSeatsFull() ? 'is-seats-full' : null])->filter()->implode(' ') }}">
                <td>
                  <b>{{ $package->title }}</b>
                  @if($package->isPastDeparture())
                    <span class="badge past-departure">Periode lewat</span>
                  @endif
                  @if($package->isSeatsFull())
                    <span class="badge seats-full">Seat penuh</span>
                  @endif
                </td>
                <td>{{ $package->routeLine() ?: '—' }}</td>
                <td>{{ $package->catalogDepartureDateLine() ?? '—' }}</td>
                <td>{{ $package->formattedStartingPrice() }}</td>
                <td>{{ $package->seatsLine() }}</td>
                <td><span class="badge {{ $package->status }}">{{ \App\Models\Package::STATUSES[$package->status] ?? $package->status }}</span></td>
                <td>{{ $package->updated_at?->translatedFormat('d M Y H:i') }}</td>
                <td><a class="btn gray compact" href="{{ route('admin.packages.edit', $package) }}">Edit</a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endforeach
  </div>
@empty
  <div class="panel form-pad empty-state">Belum ada data price list.</div>
@endforelse
@endsection
