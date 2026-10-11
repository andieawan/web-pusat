@foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $kunci => $nama)
    @if ($tautan = \App\Models\Pengaturan::ambil('sosmed_' . $kunci))
        <a href="{{ $tautan }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $nama }}"><i class="bi bi-{{ $kunci }}"></i></a>
    @endif
@endforeach
