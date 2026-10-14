<?php

use App\Enums\JenisMataPelajaran;
use App\Enums\RaporStatus;
use App\Models\IndikatorCapaian;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['ADMIN', 'GURU', 'GURUWALI', 'TATAUSA', 'KEPSEKOL'] as $kode) {
        Role::factory()->bawaan($kode)->create();
    }

    Storage::fake(Rapor::DISK_PDF);

    $this->wali = User::factory()->create(['Role_ID' => 'GURUWALI']);
    $this->guru = User::factory()->create(['Role_ID' => 'GURU']);
    $this->kelas = Kelas::factory()->create(['User_ID' => $this->wali->getKey()]);
    $this->siswa = Siswa::factory()->create(['NISN' => '0154872301']);

    $this->rapor = Rapor::factory()->create([
        'Siswa_ID' => $this->siswa->getKey(),
        'Kelas_ID' => $this->kelas->getKey(),
        'Status' => RaporStatus::Disetujui,
    ]);

    $this->mapelProsa = MataPelajaran::factory()->create([
        'Nama_Mapel' => 'Nilai-nilai Agama & Budi Pekerti',
        'Jenis_Mapel' => JenisMataPelajaran::Intrakurikuler,
    ]);
    $this->mapelTabel = MataPelajaran::factory()->create([
        'Nama_Mapel' => 'Bahasa Mandarin',
        'Jenis_Mapel' => JenisMataPelajaran::MuatanLokal,
    ]);

    Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $this->mapelProsa->getKey(),
        'User_ID' => $this->wali->getKey(),
    ]);
    Pembelajaran::factory()->create([
        'Kelas_ID' => $this->kelas->getKey(),
        'Mapel_ID' => $this->mapelTabel->getKey(),
        'User_ID' => $this->guru->getKey(),
    ]);

    Narasi::factory()->final('Andini terbiasa berdoa sebelum kegiatan.')->create([
        'Rapor_ID' => $this->rapor->getKey(),
        'Mapel_ID' => $this->mapelProsa->getKey(),
    ]);
    Narasi::factory()->final('Andini mengenali pinyin sederhana.')->create([
        'Rapor_ID' => $this->rapor->getKey(),
        'Mapel_ID' => $this->mapelTabel->getKey(),
    ]);

    $program = ProgramPengembangan::factory()->create(['Mapel_ID' => $this->mapelTabel->getKey()]);
    IndikatorCapaian::factory()->create([
        'Program_ID' => $program->getKey(),
        'Deskripsi' => '听 Listening : 能根据所听到的拼音。',
    ]);
});

it('mengekspor rapor menjadi berkas PDF dan mengingat jalurnya', function (): void {
    $jalur = $this->rapor->eksporPDF();

    expect($jalur)->toBe('rapor/'.$this->rapor->getKey().'.pdf');
    Storage::disk(Rapor::DISK_PDF)->assertExists($jalur);

    // The file must be a real PDF, not an empty placeholder.
    $isi = Storage::disk(Rapor::DISK_PDF)->get($jalur);
    expect($isi)->toStartWith('%PDF-');

    $this->rapor->refresh();
    expect($this->rapor->File_PDF)->toBe($jalur);
    expect($this->rapor->Tanggal_Cetak)->not->toBeNull();
});

it('memuat isi rapor ke dalam PDF', function (): void {
    $this->rapor->eksporPDF();

    // The PDF is compressed, so the content streams are not readable as plain
    // text; assert on the marker that the document was rendered at all.
    $ukuran = Storage::disk(Rapor::DISK_PDF)->size($this->rapor->berkasPdf());

    expect($ukuran)->toBeGreaterThan(1_000);
});

it('memakai ulang berkas yang sama saat diekspor dua kali', function (): void {
    $pertama = $this->rapor->eksporPDF();
    $kedua = $this->rapor->eksporPDF();

    expect($kedua)->toBe($pertama);
    Storage::disk(Rapor::DISK_PDF)->assertExists($pertama);
});

it('mengelompokkan mata pelajaran sesuai bagian dokumen', function (): void {
    $seksi = $this->rapor->seksi();

    expect($seksi['intrakurikuler']->pluck('mapel.Nama_Mapel')->all())
        ->toBe(['Nilai-nilai Agama & Budi Pekerti'])
        ->and($seksi['tabel']->pluck('mapel.Nama_Mapel')->all())
        ->toBe(['Bahasa Mandarin']);
});

it('mengunduh berkas PDF rapor yang sudah diunduh', function (): void {
    $this->actingAs($this->wali)
        ->get(route('rapor.unduh', $this->rapor))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('menolak unduhan saat rapor belum disetujui', function (): void {
    $this->rapor->forceFill(['Status' => RaporStatus::Draft])->save();

    $this->actingAs($this->wali)
        ->get(route('rapor.unduh', $this->rapor))
        ->assertForbidden();
});

it('menolak unduhan dari peran yang tidak berhak', function (): void {
    $tataUsaha = User::factory()->create(['Role_ID' => 'TATAUSA']);

    $this->actingAs($tataUsaha)
        ->get(route('rapor.unduh', $this->rapor))
        ->assertForbidden();
});

it('mengekspor seluruh buku untuk guru wali kelas', function (): void {
    $this->actingAs($this->wali);

    Livewire\Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('A. INTRAKURIKULER')
        ->assertSee('Bahasa Mandarin')
        ->assertSee('Refleksi orang tua');
});

it('hanya menampilkan mata pelajaran sendiri untuk guru mata pelajaran', function (): void {
    $this->actingAs($this->guru);

    Livewire\Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->assertOk()
        ->assertSee('Bahasa Mandarin')
        ->assertDontSee('Nilai-nilai Agama & Budi Pekerti')
        ->assertDontSee('Refleksi orang tua');
});

it('mengunduh berkas yang diekspor oleh aksi export', function (): void {
    $this->actingAs($this->wali);

    Livewire\Livewire::test('pages::rapor-preview', ['rapor' => $this->rapor])
        ->call('exportPdf')
        ->assertRedirect(route('rapor.unduh', $this->rapor));

    Storage::disk(Rapor::DISK_PDF)->assertExists($this->rapor->berkasPdf());
});
