@extends('layouts.admin')

@section('title', $pilgrim->full_name)
@section('content')
@php
  $isHaji = $pilgrim->departure?->isHaji() ?? false;
  $departure = $pilgrim->departure;
  $transactionTypes = \App\Models\PilgrimTransaction::typesFor($isHaji);
@endphp
<div class="page-head">
  <div>
    <p class="eyebrow">{{ $pilgrim->departure?->program_name }}</p>
    <h1>{{ $pilgrim->full_name }}</h1>
    <p class="sub">{{ $pilgrim->roomTypeLabel() }} · {{ $pilgrim->groupingStatusLabel() }}@if($pilgrim->room) · {{ $pilgrim->room->room_number }}@endif</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.operations.pilgrims.edit', $pilgrim) }}">Edit</a>
    @if($pilgrim->departure)
      <a class="btn gray" href="{{ route('admin.operations.departures.edit', $pilgrim->departure) }}">Edit keberangkatan</a>
      <a class="btn" href="{{ route('admin.operations.grouping.index', [$pilgrim->departure, 'tab' => $pilgrim->room_type]) }}">Grouping room</a>
    @endif
  </div>
</div>

<div class="ops-detail-grid">
  <section class="panel form-pad ops-panel">
    <h2>Profil jamaah</h2>
    <dl class="ops-spec-grid">
      <div class="ops-spec-row"><dt>HP</dt><dd>{{ $pilgrim->phone ?: '—' }}</dd></div>
      <div class="ops-spec-row"><dt>Jenis kelamin</dt><dd>{{ $pilgrim->genderLabel() }}</dd></div>
      <div class="ops-spec-row"><dt>Keberangkatan</dt><dd>{{ $pilgrim->departure?->program_name ?: '—' }}</dd></div>
      <div class="ops-spec-row"><dt>Tipe kamar</dt><dd>{{ $pilgrim->roomTypeLabel() }}</dd></div>
      <div class="ops-spec-row"><dt>Room</dt><dd>{{ $pilgrim->room?->room_number ?: 'Belum group' }}</dd></div>
      @if($isHaji)
        <div class="ops-spec-row"><dt>ID / catatan haji</dt><dd>{{ $pilgrim->haji_registration_id ?: '—' }}</dd></div>
        <div class="ops-spec-row"><dt>Nomor porsi</dt><dd>{{ $pilgrim->haji_portion_number ?: '—' }}</dd></div>
      @endif
      @if($pilgrim->notes)
        <div class="ops-spec-row"><dt>Catatan</dt><dd>{{ $pilgrim->notes }}</dd></div>
      @endif
    </dl>
  </section>

  @if($departure)
  <section class="panel form-pad ops-panel">
    <div class="departure-info-head">
      <h2>Program keberangkatan</h2>
      <a class="btn gray sm" href="{{ route('admin.operations.departures.edit', $departure) }}">Ubah</a>
    </div>
    <p class="sub">Hotel dan maskapai sama untuk semua jamaah di {{ $departure->program_name }}.</p>
    <dl class="ops-spec-grid">
      <div class="ops-spec-row"><dt>Maskapai</dt><dd>{{ $departure->airline ?: '—' }}</dd></div>
      <div class="ops-spec-row"><dt>Penerbangan</dt><dd>{{ $departure->flight_number ?: '—' }}</dd></div>
      <div class="ops-spec-row"><dt>Hotel Makkah</dt><dd>{{ $departure->hotel_makkah ?: '—' }}</dd></div>
      <div class="ops-spec-row"><dt>Hotel Madinah</dt><dd>{{ $departure->hotel_madinah ?: '—' }}</dd></div>
      @if($isHaji)
        <div class="ops-spec-row"><dt>Hotel Transit</dt><dd>{{ $departure->hotel_transit ?: '—' }}</dd></div>
        <div class="ops-spec-row"><dt>Maktab</dt><dd>{{ $departure->hotel_maktab ?: '—' }}</dd></div>
      @endif
    </dl>
  </section>
  @endif

  <section class="panel form-pad ops-panel">
    <h2>Pembukuan</h2>

    <div class="ops-pay-summary">
      <div class="ops-pay-stat">
        <span>Harga paket</span>
        <b>{{ $pilgrim->formattedPackagePrice() }}</b>
      </div>
      <div class="ops-pay-stat highlight">
        <span>Total dibayar</span>
        <b>{{ $pilgrim->formattedPaidAmount() }}</b>
      </div>
      <div class="ops-pay-stat {{ $pilgrim->hasOverpayment() ? 'pay-over-stat' : ($pilgrim->remainingBalance() > 0 ? 'warn' : 'ok') }}">
        <span>{{ $pilgrim->hasOverpayment() ? 'Lebih bayar' : 'Sisa' }}</span>
        <b>{{ $pilgrim->hasOverpayment() ? $pilgrim->formattedOverpayment() : $pilgrim->formattedRemainingBalance() }}</b>
      </div>
    </div>

    @if($pilgrim->hasOverpayment())
      <p class="alert warn pay-over-note">Pembayaran melebihi harga paket sebesar {{ $pilgrim->formattedOverpayment() }}. Periksa penyesuaian harga atau catat refund.</p>
    @endif

    <dl class="ops-spec-grid ops-spec-grid--compact">
      <div class="ops-spec-row"><dt>Tanggal DP</dt><dd>{{ $pilgrim->dp_date?->translatedFormat('d M Y') ?: '—' }}</dd></div>
      <div class="ops-spec-row"><dt>Tanggal pelunasan</dt><dd>{{ $pilgrim->settlement_date?->translatedFormat('d M Y') ?: '—' }}</dd></div>
    </dl>
  </section>
