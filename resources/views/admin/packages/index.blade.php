@extends('layouts.admin')

@section('title', 'Paket')
@section('content')
<div class="page-head">
  <div>
    <h1>Paket keberangkatan</h1>
    <p class="sub">Katalog yang tampil di website.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.packages.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah paket</a>
  </div>
</div>
@include('admin.partials.scope-tabs')
<form class="filter-bar" method="get">
  @if($trashed)<input type="hidden" name="trashed" value="1">@endif
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul paket">
  <select name="status">
    <option value="">Semua status</option>
    @foreach(\App\Models\Package::STATUSES as $key => $label)
      <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <button class="btn gray" type="submit">@include('admin.partials.icon', ['name' => 'search']) Filter</button>
</form>
<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Paket</th><th>Berangkat</th><th>Harga</th><th>Closing</th><th>Status</th><th>Waktu</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($packages as $package)
          <tr class="{{ $package->trashed() ? 'is-deleted' : '' }}">
            <td><b>{{ $package->title }}</b><small>{{ $package->typeLabel() }} · {{ $package->duration_days }} hari</small></td>
            <td>{{ $package->departureLine() }}</td>
            <td>{{ $package->formattedPrice() }}<small>{{ $package->seatsLine() }}</small></td>
            <td>{{ (int) $package->sold_pax }} jamaah</td>
            <td><span class="badge {{ $package->status }}">{{ \App\Models\Package::STATUSES[$package->status] ?? $package->status }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $package])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $package,
                'edit' => route('admin.packages.edit', $package),
                'destroy' => route('admin.packages.destroy', $package),
                'restore' => route('admin.packages.restore', $package),
                'confirm' => 'Hapus paket ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">{{ $trashed ? 'Tidak ada paket terhapus.' : 'Belum ada paket.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
