@php
  /** @var list<array{label: string, file_path: string}> $items */
  $heading = $heading ?? 'Itinerary tentatif (contoh)';
  $note = $note ?? 'Contoh itinerary sebelum jadwal resmi diumumkan.';
@endphp
@if($items !== [])
  <div class="haji-itinerary-sample-block">
    <h3 class="haji-itinerary-sample-heading">{{ $heading }}</h3>
    @if($note !== '')
      <p class="itinerary-pdf-note">{{ $note }}</p>
    @endif
    <ul class="itinerary-pdf-list itinerary-pdf-list--sample haji-itinerary-list">
      @foreach($items as $item)
        <li>
          <button type="button"
            class="itinerary-pdf-trigger haji-itinerary-trigger"
            data-pdf="{{ $item['file_path'] }}"
            data-label="{{ $item['label'] }}"
            data-download="{{ \Illuminate\Support\Str::slug($item['label']).'.pdf' }}">
            <span class="haji-itinerary-trigger-copy">
              <span class="haji-itinerary-departure">{{ $item['label'] }}</span>
              <span class="haji-itinerary-hijri">contoh tentatif</span>
            </span>
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
            <i class="bi bi-eye itinerary-pdf-open" aria-hidden="true"></i>
          </button>
        </li>
      @endforeach
    </ul>
  </div>
@endif
