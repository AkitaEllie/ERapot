<?php

use App\Enums\Nilai;
use App\Enums\StatusNarasi;
use App\Exceptions\NarasiTidakTersedia;
use App\Livewire\Concerns\MengajukanRapor;
use App\Models\Dokumentasi;
use App\Models\IndikatorCapaian;
use App\Models\MataPelajaran;
use App\Models\NilaiSiswa;
use App\Models\Narasi;
use App\Models\Pembelajaran;
use App\Models\ProgramPengembangan;
use App\Models\Rapor;
use App\Services\GeminiApi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new #[Layout('layouts::app', ['active' => 'rapor'])]
class extends \Livewire\Component
{
    use MengajukanRapor, WithFileUploads;

    public Rapor $rapor;

    public MataPelajaran $mapel;

    /** @var array<string, string> Indikator_ID => Nilai */
    public array $nilai = [];

    /** @var array<string, bool> Indikator_ID => bool */
    public array $untukNarasi = [];

    public string $catatan = '';

    public string $draftNarasi = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $fotoBaru = [];

    public function mount(): void
    {
        abort_unless($this->rapor->bolehDiisiOleh(auth()->user(), $this->mapel), 403);

        $narasi = $this->narasi;

        $this->catatan = $narasi?->Catatan_Guru ?? '';
        $this->draftNarasi = $narasi?->Draft_Narasi ?? '';

        foreach ($this->program->flatMap(fn (ProgramPengembangan $program): Collection => $program->indikatorCapaian) as $indikator) {
            $terisi = $narasi?->nilaiSiswa->firstWhere('Indikator_ID', $indikator->getKey());

            if ($terisi instanceof NilaiSiswa) {
                $this->nilai[$indikator->getKey()] = $terisi->Nilai->value;
                $this->untukNarasi[$indikator->getKey()] = (bool) $terisi->Dipilih_Untuk_Narasi;
            }
        }
    }

    public function updated($property): void
    {
        if ($property === 'fotoBaru') {
            $this->simpanFotoBaru();
        }
    }

    #[Computed]
    public function narasi(): ?Narasi
    {
        return $this->rapor->narasi()
            ->where('Mapel_ID', $this->mapel->getKey())
            ->with(['nilaiSiswa', 'dokumentasi'])
            ->first();
    }

    /**
     * The subject tabs this teacher may open: all of them for the class's
     * homeroom teacher, only their own for a subject teacher.
     *
     * @return Collection<int, MataPelajaran>
     */
    #[Computed]
    public function daftarMapel(): Collection
    {
        return Pembelajaran::query()
            ->where('Kelas_ID', $this->rapor->Kelas_ID)
            ->unless($this->rapor->milikWaliKelas(auth()->user()), fn ($query) => $query->where('User_ID', auth()->id()))
            ->with('mataPelajaran')
            ->get()
            ->pluck('mataPelajaran')
            ->filter()
            ->unique('Mapel_ID')
            ->values();
    }

    /** @return Collection<int, ProgramPengembangan> */
    #[Computed]
    public function program(): Collection
    {
        return ProgramPengembangan::query()
            ->where('Mapel_ID', $this->mapel->getKey())
            ->with(['indikatorCapaian' => fn ($query) => $query->orderBy('Kode_KD')])
            ->orderBy('Nama_Program')
            ->get();
    }

    public function simpanDraft(): void
    {
        if ($this->terkunci()) {
            return;
        }

        $this->simpanTabIni();

        session()->flash('sukses', 'Data rapor berhasil disimpan.');
    }

    /**
     * Save the narrative the teacher has reviewed and edited by hand, the last
     * step of the human-in-the-loop flow.
     */
    public function simpanNarasi(): void
    {
        if ($this->terkunci()) {
            return;
        }

        $this->validate(['draftNarasi' => ['required', 'string']], [
            'draftNarasi.required' => 'Narasi masih kosong. Tekan Generate Narasi atau tulis narasinya sendiri.',
        ]);

        $narasi = $this->ensureNarasi();
        $this->simpanNilaiDanCatatan();
        $narasi->editNarasi($this->draftNarasi);
        $this->rapor->tandaiSedangDiisi();
        unset($this->narasi);

        session()->flash('sukses', 'Narasi berhasil disimpan.');
    }

