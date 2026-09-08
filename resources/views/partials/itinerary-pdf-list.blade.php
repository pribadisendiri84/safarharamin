@php
  /** @var \Illuminate\Support\Collection<int, \App\Models\HajiPageItinerary|\App\Models\PackageItinerary> $items */
@endphp
@if($items->isNotEmpty())
  @if(!empty($heading))
    <h2>{{ $heading }}</h2>
  @endif
  @if(!empty($note))
    <p class="itinerary-pdf-note">{{ $note }}</p>
  @endif
  <ul class="itinerary-pdf-list">
    @foreach($items as $item)
      <li>
        <button type="button"
          class="itinerary-pdf-trigger"
          data-pdf="{{ $item->file_path }}"
          data-label="{{ $item->displayLabel() }}"
          data-download="{{ $item->downloadFilename() }}">
          <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
          <span>{{ $item->displayLabel() }}</span>
          <i class="bi bi-eye itinerary-pdf-open" aria-hidden="true"></i>
        </button>
      </li>
    @endforeach
  </ul>
@endif
