@extends('layouts.admin')

@section('title', 'Keberangkatan')
@section('content')
<div class="page-head">
  <div>
    <h1>Keberangkatan</h1>
    <p class="sub">Kelola program keberangkatan umroh & haji untuk operasional jamaah.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.operations.departures.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah keberangkatan</a>
  </div>
</div>

@include('admin.partials.scope-tabs')

<form class="filter-bar" method="get">
  @if($trashed)<input type="hidden" name="trashed" value="1">@endif
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari program, maskapai, penerbangan">
  <select name="kind">
    <option value="">Semua jenis</option>
    @foreach(\App\Models\Departure::KINDS as $key => $label)
      <option value="{{ $key }}" @selected(request('kind') === $key)>{{ $label }}</option>
    @endforeach
  </select>
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
              @if($departure->flight_number)<br><small class="muted">{{ $departure->flight_number }}</small>@endif
            </td>
            <td>{{ $departure->formattedDepartureDate() }}</td>
            <td>{{ $departure->airline ?: '—' }}</td>
            <td><span class="badge">{{ $departure->kindLabel() }}</span></td>
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
