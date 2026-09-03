<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice {{ $transaction->invoice_number }} — {{ $site->name }}</title>
  <link rel="icon" type="image/webp" href="{{ $site->logoUrl }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <style>
    :root {
      --ink: #101828;
      --muted: #667085;
      --line: #e4e7ec;
      --brand: #013180;
      --brand-soft: #e8eef8;
      --gold: #f6881f;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, ui-sans-serif, system-ui, sans-serif;
      color: var(--ink);
      background: #eef2f8;
      -webkit-font-smoothing: antialiased;
    }
    .toolbar {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding: 14px 20px;
      background: #fff;
      border-bottom: 1px solid var(--line);
      position: sticky;
      top: 0;
      z-index: 10;
    }
    .btn {
      border: 0;
      border-radius: 10px;
      padding: 10px 16px;
      font: inherit;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      background: var(--brand);
      color: #fff;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }
    .btn.gray { background: #eef1f6; color: #2d3648; }

    .sheet {
      max-width: 780px;
      margin: 28px auto 40px;
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(16, 24, 40, .08);
    }
    .sheet-top {
      height: 6px;
      background: linear-gradient(90deg, var(--brand) 0%, #1a4fa8 55%, var(--gold) 100%);
    }
    .sheet-body { padding: 32px 36px 28px; }

    .head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 24px;
      margin-bottom: 28px;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--line);
    }
    .brand { display: flex; gap: 14px; align-items: center; min-width: 0; }
    .brand img { height: 48px; width: auto; flex-shrink: 0; }
    .brand-text { min-width: 0; }
    .brand-text h1 { margin: 0; font-size: 18px; font-weight: 700; letter-spacing: -.02em; }
    .brand-text p { margin: 4px 0 0; color: var(--muted); font-size: 12px; line-height: 1.45; max-width: 320px; }

    .invoice-title { text-align: right; flex-shrink: 0; }
    .invoice-title .label {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--brand);
      background: var(--brand-soft);
      padding: 5px 10px;
      border-radius: 999px;
      margin-bottom: 8px;
    }
    .invoice-title h2 {
      margin: 0;
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -.03em;
      color: var(--ink);
    }

    .meta-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .meta-item {
      background: #fafbfd;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px 14px;
    }
    .meta-item span {
      display: block;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .meta-item b { font-size: 14px; font-weight: 600; }

    .party-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 24px;
    }
    .party-box {
      border: 1px solid var(--line);
      border-radius: 12px;
      overflow: hidden;
    }
    .party-box h3 {
      margin: 0;
      padding: 10px 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--muted);
      background: #fafbfd;
      border-bottom: 1px solid var(--line);
    }
    .party-box dl { margin: 0; padding: 12px 14px; display: grid; gap: 8px; }
    .party-row { display: grid; grid-template-columns: 88px 1fr; gap: 8px; font-size: 13px; }
    .party-row dt { margin: 0; color: var(--muted); font-weight: 500; }
    .party-row dd { margin: 0; font-weight: 600; line-height: 1.4; }

    .items { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; margin-bottom: 0; }
    .items table { width: 100%; border-collapse: collapse; }
    .items th {
      text-align: left;
      padding: 11px 16px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: var(--muted);
      background: #fafbfd;
      border-bottom: 1px solid var(--line);
    }
    .items th:last-child,
    .items td:last-child { text-align: right; }
    .items td {
      padding: 14px 16px;
      font-size: 14px;
      border-bottom: 1px solid var(--line);
      vertical-align: middle;
    }
    .items tr:last-child td { border-bottom: 0; }
    .items .desc { font-weight: 600; color: var(--ink); }
    .items .sub { display: block; margin-top: 3px; font-size: 12px; font-weight: 500; color: var(--muted); }
    .type-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      background: var(--brand-soft);
      color: var(--brand);
    }
    .amount { font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .summary {
      display: flex;
      justify-content: flex-end;
      padding: 16px;
      background: linear-gradient(135deg, #f7f9fd 0%, var(--brand-soft) 100%);
      border-top: 1px solid var(--line);
    }
    .summary-box { min-width: 260px; text-align: right; }
    .summary-box span {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-bottom: 4px;
    }
    .summary-box b {
      font-size: 26px;
      font-weight: 700;
      color: var(--brand);
      letter-spacing: -.02em;
      font-variant-numeric: tabular-nums;
    }

    .foot {
      margin: 20px 0 0;
      padding-top: 16px;
      border-top: 1px dashed var(--line);
      font-size: 11.5px;
      color: var(--muted);
      line-height: 1.6;
    }

    @media (max-width: 640px) {
      .sheet-body { padding: 22px 18px; }
      .head { flex-direction: column; }
      .invoice-title { text-align: left; }
      .meta-grid, .party-grid { grid-template-columns: 1fr; }
      .party-row { grid-template-columns: 1fr; gap: 2px; }
    }
    @media print {
      @page { margin: 12mm; }
      body { background: #fff; }
      .toolbar { display: none; }
      .sheet {
        margin: 0;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        max-width: none;
      }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a class="btn gray" href="{{ route('admin.operations.pilgrims.show', $pilgrim) }}">← Kembali</a>
    <button class="btn" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
  </div>

  <article class="sheet">
    <div class="sheet-top"></div>
    <div class="sheet-body">
      <header class="head">
        <div class="brand">
          <img src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
          <div class="brand-text">
            <h1>{{ $site->name }}</h1>
            <p>{{ $site->tagline }}</p>
          </div>
        </div>
        <div class="invoice-title">
          <span class="label">Kwitansi Pembayaran</span>
          <h2>{{ $transaction->invoice_number }}</h2>
        </div>
      </header>

      <div class="meta-grid">
        <div class="meta-item">
          <span>No. Invoice</span>
          <b>{{ $transaction->invoice_number }}</b>
        </div>
        <div class="meta-item">
          <span>Tanggal Invoice</span>
          <b>{{ $transaction->invoice_created_at?->translatedFormat('d M Y') }}</b>
        </div>
        <div class="meta-item">
          <span>Tanggal Bayar</span>
          <b>{{ $transaction->paid_at->translatedFormat('d M Y') }}</b>
        </div>
      </div>

      <div class="party-grid">
        <section class="party-box">
          <h3>Tagihan Kepada</h3>
          <dl>
            <div class="party-row"><dt>Nama</dt><dd>{{ $pilgrim->full_name }}</dd></div>
            @if($pilgrim->phone)
              <div class="party-row"><dt>Telepon</dt><dd>{{ $pilgrim->phone }}</dd></div>
            @endif
            @if($pilgrim->gender)
              <div class="party-row"><dt>Jenis kelamin</dt><dd>{{ $pilgrim->genderLabel() }}</dd></div>
            @endif
          </dl>
        </section>
        <section class="party-box">
          <h3>Detail Keberangkatan</h3>
          <dl>
            <div class="party-row"><dt>Program</dt><dd>{{ $pilgrim->departure?->program_name ?: '—' }}</dd></div>
            <div class="party-row"><dt>Berangkat</dt><dd>{{ $pilgrim->departure?->formattedDepartureDate() ?: '—' }}</dd></div>
            <div class="party-row"><dt>Kamar</dt><dd>{{ $pilgrim->roomTypeLabel() }}@if($pilgrim->room) · {{ $pilgrim->room->room_number }}@endif</dd></div>
          </dl>
        </section>
      </div>

      <div class="items">
        <table>
          <thead>
            <tr>
              <th style="width:50%">Keterangan</th>
              <th style="width:22%">Jenis</th>
              <th style="width:28%">Nominal</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <span class="desc">{{ $transaction->notes ?: 'Pembayaran '.$transaction->typeLabel() }}</span>
                @if($transaction->notes)
                  <span class="sub">{{ $transaction->typeLabel() }}</span>
                @endif
              </td>
              <td><span class="type-badge">{{ $transaction->typeLabel() }}</span></td>
              <td><span class="amount">{{ $transaction->formattedAmount() }}</span></td>
            </tr>
          </tbody>
        </table>
        <div class="summary">
          <div class="summary-box">
            <span>Total Dibayar</span>
            <b>{{ $transaction->formattedAmount() }}</b>
          </div>
        </div>
      </div>

      <p class="foot">
        Dokumen ini diterbitkan otomatis oleh sistem operasi jamaah {{ $site->name }}.
        @if($transaction->author) Dicatat oleh <strong>{{ $transaction->author->name }}</strong>. @endif
        @if($transaction->hasProof()) Bukti transfer tersimpan di arsip internal. @endif
      </p>
    </div>
  </article>
</body>
</html>
