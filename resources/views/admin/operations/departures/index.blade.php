@extends('layouts.admin')

@section('title', 'Keberangkatan')
@section('content')
@php
  $kindFilter = $kindFilter ?? request('kind', '');
  $filterQuery = request()->except('kind', 'page');
  $createParams = $kindFilter === 'haji' ? ['from' => 'haji_page'] : ($kindFilter === 'umroh' ? ['kind' => 'umroh'] : []);
@endphp
<div class="page-head">
  <div>
    <h1>Keberangkatan</h1>
    <p class="sub">Kelola keberangkatan umroh &amp; haji. Haji Plus diisi dari data halaman khusus — tanggal &amp; itinerary PDF di sini.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.operations.departures.create', $createParams) }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah keberangkatan</a>
  </div>
</div>

@include('admin.partials.scope-tabs')

<div class="tabs tabs--kind">
  <a href="{{ route('admin.operations.departures.index', $filterQuery) }}" class="{{ $kindFilter === '' ? 'active' : '' }}">Semua</a>
  @foreach(\App\Models\Departure::KINDS as $key => $label)
    <a href="{{ route('admin.operations.departures.index', [...$filterQuery, 'kind' => $key]) }}" class="{{ $kindFilter === $key ? 'active' : '' }}">{{ $label }}</a>
  @endforeach
</div>

<form class="filter-bar" method="get">
  @if($trashed)<input type="hidden" name="trashed" value="1">@endif
  @if($kindFilter !== '')<input type="hidden" name="kind" value="{{ $kindFilter }}">@endif
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari program, maskapai, penerbangan">
  <button class="btn gray filter-submit" type="submit">@include('admin.partials.icon', ['name' => 'search']) Filter</button>
</form>

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Program</th>
          <th>Tanggal</th>
          <th>Maskapai</th>
          <th>Jenis</th>
          <th>Jamaah</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($departures as $departure)
          <tr>
            <td>
              <b>{{ $departure->program_name }}</b>
              @if($departure->isHaji() && $departure->hijriLabel() !== '')
                <br><small class="muted">{{ $departure->hijriLabel() }}</small>
              @endif
            </td>
            <td>{{ $departure->formattedDepartureDate() }}</td>
            <td>{{ $departure->airlineLine() }}</td>
            <td><span class="badge kind-{{ $departure->program_kind }}">{{ $departure->kindLabel() }}</span></td>
            <td>{{ $departure->pilgrims_count }}</td>
            <td class="row-actions-cell">
              @unless($trashed)
                <a class="btn gray sm" href="{{ route('admin.operations.grouping.index', $departure) }}">Grouping</a>
                <a class="btn gray sm" href="{{ route('admin.operations.recap.show', $departure) }}">Rekap</a>
                <a class="btn gray sm" href="{{ route('admin.operations.departures.edit', $departure) }}">Edit</a>
                <form method="post" action="{{ route('admin.operations.departures.destroy', $departure) }}" onsubmit="return confirm('Hapus keberangkatan ini?')">
                  @csrf @method('DELETE')
                  <button class="btn gray sm danger" type="submit">Hapus</button>
                </form>
              @else
                <form method="post" action="{{ route('admin.operations.departures.restore', $departure->id) }}">
                  @csrf
                  <button class="btn gray sm" type="submit">Pulihkan</button>
                </form>
              @endunless
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-cell">Belum ada keberangkatan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($departures->hasPages())
    <div class="pager">{{ $departures->links() }}</div>
  @endif
</div>
@endsection