</div>

<section class="panel form-pad ops-panel">
  <h2>Catat transaksi</h2>
  <form class="ops-transaction-form ops-transaction-form--card" method="post" action="{{ route('admin.operations.pilgrims.transactions.store', $pilgrim) }}" enctype="multipart/form-data">
    @csrf
    <div class="ops-transaction-fields">
      <label>Jenis
        <select name="type" required>
          @foreach($transactionTypes as $key => $label)
            <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Nominal (Rp)
        <input type="text" class="js-rupiah" name="amount" value="{{ old('amount') }}" required placeholder="0">
      </label>
      <label>Tanggal bayar
        <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required>
      </label>
      <label>Catatan
        <input name="notes" value="{{ old('notes') }}" placeholder="Opsional · untuk Lain-lain: vaksin, handling, dll.">
      </label>
    </div>
    <label class="ops-proof-upload">Bukti pembayaran
      <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf">
      <span class="sub">JPG, PNG, WEBP, atau PDF · maks. 5 MB</span>
    </label>
    <button class="btn" type="submit">Simpan transaksi</button>
  </form>
</section>

<section class="panel form-pad ops-panel">
  <h2>Riwayat transaksi</h2>
  <div class="table-wrap">
    <table class="tx-history-table">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Jenis</th>
          <th>Nominal</th>
          <th>Catatan</th>
          <th>Bukti</th>
          <th>Oleh</th>
          <th class="col-tx-actions"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($pilgrim->transactions as $transaction)
          <tr>
            <td>{{ $transaction->paid_at->translatedFormat('d M Y') }}</td>
            <td><span class="badge tx-{{ $transaction->type }}">{{ $transaction->typeLabel() }}</span></td>
            <td><b>{{ $transaction->formattedAmount() }}</b></td>
            <td>
              @if($transaction->notes)
                {{ $transaction->notes }}
              @else
                <span class="muted">—</span>
              @endif
              @if($transaction->hasInvoice())
                <small class="tx-invoice-no">{{ $transaction->invoice_number }}</small>
              @endif
            </td>
            <td>
              @if($transaction->hasProof())
                <a class="btn gray sm" href="{{ $transaction->proofUrl() }}" target="_blank" rel="noopener">Lihat</a>
              @else
                <span class="muted">—</span>
              @endif
            </td>
            <td>{{ $transaction->author?->name ?: '—' }}</td>
            <td class="col-tx-actions">
              <div class="tx-row-actions">
                <a class="btn gray sm" href="{{ route('admin.operations.pilgrims.transactions.invoice.show', [$pilgrim, $transaction]) }}" target="_blank" rel="noopener" title="Cetak invoice">Print</a>
                <form method="post" action="{{ route('admin.operations.pilgrims.transactions.destroy', [$pilgrim, $transaction]) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                  @csrf @method('DELETE')
                  <button class="btn gray sm danger" type="submit">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-cell">Belum ada transaksi.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