    /**
     * Submit the report. The open tab's ratings must be complete first; the
     * rest of the report is checked by the submit dialog.
     */
    public function ajukan(): void
    {
        $this->validate([
            'nilai' => ['array'],
            'nilai.*' => ['required', Rule::enum(Nilai::class)],
        ], [], ['nilai' => 'pilihan sikap']);

        // Every indicator on this tab needs a rating; a subject without
        // indicators has nothing to rate and goes straight to the check.
        $belumDinilai = $this->program
            ->flatMap(fn (ProgramPengembangan $program): Collection => $program->indikatorCapaian)
            ->reject(fn (IndikatorCapaian $indikator): bool => filled($this->nilai[$indikator->getKey()] ?? null))
            ->count();

        if ($belumDinilai > 0) {
            $this->addError('nilai', $belumDinilai.' indikator pada tab ini belum dinilai (MB, BSH, atau BSB).');

            return;
        }

        $this->periksaPengajuan();
    }

    protected function simpanTabIni(): void
    {
        $narasi = $this->ensureNarasi();
        $this->simpanNilaiDanCatatan();

        if ($this->draftNarasi !== (string) $narasi->Draft_Narasi) {
            $narasi->editNarasi($this->draftNarasi);
        }

        $this->rapor->tandaiSedangDiisi();
        unset($this->narasi);
    }

    private function terkunci(): bool
    {
        if ($this->rapor->bolehDiubah()) {
            return false;
        }

        session()->flash('galat', 'Rapor sedang menunggu persetujuan atau sudah disetujui, sehingga tidak dapat diubah.');

        return true;
    }

    public function generateNarasi(): void
    {
        if ($this->terkunci()) {
            return;
        }

        $this->simpanNilaiDanCatatan();

        unset($this->narasi);
        $narasi = $this->narasi;

        if (! $narasi instanceof Narasi) {
            session()->flash('galat', 'Belum ada narasi untuk mata pelajaran ini.');

            return;
        }

        if ($narasi->nilaiSiswa->isEmpty() && blank($narasi->Catatan_Guru)) {
            session()->flash('galat', 'Data belum lengkap: nilai indikator atau catatan personal guru belum diisi.');

            return;
        }

        try {
            $hasil = app(GeminiApi::class)->kirimPrompt($narasi->susunPrompt());
        } catch (NarasiTidakTersedia $e) {
            // The provider is unconfigured or failed. Say why rather than leaving
            // the teacher to guess, and let them write the narrative by hand.
            session()->flash('galat', $e->getMessage());

            return;
        }

        // The draft is stored straight away, so a refresh does not lose it; the
        // teacher still reviews and edits it before saving the narrative.
        $narasi->simpanDraft($hasil);
        $this->rapor->tandaiSedangDiisi();
        $this->draftNarasi = $hasil;
        unset($this->narasi);
    }

    public function hapusFoto(string $dokumentasiId): void
    {
        if ($this->terkunci()) {
            return;
        }

        $dokumentasi = $this->narasi?->dokumentasi->firstWhere('Dokumentasi_ID', $dokumentasiId);

        $dokumentasi?->hapusFoto();
        unset($this->narasi);
    }

    private function ensureNarasi(): Narasi
    {
        return $this->narasi ?? Narasi::create([
            'Rapor_ID' => $this->rapor->getKey(),
            'Mapel_ID' => $this->mapel->getKey(),
        ]);
    }

    private function simpanNilaiDanCatatan(): void
    {
        $narasi = $this->ensureNarasi();

        foreach ($this->program->flatMap(fn (ProgramPengembangan $program): Collection => $program->indikatorCapaian) as $indikator) {
            /** @var IndikatorCapaian $indikator */
            $nilai = $this->nilai[$indikator->getKey()] ?? null;

            if ($nilai === null) {
                continue;
            }

            NilaiSiswa::query()->updateOrCreate(
                ['Narasi_ID' => $narasi->getKey(), 'Indikator_ID' => $indikator->getKey()],
                ['Nilai' => $nilai, 'Dipilih_Untuk_Narasi' => (bool) ($this->untukNarasi[$indikator->getKey()] ?? false)],
            );
        }

        $narasi->isiCatatanPersonal($this->catatan);
    }

    private function simpanFotoBaru(): void
    {
        if ($this->terkunci()) {
            $this->fotoBaru = [];

            return;
        }

        try {
            $this->validate(
                ['fotoBaru.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048']],
                [
                    'fotoBaru.*.image' => 'Format atau ukuran file tidak sesuai (jpg/jpeg/png, maks. 2MB).',
                    'fotoBaru.*.mimes' => 'Format atau ukuran file tidak sesuai (jpg/jpeg/png, maks. 2MB).',
                    'fotoBaru.*.max' => 'Format atau ukuran file tidak sesuai (jpg/jpeg/png, maks. 2MB).',
                ],
            );
        } catch (ValidationException $e) {
            // Drop the rejected files, otherwise they are validated again with
            // every later upload and block a valid photo.
            $this->fotoBaru = [];

            throw $e;
        }

        $narasi = $this->ensureNarasi();

