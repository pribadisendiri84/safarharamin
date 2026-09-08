@php
  /** @var \Illuminate\Support\Collection<int, \App\Models\PackageItinerary|array{label: string, file_path: string}> $items */
  $sample = $sample ?? false;

  $resolveLabel = function ($item): string {
      if (is_array($item)) {
          return trim((string) ($item['label'] ?? '')) ?: 'Contoh itinerary';
      }

      return $item->displayLabel();
  };

  $resolvePath = fn ($item): string => is_array($item) ? (string) ($item['file_path'] ?? '') : (string) $item->file_path;

  $resolveDownload = function ($item) use ($resolveLabel): string {
      if (is_array($item)) {
          $slug = \Illuminate\Support\Str::slug($resolveLabel($item)) ?: 'contoh-itinerary';

          return $slug.'.pdf';
      }

      return $item->downloadFilename();
  };
@endphp
@if($items->isNotEmpty())
  @if(!empty($heading))
    <h2>{{ $heading }}</h2>
  @endif
  @if(!empty($note))
    <p class="itinerary-pdf-note">{{ $note }}</p>
  @endif
  <ul class="itinerary-pdf-list{{ $sample ? ' itinerary-pdf-list--sample' : '' }}">
    @foreach($items as $item)
      <li>
        <button type="button"
          class="itinerary-pdf-trigger"
          data-pdf="{{ $resolvePath($item) }}"
          data-label="{{ $resolveLabel($item) }}"
          data-download="{{ $resolveDownload($item) }}">
          <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
          <span>{{ $resolveLabel($item) }}@if($sample) <em class="itinerary-sample-tag">contoh</em>@endif</span>
          <i class="bi bi-eye itinerary-pdf-open" aria-hidden="true"></i>
        </button>
      </li>
    @endforeach
  </ul>
@endif
