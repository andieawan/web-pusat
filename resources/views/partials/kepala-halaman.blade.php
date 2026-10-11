<section class="kepala-hal">
    <div class="container">
        <div>
            <small><a href="{{ route('beranda') }}">Beranda</a> <i class="bi bi-chevron-right"></i> {{ $judul }}</small>
            <h1>{{ $judul }}</h1>
            @isset($sub)<p>{{ $sub }}</p>@endisset
        </div>
        @isset($ikon)<i class="bi {{ $ikon }} kepala-hal__ikon"></i>@endisset
    </div>
</section>
@if (! empty($menu))
    <nav class="subnav" aria-label="Bagian halaman">
        <div class="container subnav__isi">
            @foreach ($menu as $id => $nama)<a href="#{{ $id }}">{{ $nama }}</a>@endforeach
        </div>
    </nav>
@endif
