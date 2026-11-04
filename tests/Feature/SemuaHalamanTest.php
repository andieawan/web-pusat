<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jelajah semua halaman: pengunjung mulai dari beranda, admin dari panel admin; ikuti setiap tautan dan pastikan tidak
 * ada halaman galat (5xx) atau tautan ke halaman tidak ada (404) / terlarang (403).
 * Set JELAJAH_SIMPAN=folder untuk menyimpan HTML tiap halaman (bahan tangkapan layar).
 */
class SemuaHalamanTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{masalah: list<string>, halaman: array<string,string>} */
    private function jelajah($user, string $mulai, int $maks = 250): array
    {
        $antre = [[$mulai, '(awal)']];
        $lihat = [];
        $pola = [];
        $masalah = [];
        $halaman = [];
        while ($antre && count($lihat) < $maks) {
            [$url, $dari] = array_shift($antre);
            if (isset($lihat[$url])) {
                continue;
            }
            $lihat[$url] = true;
            $res = ($user ? $this->actingAs($user) : $this)->get($url);
            $s = $res->getStatusCode();
            if ($s >= 500 || in_array($s, [403, 404, 405], true)) {
                $e = $res->exception;
                $masalah[] = "$s $url (dari $dari)".($e ? ' — '.class_basename($e).': '.mb_substr($e->getMessage(), 0, 200).' @ '.str_replace(base_path().'/', '', $e->getFile()).':'.$e->getLine() : '');

                continue;
            }
            if ($s >= 300 && $s < 400) {
                $loc = $this->lokal((string) $res->headers->get('Location'));
                $loc && ! isset($lihat[$loc]) && $antre[] = [$loc, $url];

                continue;
            }
            if (! str_contains((string) $res->headers->get('Content-Type'), 'html')) {
                continue;
            }
            $html = (string) $res->getContent();
            $halaman[$url] = $html;
            preg_match_all('/<a\b[^>]*\bhref="([^"]+)"/i', $html, $m);
            foreach ($m[1] as $h) {
                $h = $this->lokal(html_entity_decode($h));
                if (! $h || isset($lihat[$h]) || preg_match('#/(logout|sso/|unduh|download)|^/keluar$#', $h)) {
                    continue;
                }
                $p = preg_replace(['/\d+/', '/\?.*/'], ['N', '?'], $h); // batasi variasi id/kueri per pola
                if (($pola[$p] = ($pola[$p] ?? 0) + 1) <= 3) {
                    $antre[] = [$h, $url];
                }
            }
        }

        return ['masalah' => $masalah, 'halaman' => $halaman];
    }

    private function lokal(string $h): ?string
    {
        $h = trim($h);
        foreach ([rtrim(url('/'), '/'), rtrim((string) config('app.url'), '/'), 'http://localhost', 'https://localhost'] as $akar) {
            if ($akar !== '' && str_starts_with($h, $akar)) {
                $h = substr($h, strlen($akar)) ?: '/';
                break;
            }
        }
        if ($h === '' || $h[0] !== '/' || str_starts_with($h, '//')) {
            return null;
        }

        return strtok($h, '#') ?: null;
    }

    private function simpan(string $peran, array $halaman): void
    {
        if (! ($dir = getenv('JELAJAH_SIMPAN'))) {
            return;
        }
        @mkdir("$dir/$peran", 0777, true);
        foreach ($halaman as $url => $html) {
            file_put_contents("$dir/$peran/".(trim(preg_replace('/[^a-z0-9]+/i', '_', $url), '_') ?: 'beranda').'.html', $html);
        }
    }

    public function test_semua_halaman_bisa_dibuka(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $semua = [];
        foreach (['pengunjung' => [null, '/'], 'admin' => [User::where('is_admin', true)->firstOrFail(), '/admin']] as $nama => [$u, $mulai]) {
            $h = $this->jelajah($u, $mulai);
            $this->simpan($nama, $h['halaman']);
            $this->assertGreaterThan(0, count($h['halaman']), "$nama tidak membuka satu halaman pun");
            foreach ($h['masalah'] as $m) {
                $semua[] = "[$nama] $m";
            }
        }
        $this->assertSame([], $semua, "Halaman bermasalah:\n".implode("\n", $semua));
    }
}
