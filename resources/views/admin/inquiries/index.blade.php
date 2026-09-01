@extends('layouts.admin')

@section('title', 'Pengajuan')
@section('content')
<div class="page-head">
  <div>
    <h1>Pengajuan</h1>
    <p class="sub">{{ auth()->user()->isStaff() ? 'Follow up jamaah Anda, lalu closing jika jadi berangkat.' : 'Follow up pendaftaran, catat PIC, lalu closing jika jamaah jadi berangkat.' }}</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.inquiries.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah pengajuan</a>
  </div>
</div>
@include('admin.partials.scope-tabs')
<div class="tabs">
  <a class="{{ request('status') === null || request('status') === '' ? 'active' : '' }}" href="{{ request()->fullUrlWithoutQuery(['status', 'page']) }}">
    Semua
  </a>
  @foreach(\App\Models\Inquiry::STATUSES as $key => $label)
    @if($key === 'selesai' && ! ($statusCounts['selesai'] ?? 0))
      @continue
    @endif
    <a class="{{ request('status') === $key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}">
      {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
    </a>
  @endforeach
</div>
@if(auth()->user()->canSeeLeadSources())
<form class="filter-bar" method="get">
  @if($trashed)<input type="hidden" name="trashed" value="1">@endif
  @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
  <select name="source">
    <option value="">Semua sumber</option>
    @foreach(\App\Models\Inquiry::SOURCES as $key => $label)
      <option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="pic_id" class="js-searchable" data-placeholder="Semua PIC">
    <option value="">Semua PIC</option>
    @foreach($pics as $pic)
      <option value="{{ $pic->id }}" @selected((string) request('pic_id') === (string) $pic->id)>{{ $pic->name }}</option>
    @endforeach
  </select>
  <button class="btn gray" type="submit">Filter</button>
</form>
@endif
<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Jenis</th>
          @if(auth()->user()->canSeeLeadSources())<th>PIC</th>@endif
          <th>Kontak</th>
          <th>Detail</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($inquiries as $inquiry)
          <tr class="{{ $inquiry->trashed() ? 'is-deleted' : '' }}">
            <td>
              <a href="{{ route('admin.inquiries.show', $inquiry) }}"><b>{{ $inquiry->name }}</b></a>
              @if(auth()->user()->canSeeLeadSources())
                <small>{{ $inquiry->sourceLabel() }}</small>
              @endif
            </td>
            <td>{{ $inquiry->kindLabel() }}</td>
            @if(auth()->user()->canSeeLeadSources())
              <td>{{ $inquiry->picName() }}</td>
            @endif
            <td>{{ $inquiry->phone }}<small>{{ $inquiry->email }}</small></td>
            <td>
              @if($inquiry->package && ! $inquiry->package->trashed())
                <a href="{{ route('packages.show', $inquiry->package) }}" target="_blank">{{ $inquiry->package->title }}</a>
              @elseif($inquiry->package)
                {{ $inquiry->package->title }}<small>Paket terhapus</small>
              @endif
              @if($inquiry->isSold())
                <small>{{ $inquiry->soldPaxCount() }} jamaah · {{ $inquiry->formattedSoldAmount() }}</small>
              @elseif($inquiry->pax)
                <small>{{ $inquiry->pax }} jamaah</small>
              @endif
              @if($inquiry->notes)<small>{{ $inquiry->notes }}</small>@endif
            </td>
            <td><span class="badge {{ $inquiry->status }}">{{ $inquiry->statusLabel() }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $inquiry])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $inquiry,
                'edit' => route('admin.inquiries.show', $inquiry),
                'editLabel' => 'Follow up',
                'showWhenTrashed' => true,
                'destroy' => auth()->user()->can('manage-catalog') ? route('admin.inquiries.destroy', $inquiry) : null,
                'restore' => route('admin.inquiries.restore', $inquiry),
                'confirm' => 'Hapus pengajuan ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ auth()->user()->canSeeLeadSources() ? 8 : 7 }}" class="empty-state">{{ $trashed ? 'Tidak ada pengajuan terhapus.' : 'Belum ada pengajuan.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
