@extends('layouts.admin')

@section('title', 'Sync Schedule')
@section('content')
<div class="page-head">
  <div>
    <h1>Sync Schedule</h1>
    <p class="sub">Atur jadwal sync otomatis dari Arminareka. Default mode: Manual Approval.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.price-list.index') }}">Price List</a>
    <a class="btn gray" href="{{ route('admin.price-sync.index') }}">Riwayat Sync</a>
  </div>
</div>

<div class="summary">
  <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'calendar'])</span>
  <div>
    <b>{{ $summary }}</b>
    <p>Next run: {{ $nextRun?->translatedFormat('d M Y H:i') ?? '—' }} · Last run: {{ filled($schedule['last_run_at']) ? \Carbon\Carbon::parse($schedule['last_run_at'])->translatedFormat('d M Y H:i') : '—' }}</p>
  </div>
</div>

<form class="form panel form-pad form-narrow" method="post" action="{{ route('admin.price-sync.schedule.update') }}">
  @csrf
  @method('PUT')

  <label class="check">
    <input type="checkbox" name="enabled" value="1" @checked($schedule['enabled'])> Enable Scheduler
  </label>

  <label>Frequency
    <select name="frequency">
      <option value="hourly" @selected($schedule['frequency'] === 'hourly')>Hourly</option>
      <option value="daily" @selected($schedule['frequency'] === 'daily')>Daily</option>
      <option value="weekly" @selected($schedule['frequency'] === 'weekly')>Weekly</option>
    </select>
  </label>

  <label>Jam
    <input type="time" name="time" value="{{ $schedule['time'] }}">
  </label>

  <fieldset>
    <legend>Hari (Weekly)</legend>
    @php
      $dayOptions = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    @endphp
    @foreach($dayOptions as $value => $label)
      <label class="check">
        <input type="checkbox" name="days[]" value="{{ $value }}" @checked(in_array($value, $schedule['days'], true))> {{ $label }}
      </label>
    @endforeach
  </fieldset>

  <label>Timezone
    <input type="text" name="timezone" value="{{ $schedule['timezone'] }}" placeholder="Asia/Jakarta">
  </label>

  <label>Update Mode
    <select name="update_mode">
      <option value="manual_approval" @selected($schedule['update_mode'] === 'manual_approval')>Manual Approval (default)</option>
      <option value="auto_update" @selected($schedule['update_mode'] === 'auto_update')>Auto Update</option>
    </select>
  </label>

  <button class="btn" type="submit">Simpan jadwal</button>
</form>
@endsection
