@php
  /** @var \Illuminate\Support\Collection<int, \App\Models\HajiPageItinerary> $items */
@endphp
@if($items->isNotEmpty())
  <h2 class="haji-itinerary-heading">Itinerary</h2>
  <ul class="itinerary-pdf-list haji-itinerary-list">
    @foreach($items as $item)
      <li>
        <button type="button"
          class="itinerary-pdf-trigger haji-itinerary-trigger"
          data-pdf="{{ $item->file_path }}"
          data-label="{{ $item->displayLabel() }}"
          data-download="{{ $item->downloadFilename() }}">
          <span class="haji-itinerary-trigger-copy">
            <span class="haji-itinerary-departure">Keberangkatan {{ $item->departureLabel() }}</span>
            @if($item->hijriLabel() !== '')
              <span class="haji-itinerary-hijri">{{ $item->hijriLabel() }}</span>
            @endif
          </span>
          <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
          <i class="bi bi-eye itinerary-pdf-open" aria-hidden="true"></i>
        </button>
      </li>
    @endforeach
  </ul>
@endif
