<?php

use App\Enums\JenisMataPelajaran;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->guru = User::factory()->create();
    $this->kelas = Kelas::factory()->create(['User_ID' => $this->guru->getKey()]);
    $this->siswa = Siswa::factory()->create();

    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => $this->siswa->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
    ]);

    $this->actingAs($this->guru);
});

it('menyediakan font CJK untuk halaman berbahasa Mandarin', function (): void {
    $berkas = storage_path('fonts/NotoSansCJK.ttf');

    expect(file_exists($berkas))->toBeTrue('Font CJK belum ada di storage/fonts.');
    expect(filesize($berkas))->toBeGreaterThan(100_000, 'Font CJK tampak terpotong.');
});

it('menyisipkan deklarasi font ke dalam dokumen rapor', function (): void {
    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('NotoSansCJK', escape: false)
        ->assertSee('rapor-dokumen', escape: false);
});

it('merender teks Mandarin pada halaman mata pelajaran', function (): void {
    $mapel = MataPelajaran::factory()->create([
        'Nama_Mapel' => 'Bahasa Mandarin',
        'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
    ]);
    Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $mapel->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);
    Narasi::factory()->final()->create([
        'Rapor_ID' => $this->rapor->getKey(),
        'Mapel_ID' => $mapel->getKey(),
    ]);
    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $mapel->getKey()]);
    IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'Deskripsi' => '听 Listening : 能根据所听到的拼音。',
    ]);

    // The Chinese must survive to the rendered HTML rather than being stripped or
    // escaped into entities, which is what the font then has to draw.
    Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('听', escape: false);
});
