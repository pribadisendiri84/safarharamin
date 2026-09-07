@if($item->isVideo())
  @include('partials.gallery-item-video', ['item' => $item])
@else
  @include('partials.gallery-item-photo', ['item' => $item])
@endif
