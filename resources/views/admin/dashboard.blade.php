@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')
@php
  $rp = fn ($n) => 'Rp'.number_format((int) $n, 0, ',', '.');
@endphp
<div class="page-head">
  <div>
    <h1>Dashboard</h1>
    <p class="sub">Ringkasan follow-up pengajuan dan closing paket.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.inquiries.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah pengajuan</a>
    @can('manage-catalog')
      <a class="btn gray" href="{{ route('admin.packages.create') }}">@include('admin.partials.icon', ['name' => 'plane']) Tambah paket</a>
    @endcan
  </div>
</div>

<div class="summary">
  <span class="bubble">@include('admin.partials.icon', ['name' => 'check'])</span>
  <div>
    <b>{{ $soldCount }} closing tercatat</b>
    <p>{{ $soldPax }} jamaah closing · {{ $rp($soldAmount) }}. {{ $pipeline }} pengajuan masih perlu follow-up.</p>
  </div>
</div>

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'plane'])</span>
    <div>
      <div class="stat-label">Paket tayang</div>
      <div class="stat-value">{{ $published }}</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-gold">@include('admin.partials.icon', ['name' => 'inbox'])</span>
    <div>
      <div class="stat-label">Perlu follow-up</div>
      <div class="stat-value">{{ $pipeline }}</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'users'])</span>
    <div>
      <div class="stat-label">Jamaah closing</div>
      <div class="stat-value">{{ $soldPax }}</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'check'])</span>
    <div>
      <div class="stat-label">Nilai closing</div>
      <div class="stat-value money-value">{{ $rp($soldAmount) }}</div>
    </div>
  </div>
</div>

@if($traffic)
<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'chart']) Trafik website</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Pengunjung hari ini</th>
          <th>Page view 7 hari</th>
          <th>Klik WhatsApp 7 hari</th>
          <th>Rasio WA</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><b>{{ $traffic['visitors_today'] }}</b></td>
          <td>{{ $traffic['views_7d'] }}</td>
          <td>{{ $traffic['wa_clicks_7d'] }}</td>
          <td>{{ $traffic['wa_rate_7d'] }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="form-pad">
    <a class="btn gray" href="{{ route('admin.traffic.index') }}">Lihat trafik lengkap</a>
  </div>
</div>
@endif
@if($funnel)
<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'chart']) Funnel website vs tim</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Sumber</th>
          <th>Pengajuan</th>
          <th>Closing</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><b>Daftar lewat website</b><small>Form /daftar</small></td>
          <td>{{ $funnel['web_daftar'] }}</td>
          <td>{{ $funnel['web_daftar_closing'] }}</td>
        </tr>
        <tr>
          <td><b>Tanya WA lewat website</b><small>Form tanya paket</small></td>
          <td>{{ $funnel['web_tanya'] }}</td>
          <td>{{ $funnel['web_tanya_closing'] }}</td>
        </tr>
        <tr>
          <td><b>Input tim</b><small>Pengajuan yang dicatat staf/admin</small></td>
          <td>{{ $funnel['tim'] }}</td>
          <td>{{ $funnel['tim_closing'] }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="two">
  <section class="panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'inbox']) Perlu follow-up</div>
    <ul class="plain">
      @forelse($latestInquiries as $inquiry)
        <li>
          <a href="{{ route('admin.inquiries.show', $inquiry) }}">{{ $inquiry->name }}</a>
          <small>@if(auth()->user()->canSeeLeadSources())PIC {{ $inquiry->picName() }} · @endif{{ $inquiry->statusLabel() }} · {{ $inquiry->kindLabel() }}@if($inquiry->package) · {{ $inquiry->package->title }}@endif</small>
        </li>
      @empty
        <li class="empty">Tidak ada pengajuan yang menunggu follow-up.</li>
      @endforelse
    </ul>
  </section>
  <section class="panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'check']) Closing terbaru</div>
    <ul class="plain">
      @forelse($latestSales as $sale)
        <li>
          <a href="{{ route('admin.inquiries.show', $sale) }}">{{ $sale->name }}</a>
          <small>
            @if(auth()->user()->canSeeLeadSources())PIC {{ $sale->picName() }} · @endif{{ $sale->soldPaxCount() }} jamaah · {{ $sale->formattedSoldAmount() }}
            @if($sale->package) · {{ $sale->package->title }}@endif
          </small>
        </li>
      @empty
        <li class="empty">Belum ada closing yang dicatat.</li>
      @endforelse
    </ul>
  </section>
</div>
@endsection
