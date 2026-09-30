<?php

use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\TipeIndikator;
use App\Models\IndikatorCapaian;
use App\Models\MataPelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Role;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::factory()->bawaan('GURU')->create();
    $this->guru = User::factory()->create(['Role_ID' => 'GURU']);
    $this->periode = TahunAjaran::factory()->create([
        'Tahun_Ajaran' => '2025/2026',
        'Semester_Aktif' => Semester::Ganjil,
        'Is_Active' => true,
    ]);
    $this->mapel = MataPelajaran::factory()->create();
    $this->program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapel->getKey()]);

    $this->actingAs($this->guru);
});

function indikatorLama(Semester $semester, ?ProgramPengembangan $program = null, string $deskripsi = 'Mempraktikkan doa'): IndikatorCapaian
{
    return IndikatorCapaian::factory()->create([
        'Program_ID' => ($program ?? test()->program)->getKey(),
        'TahunAjaran_ID' => test()->periode->getKey(),
        'Jenjang' => Jenjang::Tka,
        'Semester' => $semester,
        'Tipe' => TipeIndikator::Capaian,
        'Deskripsi' => $deskripsi,
    ]);
}

it('menyalin indikator ke semester tujuan', function (): void {
    $asal = indikatorLama(Semester::Genap);

    $asal->salinIndikator(Semester::Ganjil);

    $salinan = IndikatorCapaian::query()
        ->where('Semester', Semester::Ganjil)
        ->firstOrFail();

    expect($salinan->Deskripsi)->toBe($asal->Deskripsi)
        ->and($salinan->Program_ID)->toBe($asal->Program_ID)
        ->and($salinan->Jenjang)->toBe(Jenjang::Tka)
        ->and($salinan->Tipe)->toBe(TipeIndikator::Capaian)
        ->and($salinan->TahunAjaran_ID)->toBe($this->periode->getKey())
        ->and($salinan->getKey())->not->toBe($asal->getKey());
});

it('memberi kode KD baru pada salinan', function (): void {
    $asal = indikatorLama(Semester::Genap);
    $asal->salinIndikator(Semester::Ganjil);

    $salinan = IndikatorCapaian::query()->where('Semester', Semester::Ganjil)->firstOrFail();

    expect($salinan->Kode_KD)->not->toBe('')
        ->and($salinan->Kode_KD)->not->toBe($asal->Kode_KD);
});

it('mengabaikan permintaan menyalin ke semester yang sama', function (): void {
    $asal = indikatorLama(Semester::Genap);

    $asal->salinIndikator(Semester::Genap);

    expect(IndikatorCapaian::query()->count())->toBe(1);
});

it('tidak menggandakan indikator yang sudah ada di semester tujuan', function (): void {
    indikatorLama(Semester::Ganjil, null, 'Sudah ada di ganjil');
    $asal = indikatorLama(Semester::Genap, null, 'Sudah ada di ganjil');

    $asal->salinIndikator(Semester::Ganjil);
    $asal->salinIndikator(Semester::Ganjil);

    expect(IndikatorCapaian::query()->where('Semester', Semester::Ganjil)->count())->toBe(1);
});

it('menyalin hanya indikator yang cocok dengan filter layar', function (): void {
    indikatorLama(Semester::Genap, null, 'Indikator-terfilter');
    $programLain = ProgramPengembangan::factory()->create([
        'Mapel_ID' => MataPelajaran::factory()->create()->getKey(),
    ]);
    indikatorLama(Semester::Genap, $programLain, 'Indikator-mapel-lain');

    Livewire::test('pages::indikator')
        ->set('filterMapel', $this->mapel->getKey())
        ->call('salinSemesterLalu')
        ->assertSee('1 indikator disalin dari semester Genap');

    $deskripsi = IndikatorCapaian::query()->where('Semester', Semester::Ganjil)->pluck('Deskripsi');

    expect($deskripsi->all())->toBe(['Indikator-terfilter']);
});

it('melaporkan ketika tidak ada yang bisa disalin', function (): void {
    Livewire::test('pages::indikator')
        ->call('salinSemesterLalu')
        ->assertSee('Tidak ada indikator baru untuk disalin');
});

it('menyalin dari semester lalu ke semester aktif setelah berganti semester', function (): void {
    indikatorLama(Semester::Ganjil, null, 'Dari semester lalu');

    $this->periode->gantiSemester();

    expect($this->periode->fresh()->Semester_Aktif)->toBe(Semester::Genap);

    Livewire::test('pages::indikator')
        ->call('salinSemesterLalu')
        ->assertSee('1 indikator disalin dari semester Ganjil');

    expect(IndikatorCapaian::query()->where('Semester', Semester::Genap)->pluck('Deskripsi')->all())
        ->toBe(['Dari semester lalu']);
});
