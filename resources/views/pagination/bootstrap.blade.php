{{-- Pagination bergaya Bootstrap -- dipakai EKSPLISIT lewat
     $items->links('pagination.bootstrap') di halaman yang sudah
     dimigrasikan, supaya halaman LAMA (Tailwind) tidak ikut terpengaruh
     (mereka tetap pakai pagination bawaan Laravel). --}}
@if ($paginator->hasPages())
  <nav aria-label="Navigasi Halaman">
    <ul class="pagination mb-0 d-flex align-items-center gap-1" style="list-style:none;padding:0">

      {{-- Sebelumnya --}}
      @if ($paginator->onFirstPage())
        <li class="page-item disabled">
          <span class="page-link btn btn-outline-secondary btn-sm">&laquo;</span>
        </li>
      @else
        <li class="page-item">
          <a class="page-link btn btn-outline-secondary btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
        </li>
      @endif

      {{-- Nomor halaman --}}
      @foreach ($elements as $element)
        @if (is_string($element))
          <li class="page-item disabled"><span class="page-link btn btn-outline-secondary btn-sm">{{ $element }}</span></li>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <li class="page-item active"><span class="page-link btn btn-primary btn-sm">{{ $page }}</span></li>
            @else
              <li class="page-item"><a class="page-link btn btn-outline-secondary btn-sm" href="{{ $url }}">{{ $page }}</a></li>
            @endif
          @endforeach
        @endif
      @endforeach

      {{-- Berikutnya --}}
      @if ($paginator->hasMorePages())
        <li class="page-item">
          <a class="page-link btn btn-outline-secondary btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
        </li>
      @else
        <li class="page-item disabled">
          <span class="page-link btn btn-outline-secondary btn-sm">&raquo;</span>
        </li>
      @endif
    </ul>
  </nav>
@endif
