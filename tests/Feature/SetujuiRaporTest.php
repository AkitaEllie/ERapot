<?php

use App\Enums\RaporStatus;
use App\Enums\Semester;
use App\Enums\StatusNarasi;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::factory()->bawaan('GURU')->create();
    Role::factory()->bawaan('KEPSEKOL')->create();

    $this->kepsekol = User::factory()->create(['Role_ID' => 'KEPSEKOL']);
    $this->guru = User::factory()->create(['Role_ID' => 'GURU']);
    $this->mapel = MataPelajaran::factory()->create();
    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => Siswa::factory()->create()->getKey(),
        'Kelas_ID' => Kelas::factory()->create(['User_ID' => $this->guru->getKey()])->getKey(),
        'Semester' => Semester::Ganjil,
        'Status' => RaporStatus::Menunggu,
    ]);
});

function narasiUntuk(Rapor $rapor, MataPelajaran $mapel, ?string $draft): Narasi
{
    return Narasi::factory()->create([
        'Rapor_ID' => $rapor->getKey(),
        'Mapel_ID' => $mapel->getKey(),
        'Draft_Narasi' => $draft,
        'Status_Narasi' => $draft === null ? StatusNarasi::Draft : StatusNarasi::Diedit,
    ]);
}

it('menjadikan narasi final saat rapor disetujui', function (): void {
    $narasi = narasiUntuk($this->rapor, $this->mapel, 'Narasi yang akan disetujui');

    $this->rapor->setujui($this->kepsekol);

    $narasi->refresh();

    expect($narasi->Status_Narasi)->toBe(StatusNarasi::Final)
        ->and($narasi->Narasi_Final)->toBe($narasi->Draft_Narasi);
});

it('menjadikan final seluruh mapel yang sudah terisi', function (): void {
    $satu = narasiUntuk($this->rapor, $this->mapel, 'Narasi pertama');
    $dua = narasiUntuk($this->rapor, MataPelajaran::factory()->create(), 'Narasi kedua');

    $this->rapor->setujui($this->kepsekol);

    expect($satu->fresh()->Status_Narasi)->toBe(StatusNarasi::Final)
        ->and($dua->fresh()->Status_Narasi)->toBe(StatusNarasi::Final);
});

it('tidak menandai narasi kosong sebagai final', function (): void {
    $kosong = narasiUntuk($this->rapor, $this->mapel, null);

    $this->rapor->setujui($this->kepsekol);

    expect($kosong->fresh()->Status_Narasi)->toBe(StatusNarasi::Draft)
        ->and($kosong->fresh()->Narasi_Final)->toBeNull();
});

it('mengabaikan string kosong saat memfinalkan', function (): void {
    $kosong = narasiUntuk($this->rapor, $this->mapel, '');

    $this->rapor->setujui($this->kepsekol);

    // Left as it was: an empty narrative must not be promoted to final.
    expect($kosong->fresh()->Status_Narasi)->toBe(StatusNarasi::Diedit)
        ->and($kosong->fresh()->Narasi_Final)->toBeNull();
});

it('tidak menyentuh narasi yang sudah final', function (): void {
    $narasi = narasiUntuk($this->rapor, $this->mapel, 'Sudah final');
    $narasi->simpanNarasiFinal('Teks final yang sudah disetujui');
    $sebelum = $narasi->fresh()->Tanggal_Edit;

    $this->rapor->setujui($this->kepsekol);

    $narasi->refresh();

    expect($narasi->Narasi_Final)->toBe('Teks final yang sudah disetujui')
        ->and($narasi->Tanggal_Edit->timestamp)->toBe($sebelum->timestamp);
});

it('tidak memfinalkan apa pun bila rapor belum menunggu', function (): void {
    $this->rapor->update(['Status' => RaporStatus::Draft]);
    $narasi = narasiUntuk($this->rapor, $this->mapel, 'Belum siap');

    expect($this->rapor->setujui($this->kepsekol))->toBeFalse();
    expect($narasi->fresh()->Status_Narasi)->toBe(StatusNarasi::Diedit);
});

it('memfinalkan narasi saat kepala sekolah menyetujui dari layar review', function (): void {
    $narasi = narasiUntuk($this->rapor, $this->mapel, 'Narasi untuk disetujui');

    $this->actingAs($this->kepsekol);

    Livewire::test('pages::review-rapor')
        ->call('pilih', $this->rapor->getKey())
        ->call('setujui')
        ->assertHasNoErrors();

    expect($this->rapor->fresh()->Status)->toBe(RaporStatus::Disetujui)
        ->and($narasi->fresh()->Status_Narasi)->toBe(StatusNarasi::Final)
        ->and($narasi->fresh()->Narasi_Final)->toBe('Narasi untuk disetujui');
});

it('tidak memfinalkan narasi saat rapor dikembalikan untuk revisi', function (): void {
    $narasi = narasiUntuk($this->rapor, $this->mapel, 'Perlu diperbaiki guru');

    $this->rapor->kembalikanRevisi('tata bahasa perlu diperbaiki');

    expect($narasi->fresh()->Status_Narasi)->toBe(StatusNarasi::Diedit)
        ->and($this->rapor->fresh()->Status)->toBe(RaporStatus::PerluRevisi);
});