        foreach ($this->fotoBaru as $file) {
            $dokumentasi = $narasi->dokumentasi()->create(['Foto' => '']);

            try {
                $dokumentasi->unggahFoto($file);
            } catch (HttpException) {
                $dokumentasi->delete();
                session()->flash('galat', 'Maksimal '.Dokumentasi::MAKS_FOTO.' foto per narasi.');
            }
        }

        $this->fotoBaru = [];
        $this->rapor->tandaiSedangDiisi();
        unset($this->narasi);
    }

    #[Computed]
    public function judul(): string
    {
        return 'Input Rapor - '.$this->rapor->siswa->Nama;
    }

    #[Computed]
    public function subjudul(): string
    {
        return sprintf(
            '%s - NISN %s - Semester %s %s',
            $this->rapor->kelas->Nama_Kelas,
            $this->rapor->siswa->NISN,
            $this->rapor->Semester->label(),
            $this->rapor->kelas->tahunAjaran->Tahun_Ajaran,
        );
    }

    #[Computed]
    public function statusNarasi(): StatusNarasi
    {
        return $this->narasi?->Status_Narasi ?? StatusNarasi::Draft;
    }
};

?>

<div>
    <x-app.header
        :title="$this->judul"
        :subtitle="$this->subjudul"
    >
        <x-slot:actions>
            <x-ui.badge :status="$rapor->Status">{{ $rapor->Status->label() }}</x-ui.badge>
            <x-ui.button type="button" variant="secondary" wire:click="simpanDraft" :disabled="! $rapor->bolehDiubah()">Simpan Draft</x-ui.button>
            @if ($rapor->milikWaliKelas(auth()->user()))
                <x-ui.button type="button" variant="primary" wire:click="ajukan" :disabled="! $rapor->bolehDiubah()">{{ $rapor->Status === \App\Enums\RaporStatus::PerluRevisi ? 'Ajukan Ulang' : 'Ajukan Rapor' }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <x-rapor.tab-input :rapor="$rapor" :daftar-mapel="$this->daftarMapel" :aktif="$mapel->getKey()" />

    <div class="flex-1 overflow-y-auto bg-canvas">
        <x-rapor.status-banner :rapor="$rapor" />

    <div class="px-8 py-6">
        <div class="grid grid-cols-[700px_400px] items-start gap-7">
            {{-- Indicator checklist --}}
            <section>
                <h2 class="text-[18px] text-[16px] font-semibold text-heading">Program Pengembangan &amp; Indikator Capaian</h2>
                <p class="mt-1 text-[15px] text-muted">Centang indikator yang paling menonjol untuk dijadikan bahan narasi</p>

                <div class="mt-4 overflow-hidden rounded border border-hairline bg-surface">
                    @forelse ($this->program as $program)
                        <div class="border-b border-hairline bg-canvas px-4 py-3">
                            <p class="text-[15px] font-medium text-heading">{{ $program->Nama_Program }}</p>
                        </div>

                        @foreach ($program->indikatorCapaian as $indikator)
                            <div class="border-b border-hairline px-4 py-4 last:border-b-0">
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model="untukNarasi.{{ $indikator->getKey() }}"
                                        class="mt-0.5 size-4 shrink-0 rounded-sm border-sunken-strong text-accent-deep focus:ring-accent"
                                    >
                                    <span class="text-[15px] text-body">{{ $indikator->Deskripsi }}</span>
                                </label>

                                <div class="mt-3 flex gap-2 pl-7">
                                    @foreach (Nilai::cases() as $pilihan)
                                        <label @class([
                                            'flex h-8 cursor-pointer items-center justify-center rounded border px-3 text-[14px] transition-colors',
                                            'border-accent bg-accent-soft text-accent-deep' => ($nilai[$indikator->getKey()] ?? null) === $pilihan->value,
                                            'border-hairline-strong bg-surface text-label hover:bg-canvas' => ($nilai[$indikator->getKey()] ?? null) !== $pilihan->value,
                                        ])>
                                            <input type="radio" class="sr-only" wire:model="nilai.{{ $indikator->getKey() }}" value="{{ $pilihan->value }}">
                                            {{ $pilihan->value }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @empty
                        <p class="px-4 py-10 text-center text-[15px] text-muted">Belum ada indikator untuk mata pelajaran ini.</p>
                    @endforelse

                    @error('nilai')
                        <p class="border-t border-hairline bg-rose-50 px-4 py-3 text-[13px] font-medium text-rose-800">{{ $message }}</p>
                    @enderror
                    <p class="bg-canvas px-4 py-3 text-[13px] text-muted">
                        MB = {{ \App\Enums\Nilai::MulaiBerkembang->label() }}, BSH = {{ \App\Enums\Nilai::BerkembangSesuaiHarapan->label() }}, BSB = {{ \App\Enums\Nilai::BerkembangSangatBaik->label() }}
                    </p>
                </div>
            </section>

            {{-- Right rail --}}
            <aside class="space-y-6">
                <section>
                    <h3 class="text-[16px] font-semibold text-heading">Catatan Personal Guru</h3>
                    <textarea
                        wire:model="catatan"
                        rows="4"
                        placeholder="Tulis hal menonjol dari anak: kebiasaan, sikap, atau kejadian khusus selama semester ini."
                        class="mt-2 w-full resize-none rounded border border-hairline bg-surface px-4 py-3 text-[15px] text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none"
                    ></textarea>
                </section>

                <section>
                    <h3 class="text-[16px] font-semibold text-heading">Foto Dokumentasi (1-3 foto)</h3>
                    <div class="mt-2 flex gap-4">
                        @foreach ($this->narasi?->dokumentasi ?? [] as $foto)
                            <figure class="relative w-[120px]">
                                <img src="{{ Storage::url($foto->Foto) }}" alt="{{ $foto->Keterangan }}" class="h-[110px] w-[120px] rounded border border-hairline object-cover">
                                <figcaption class="mt-1 truncate text-[13px] text-muted">{{ $foto->Keterangan }}</figcaption>
                                @if ($rapor->bolehDiubah())
                                    <button type="button" wire:click="hapusFoto('{{ $foto->Dokumentasi_ID }}')" class="absolute -top-1.5 -right-1.5 flex size-5 items-center justify-center rounded-full bg-surface text-muted ring-1 ring-hairline transition-colors hover:text-rose-700" aria-label="Hapus foto">&times;</button>
                                @endif
                            </figure>
                        @endforeach

                        @if ($rapor->bolehDiubah() && ($this->narasi?->dokumentasi->count() ?? 0) < Dokumentasi::MAKS_FOTO)
                            <label class="flex h-[110px] w-[120px] cursor-pointer flex-col items-center justify-center gap-1 rounded border border-dashed border-hairline-strong bg-surface text-muted transition-colors hover:border-accent hover:text-accent-deep">
                                <span class="text-[22px] leading-none">+</span>
                                <span class="text-[13px]">Unggah</span>
                                <input type="file" accept="image/jpeg,image/png" multiple class="sr-only" wire:model="fotoBaru">
                            </label>
                        @endif
                    </div>
                    <div wire:loading wire:target="fotoBaru" class="mt-2 text-[13px] text-muted">Mengunggah foto...</div>
                    @error('fotoBaru.*')
                        <p class="mt-2 text-[13px] font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </section>

                <section>
                    <div class="flex items-center justify-between">
                        <h3 class="text-[16px] font-semibold text-heading">Narasi Rapor</h3>
                        <x-ui.button type="button" :variant="filled($draftNarasi) ? 'secondary' : 'primary'" wire:click="generateNarasi" wire:loading.attr="disabled" wire:target="generateNarasi" :disabled="! $rapor->bolehDiubah()">
                            <span wire:loading.remove wire:target="generateNarasi">{{ filled($draftNarasi) ? 'Generate Ulang' : 'Generate Narasi' }}</span>
                            <span wire:loading wire:target="generateNarasi">Menyusun narasi...</span>
                        </x-ui.button>
                    </div>
                    <textarea
                        wire:model="draftNarasi"
                        rows="7"
                        placeholder="Draf narasi hasil AI akan tampil di sini dan masih bisa disunting guru sebelum disimpan."
                        @disabled(! $rapor->bolehDiubah())
                        class="mt-2 w-full resize-y rounded border border-hairline bg-surface px-4 py-3 text-[15px] leading-relaxed text-body placeholder:text-placeholder focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none disabled:bg-canvas"
                    ></textarea>
                    @error('draftNarasi')
                        <p class="mt-1 text-[13px] font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div class="inline-flex h-6 items-center gap-1.5 rounded px-2.5 text-[13px] ring-1 ring-inset ring-hairline">
                            <span class="text-muted">Status narasi:</span>
                            <span class="text-label">{{ $this->statusNarasi->label() }}</span>
                            @if ($this->narasi?->Tanggal_Generate)
                                <span class="text-muted">- draf AI {{ $this->narasi->Tanggal_Generate->translatedFormat('j M Y H.i') }}</span>
                            @endif
                        </div>
                        <x-ui.button type="button" variant="primary" size="sm" wire:click="simpanNarasi" :disabled="! $rapor->bolehDiubah()">Simpan Narasi</x-ui.button>
                    </div>
                </section>
            </aside>
        </div>
    </div>
    </div>

    <x-rapor.modal-ajukan :rapor="$rapor" :modal="$modalAjukan" :kekurangan="$kekurangan" />
</div>
