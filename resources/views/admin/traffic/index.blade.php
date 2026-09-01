@extends('layouts.admin')

@section('title', 'Trafik')
@section('content')
<div class="page-head">
  <div>
    <h1>Trafik website</h1>
    <p class="sub">Pengunjung anonim, sumber kunjungan, dan klik tombol WhatsApp. Nomor HP hanya tercatat jika jamaah isi form.</p>
  </div>
</div>

<div class="stat-row">
  @foreach($periods as $period)
    <div class="stat">
      <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'users'])</span>
      <div>
        <div class="stat-label">Pengunjung {{ strtolower($period['label']) }}</div>
        <div class="stat-value">{{ $period['visitors'] }}</div>
        <small>{{ $period['views'] }} page view</small>
      </div>
    </div>
  @endforeach
</div>

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'phone'])</span>
    <div>
      <div class="stat-label">Klik WhatsApp 7 hari</div>
      <div class="stat-value">{{ $periods['week']['wa_clicks'] }}</div>
      <small>{{ $periods['week']['wa_visitors'] }} pengunjung · rasio {{ $periods['week']['wa_rate'] }}</small>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-gold">@include('admin.partials.icon', ['name' => 'inbox'])</span>
    <div>
      <div class="stat-label">Pengajuan website 7 hari</div>
      <div class="stat-value">{{ $periods['week']['leads'] }}</div>
      <small>Rasio dari pengunjung {{ $periods['week']['lead_rate'] }}</small>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'chart'])</span>
    <div>
      <div class="stat-label">Klik WhatsApp 30 hari</div>
      <div class="stat-value">{{ $periods['month']['wa_clicks'] }}</div>
      <small>{{ $periods['month']['wa_rate'] }} pengunjung klik WA</small>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-rose">@include('admin.partials.icon', ['name' => 'inbox'])</span>
    <div>
      <div class="stat-label">Pengajuan website 30 hari</div>
      <div class="stat-value">{{ $periods['month']['leads'] }}</div>
      <small>Rasio {{ $periods['month']['lead_rate'] }}</small>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'phone']) Klik WhatsApp 7 hari</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tombol</th>
          <th>Klik</th>
        </tr>
      </thead>
      <tbody>
        @foreach($waByPlacement as $row)
          <tr>
            <td><b>{{ $row->label }}</b></td>
            <td>{{ $row->clicks }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="two">
  <section class="panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'external']) Sumber 30 hari</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Sumber</th>
            <th>Pengunjung</th>
            <th>Page view</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sources as $row)
            <tr>
              <td><b>{{ $row->source }}</b></td>
              <td>{{ $row->visitors }}</td>
              <td>{{ $row->views }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="empty-state">Belum ada kunjungan tercatat.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
  <section class="panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'search']) Halaman 30 hari</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Halaman</th>
            <th>Pengunjung</th>
            <th>Page view</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pages as $row)
            <tr>
              <td><b>{{ $row->path }}</b></td>
              <td>{{ $row->visitors }}</td>
              <td>{{ $row->views }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="empty-state">Belum ada halaman tercatat.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>

<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'calendar']) Klik WhatsApp per hari</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Klik</th>
          <th>Pengunjung</th>
        </tr>
      </thead>
      <tbody>
        @forelse($dailyClicks as $row)
          <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($row->day)->translatedFormat('d M Y') }}</td>
            <td>{{ $row->clicks }}</td>
            <td>{{ $row->visitors }}</td>
          </tr>
        @empty
          <tr><td colspan="3" class="empty-state">Belum ada klik WhatsApp.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
