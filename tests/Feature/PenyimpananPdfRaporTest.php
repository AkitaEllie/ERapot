<?php

use App\Enums\RaporStatus;
use App\Models\Kelas;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const ISI_LAMA = '%PDF milik anak lain';

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    Storage::fake('public');
    Storage::fake(Rapor::DISK_PDF);

    $this->wali = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => Kelas::factory()->create(['User_ID' => $this->wali->getKey()])->getKey(),
        'Status' => RaporStatus::Disetujui,
    ]);
    $this->disk = Storage::disk(Rapor::DISK_PDF);
});

function unduhIsi(Rapor $rapor, User $user): string
{
    return test()->actingAs($user)->get(route('rapor.unduh', $rapor))->assertOk()->streamedContent();
}

describe('tempat penyimpanan', function (): void {
    it('tidak menaruh PDF pada penyimpanan publik', function (): void {
        $berkas = $this->rapor->eksporPDF();

        Storage::disk('public')->assertMissing($berkas);
        $this->disk->assertExists($berkas);
    });

    it('tidak menyajikan PDF lewat URL penyimpanan tanpa tanda tangan', function (): void {
        $berkas = $this->rapor->eksporPDF();

        $this->get('/storage/'.$berkas)->assertForbidden();
    });

    it('tetap mewajibkan login untuk mengunduh', function (): void {
        $this->rapor->eksporPDF();

        $this->get(route('rapor.unduh', $this->rapor))->assertRedirect(route('login'));
    });
});

describe('berkas usang', function (): void {
    it('tidak menyajikan berkas yang tidak tercatat milik rapor ini', function (): void {
        // A leftover at this id's path, as after a reset or a reused id.
        $this->disk->put($this->rapor->berkasPdf(), ISI_LAMA);

        expect(unduhIsi($this->rapor, $this->wali))->not->toBe(ISI_LAMA)->toStartWith('%PDF-');
    });

    it('mengekspor ulang saat berkasnya hilang', function (): void {
        $berkas = $this->rapor->eksporPDF();
        $this->disk->delete($berkas);

        expect(unduhIsi($this->rapor->fresh(), $this->wali))->toStartWith('%PDF-');
    });

    it('memakai ulang berkas yang masih berlaku', function (): void {
        $berkas = $this->rapor->eksporPDF();
        $this->disk->put($berkas, ISI_LAMA);

        expect(unduhIsi($this->rapor->fresh(), $this->wali))->toBe(ISI_LAMA);
    });

    it('mengekspor ulang setelah data tercetak berubah', function (string $yangDiubah): void {
        $berkas = $this->rapor->eksporPDF();
        $this->disk->put($berkas, ISI_LAMA);

        $this->travel(1)->minute();
        match ($yangDiubah) {
            'siswa' => $this->rapor->siswa->update(['Nama' => 'Nama Diperbaiki']),
            'kelas' => $this->rapor->kelas->update(['Nama_Kelas' => 'Kelas Diperbaiki']),
            'rapor' => $this->rapor->update(['Sakit' => 9]),
        };

        expect(unduhIsi($this->rapor->fresh(), $this->wali))->not->toBe(ISI_LAMA);
    })->with(['siswa', 'kelas', 'rapor']);

    it('melupakan berkas lama saat rapor disetujui', function (): void {
        $this->rapor->eksporPDF();
        $this->rapor->forceFill(['Status' => RaporStatus::Menunggu])->save();

        $this->rapor->setujui(User::factory()->create(['Role_ID' => 'KEPSEKOL']));

        expect($this->rapor->fresh()->berkasPdfMasihBerlaku())->toBeFalse();
    });
});

describe('perintah rapor:amankan-pdf', function (): void {
    it('memindahkan berkas yang tercatat dan menghapus yang tanpa pemilik', function (): void {
        $publik = Storage::disk('public');
        $berkas = $this->rapor->berkasPdf();
        $this->rapor->forceFill(['File_PDF' => $berkas])->save();
        $publik->put($berkas, '%PDF tercatat');
        $publik->put('rapor/R-9999999.pdf', ISI_LAMA);
        $publik->put('dokumentasi/foto.jpg', 'foto');

        $this->artisan('rapor:amankan-pdf')
            ->expectsOutputToContain('1 PDF dipindahkan ke penyimpanan privat, 1 PDF tanpa pemilik dihapus.')
            ->assertSuccessful();

        expect($this->disk->get($berkas))->toBe('%PDF tercatat');
        $publik->assertMissing($berkas);
        $publik->assertMissing('rapor/R-9999999.pdf');
        $this->disk->assertMissing('rapor/R-9999999.pdf');
        $publik->assertExists('dokumentasi/foto.jpg');
    });
});
