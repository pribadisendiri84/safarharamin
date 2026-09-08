@php
  use App\Support\HajiPlusPage;

  $icon = trim((string) ($icon ?? ''));
  $class = trim((string) ($class ?? ''));
  $biClass = trim('bi '.HajiPlusPage::iconBootstrapClass($icon).' '.$class);
@endphp
@if(HajiPlusPage::iconIsUploaded($icon))
  <img @if($class !== '') class="{{ $class }}" @endif src="{{ $icon }}" alt="" aria-hidden="true">
@elseif($icon !== '')
  <i class="{{ trim($biClass) }}" aria-hidden="true"></i>
@endif
