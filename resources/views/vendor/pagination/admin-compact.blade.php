@if ($paginator->hasPages())
  <nav class="pagination-compact" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
      <span class="page-item disabled" aria-disabled="true">&lsaquo;</span>
    @else
      <a class="page-item" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&lsaquo;</a>
    @endif

    @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
      @if ($page == $paginator->currentPage())
        <span class="page-item active" aria-current="page">{{ $page }}</span>
      @else
        <a class="page-item" href="{{ $url }}">{{ $page }}</a>
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <a class="page-item" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&rsaquo;</a>
    @else
      <span class="page-item disabled" aria-disabled="true">&rsaquo;</span>
    @endif
  </nav>
@endif
