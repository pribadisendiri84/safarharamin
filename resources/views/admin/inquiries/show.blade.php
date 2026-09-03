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
      <a class="btn ghost" href="{{ route('admin.inquiries.edit', $inquiry) }}">Edit data</a>
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
          <input type="text" class="js-rupiah" name="sold_amount" value="{{ old('sold_amount', $inquiry->sold_amount) }}" placeholder="Kosong = harga paket × jamaah">
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

@if($inquiry->isSold() && ! $inquiry->trashed())
  <section class="panel import-pilgrim-panel">
    <div class="panel-head">@include('admin.partials.icon', ['name' => 'users']) Pindah ke Jamaah</div>
    @if($inquiry->pilgrimsImported())
      <div class="form-pad">
        <p class="sub">Pengajuan closing ini sudah dipindah ke modul jamaah ({{ $inquiry->pilgrims->count() }} jamaah).</p>
        <div class="actions">
          <a class="btn gray" href="{{ route('admin.operations.pilgrims.index', ['departure_id' => $inquiry->pilgrims->first()?->departure_id]) }}">Lihat jamaah</a>
          @if($inquiry->pilgrims->first()?->departure)
            <a class="btn" href="{{ route('admin.operations.grouping.index', $inquiry->pilgrims->first()->departure) }}">Grouping room</a>
          @endif
        </div>
      </div>
    @elseif($departures->isEmpty())
      <div class="form-pad">
        <p class="sub">Belum ada keberangkatan. Buat dulu di menu <a href="{{ route('admin.operations.departures.create') }}">Keberangkatan</a>, lalu kembali ke sini untuk memindahkan jamaah closing.</p>
      </div>
    @else
      <form class="form form-pad import-pilgrim-form" method="post" action="{{ route('admin.inquiries.import-pilgrims', $inquiry) }}">
        @csrf
        <p class="sub import-pilgrim-intro">
          Pindahkan {{ $inquiry->soldPaxCount() }} jamaah closing ke operasi. Harga per jamaah: <b>Rp {{ number_format((int) round((int) $inquiry->sold_amount / max(1, $inquiry->soldPaxCount())), 0, ',', '.') }}</b>.
          @unless($matchedDepartures)
            Paket pengajuan belum punya keberangkatan khusus — pilih keberangkatan yang sesuai.
          @endunless
        </p>
        <div class="row2">
          <label>Keberangkatan
            <select name="departure_id" id="import-departure" required class="js-searchable" data-placeholder="Pilih keberangkatan…">
              <option value="">Pilih keberangkatan</option>
              @foreach($departures as $departure)
                <option value="{{ $departure->id }}" data-kind="{{ $departure->program_kind }}" @selected((string) old('departure_id') === (string) $departure->id)>
                  {{ $departure->program_name }} · {{ $departure->formattedDepartureDate() }}
                </option>
              @endforeach
            </select>
          </label>
          <label>Tipe kamar
            <select name="room_type" id="import-room-type" required>
              @foreach(\App\Enums\RoomType::labels() as $key => $label)
                <option value="{{ $key }}" data-haji-only="{{ $key === 'double_plus' ? '1' : '0' }}" @selected(old('room_type', 'quad') === $key)>{{ $label }}</option>
              @endforeach
            </select>
          </label>
        </div>
        <div class="import-name-list">
          <span class="import-name-label">Nama jamaah ({{ $inquiry->soldPaxCount() }})</span>
          @for($i = 0; $i < $inquiry->soldPaxCount(); $i++)
            <label>Nama jamaah {{ $i + 1 }}
              <input
                type="text"
                name="names[]"
                value="{{ old('names.'.$i, $i === 0 ? $inquiry->name : '') }}"
                @required(true)
                placeholder="{{ $i === 0 ? 'Nama kontak pengajuan' : 'Nama jamaah '.($i + 1) }}"
              >
            </label>
          @endfor
        </div>
        <button class="btn" type="submit">Pindah ke Jamaah</button>
      </form>
    @endif
  </section>
@endif
@endsection

@push('scripts')
<script>
(function () {
  var departureSelect = document.getElementById('import-departure');
  var roomSelect = document.getElementById('import-room-type');
  if (!departureSelect || !roomSelect) return;

  var hajiIds = @json($departures->where('program_kind', 'haji')->pluck('id')->values());

  function syncRoomTypes() {
    var id = parseInt(departureSelect.value || '0', 10);
    var isHaji = hajiIds.includes(id);
    var selected = roomSelect.value;

    Array.from(roomSelect.options).forEach(function (option) {
      if (option.dataset.hajiOnly === '1') {
        option.hidden = !isHaji;
        option.disabled = !isHaji;
      }
    });

    var active = roomSelect.querySelector('option[value="' + selected + '"]');
    if (active && (active.disabled || active.hidden)) {
      var fallback = Array.from(roomSelect.options).find(function (option) {
        return !option.disabled && !option.hidden;
      });
      if (fallback) roomSelect.value = fallback.value;
    }
  }

  departureSelect.addEventListener('change', syncRoomTypes);
  syncRoomTypes();
})();
</script>
@endpush
