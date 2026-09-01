@extends('layouts.admin')

@section('title', 'Follow up '.$inquiry->name)
@section('content')
<div class="page-head">
  <div>
    <h1>{{ $inquiry->name }}</h1>
    <p class="sub">{{ $inquiry->kindLabel() }}@if(auth()->user()->canSeeLeadSources()) · {{ $inquiry->sourceLabel() }} · PIC {{ $inquiry->picName() }}@endif · {{ $inquiry->phone }}@if($inquiry->email) · {{ $inquiry->email }}@endif</p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ route('admin.inquiries.index') }}">Kembali</a>
    @unless($inquiry->trashed())
      <a class="btn gray" href="{{ $inquiry->whatsappUrl() }}" target="_blank">@include('admin.partials.icon', ['name' => 'phone']) Chat WhatsApp</a>
    @endunless
  </div>
</div>

<div class="summary">
  <span class="bubble">@include('admin.partials.icon', ['name' => $inquiry->isSold() ? 'check' : 'inbox'])</span>
  <div>
    <b>{{ $inquiry->statusLabel() }}@if($inquiry->isSold()) · {{ $inquiry->formattedSoldAmount() }}@endif</b>
    <p>
      @if($inquiry->package)
        Paket {{ $inquiry->package->title }}.
      @else
        Belum terhubung ke paket.
      @endif
      @if(auth()->user()->canSeeLeadSources()) PIC: {{ $inquiry->picName() }}. @endif
      @if($inquiry->pax) Minat {{ $inquiry->pax }} jamaah. @endif
      @if($inquiry->notes) Catatan jamaah: {{ $inquiry->notes }} @endif
    </p>
  </div>
</div>

<div class="two">
  <section class="panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'check']) Status &amp; closing</div>
    @unless($inquiry->trashed())
    <form class="form form-pad" method="post" action="{{ route('admin.inquiries.update', $inquiry) }}">
      @csrf
      @method('PUT')
      @if(auth()->user()->canSeeLeadSources())
      <label>PIC
        <select name="pic_id" class="js-searchable" data-placeholder="Pilih PIC…">
          <option value="">Belum ada PIC</option>
          @foreach($pics as $pic)
            <option value="{{ $pic->id }}" @selected((string) old('pic_id', $inquiry->pic_id) === (string) $pic->id)>{{ $pic->name }}</option>
          @endforeach
        </select>
      </label>
      @endif
      <label>Status
        <select name="status" required>
          @foreach(\App\Models\Inquiry::STATUSES as $key => $label)
            @if($key === 'selesai' && $inquiry->status !== 'selesai')
              @continue
            @endif
            <option value="{{ $key }}" @selected(old('status', $inquiry->status) === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Paket
        <select name="package_id">
          <option value="">Pilih paket</option>
          @foreach($packages as $package)
            <option value="{{ $package->id }}" @selected((string) old('package_id', $inquiry->package_id) === (string) $package->id)>
              {{ $package->title }}{{ $package->trashed() ? ' (terhapus)' : '' }}
            </option>
          @endforeach
        </select>
      </label>
      <div class="row2">
        <label>Jumlah jamaah
          <input type="number" name="sold_pax" min="1" max="80" value="{{ old('sold_pax', $inquiry->sold_pax ?? $inquiry->pax ?? 1) }}">
        </label>
        <label>Nilai closing (Rp)
          <input type="number" name="sold_amount" min="0" value="{{ old('sold_amount', $inquiry->sold_amount) }}" placeholder="Kosong = harga paket × jamaah">
        </label>
      </div>
      <label>Tanggal closing
        <input type="date" name="closed_at" value="{{ old('closed_at', optional($inquiry->closed_at)->format('Y-m-d')) }}">
      </label>
      <p class="sub">Pilih <b>Closing</b> jika jamaah jadi berangkat (biasanya sudah DP). Seat paket akan berkurang otomatis. <b>Batal</b> untuk yang tidak jadi.</p>
      <button class="btn" type="submit">Simpan</button>
    </form>
    @else
      <div class="form-pad">
        <p class="sub">Pengajuan ini sudah dihapus. Pulihkan dulu untuk mengubah status.</p>
      </div>
    @endunless
  </section>

  <section class="panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'quote']) Catatan follow-up</div>
    @unless($inquiry->trashed())
    <form class="form form-pad" method="post" action="{{ route('admin.inquiries.notes.store', $inquiry) }}">
      @csrf
      <label>Catatan baru
        <textarea name="body" rows="3" required placeholder="Contoh: Sudah WA, menunggu DP 2 jamaah.">{{ old('body') }}</textarea>
      </label>
      <button class="btn gray" type="submit">Simpan catatan</button>
    </form>
    @endunless
    <ul class="plain note-list">
      @forelse($inquiry->followUps as $note)
        <li>
          <b>{{ $note->author?->name ?? 'Staf' }}</b>
          <small>{{ $note->created_at?->format('d M Y H:i') }}</small>
          <p>{{ $note->body }}</p>
        </li>
      @empty
        <li class="empty">Belum ada catatan follow-up.</li>
      @endforelse
    </ul>
  </section>
</div>
@endsection
