<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tata Usaha: the accounts that can sign in, wireframes TU-02 and TU-03.
 *
 * Homeroom classes are set on Data Kelas and subjects on Penugasan Mengajar, so
 * this screen only shows them; it never assigns them.
 */
new #[Layout('layouts::app', ['active' => 'pengguna'])]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cari = '';

    #[Url(as: 'peran')]
    public string $filterPeran = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public bool $formTerbuka = false;

    public ?string $sedangDiubah = null;

    public string $nama = '';

    public string $email = '';

    public string $nip = '';

    public string $telepon = '';

    public string $peran = 'GURU';

    public bool $aktif = true;

    public string $kataSandi = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['cari', 'filterPeran', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    /** @return Collection<int, Role> */
    #[Computed]
    public function daftarPeran(): Collection
    {
        return Role::query()->orderBy('Nama_Role')->get();
    }

    #[Computed]
    public function total(): int
    {
        return User::query()->count();
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function pengguna(): LengthAwarePaginator
    {
        return User::query()
            ->with(['role', 'kelasDiampu'])
            ->when($this->cari !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('Nama', 'like', '%'.$this->cari.'%')
                ->orWhere('Email', 'like', '%'.$this->cari.'%')))
            ->when($this->filterPeran !== '', fn ($query) => $query->where('Role_ID', $this->filterPeran))
            ->when($this->filterStatus !== '', fn ($query) => $query->where('Is_Active', $this->filterStatus === 'aktif'))
            ->orderBy('Nama')
            ->paginate(10);
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->formTerbuka = true;
    }

    public function ubah(string $id): void
    {
        $user = User::query()->findOrFail($id);

        $this->resetForm();
        $this->sedangDiubah = $user->getKey();
        $this->nama = $user->Nama;
        $this->email = $user->Email;
        $this->nip = (string) $user->NIP;
        $this->telepon = (string) $user->No_Telepon;
        $this->peran = $user->Role_ID;
        $this->aktif = $user->Is_Active;
        $this->formTerbuka = true;
    }

    public function tutup(): void
    {
        $this->formTerbuka = false;
        $this->resetForm();
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:100',
                Rule::unique('users', 'Email')->ignore($this->sedangDiubah, 'User_ID'),
            ],
            'nip' => ['nullable', 'string', 'max:20'],
            'telepon' => ['nullable', 'string', 'max:15'],
            'peran' => ['required', Rule::exists('roles', 'Role_ID')],
            'aktif' => ['boolean'],
            'kataSandi' => [$this->sedangDiubah ? 'nullable' : 'required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Data sudah terdaftar: email ini sudah digunakan pengguna lain.',
        ], [
            'nama' => 'nama lengkap',
            'peran' => 'peran',
            'kataSandi' => 'kata sandi',
            'telepon' => 'no. telepon',
        ]);

        if ($this->sedangDiubah === auth()->id() && ! $data['aktif']) {
            $this->addError('aktif', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');

            return;
        }

        $user = $this->sedangDiubah !== null
            ? User::query()->findOrFail($this->sedangDiubah)
            : new User;

        $user->fill([
            'Nama' => $data['nama'],
            'Email' => $data['email'],
            'NIP' => $data['nip'] ?: null,
            'No_Telepon' => $data['telepon'] ?: null,
            'Role_ID' => $data['peran'],
            'Is_Active' => $data['aktif'],
        ]);

        if (filled($data['kataSandi'])) {
            // The Password attribute is cast to "hashed", so it hashes on assignment.
            $user->Password = $data['kataSandi'];
        }

        $user->save();

        session()->flash('sukses', 'Data berhasil disimpan.');
        $this->tutup();
    }

    public function ubahStatus(string $id): void
    {
        $user = User::query()->findOrFail($id);

        if ($user->getKey() === auth()->id()) {
            session()->flash('galat', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');

            return;
        }

        $user->forceFill(['Is_Active' => ! $user->Is_Active])->save();

        session()->flash('sukses', $user->Nama.($user->Is_Active ? ' diaktifkan kembali.' : ' dinonaktifkan dan tidak dapat login.'));
    }

    private function resetForm(): void
    {
        $this->reset('sedangDiubah', 'nama', 'email', 'nip', 'telepon', 'peran', 'aktif', 'kataSandi');
        $this->resetValidation();
    }
};

?>

<div>
    <x-app.header title="Data Guru & Pengguna" :subtitle="$this->total.' pengguna terdaftar'">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" wire:click="tambah">Tambah Pengguna</x-ui.button>
        </x-slot:actions>
    </x-app.header>

    <x-ui.flash />

    <div class="flex shrink-0 items-center gap-4 border-b border-hairline bg-surface px-8 py-4">
        <div class="w-[420px]"><x-ui.input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama atau email..." aria-label="Cari pengguna" /></div>
        <div class="w-[200px]">
            <x-ui.select wire:model.live="filterPeran" aria-label="Filter peran">
                <option value="">Semua peran</option>
                @foreach ($this->daftarPeran as $role)
                    <option value="{{ $role->Role_ID }}">{{ $role->Nama_Role }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="w-[180px]">
            <x-ui.select wire:model.live="filterStatus" aria-label="Filter status">
                <option value="">Semua status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </x-ui.select>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="overflow-hidden rounded border border-hairline bg-surface">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-hairline bg-canvas text-[13px] text-muted">
                        <th class="w-14 px-4 py-3 text-right font-medium">No</th>
                        <th class="px-4 py-3 text-left font-medium">Nama</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="w-44 px-4 py-3 text-left font-medium">Peran</th>
                        <th class="w-40 px-4 py-3 text-left font-medium">Wali Kelas</th>
                        <th class="w-28 px-4 py-3 text-left font-medium">Status</th>
                        <th class="w-52 px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pengguna as $user)
                        <tr wire:key="{{ $user->getKey() }}" class="border-b border-hairline last:border-b-0 hover:bg-canvas">
                            <td class="px-4 py-3 text-right text-[15px] text-muted">{{ $this->pengguna->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $user->Nama }}</td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $user->Email }}</td>
                            <td class="px-4 py-3"><x-ui.badge>{{ $user->role?->Nama_Role ?? $user->Role_ID }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-[15px] text-body">{{ $user->kelasDiampu->pluck('Nama_Kelas')->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($user->Is_Active)
                                    <x-ui.badge tone="positif">Aktif</x-ui.badge>
                                @else
                                    <x-ui.badge>Nonaktif</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubah('{{ $user->getKey() }}')">Ubah</x-ui.button>
                                    @if ($user->getKey() === auth()->id())
                                        {{-- Nobody switches their own account off. --}}
                                    @elseif ($user->Is_Active)
                                        <x-ui.button type="button" variant="secondary" size="sm"
                                            wire:click="ubahStatus('{{ $user->getKey() }}')"
                                            wire:confirm="Nonaktifkan {{ $user->Nama }}? Akun ini tidak akan bisa login."
                                        >Nonaktifkan</x-ui.button>
                                    @else
                                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="ubahStatus('{{ $user->getKey() }}')">Aktifkan</x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-[15px] text-muted">Tidak ada pengguna yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-ui.table-footer :paginator="$this->pengguna" satuan="pengguna" />
        </div>
    </div>

    @if ($formTerbuka)
        <x-ui.modal :title="$sedangDiubah ? 'Ubah Pengguna' : 'Tambah Pengguna'" subtitle="Akun digunakan untuk login ke website e-Rapor sesuai perannya." close="tutup">
            <form id="form-pengguna" wire:submit="simpan" class="space-y-4">
                <x-ui.field label="Nama Lengkap" for="nama" error="nama">
                    <x-ui.input id="nama" wire:model="nama" :invalid="$errors->has('nama')" autofocus />
                </x-ui.field>

                <x-ui.field label="Email" for="email" error="email">
                    <x-ui.input type="email" id="email" wire:model="email" :invalid="$errors->has('email')" />
                </x-ui.field>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui.field label="Peran" for="peran" error="peran">
                        <x-ui.select id="peran" wire:model="peran">
                            @foreach ($this->daftarPeran as $role)
                                <option value="{{ $role->Role_ID }}">{{ $role->Nama_Role }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Status Akun" for="aktif" error="aktif">
                        <x-ui.select id="aktif" wire:model.boolean="aktif">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="NIP (opsional)" for="nip" error="nip">
                        <x-ui.input id="nip" wire:model="nip" />
                    </x-ui.field>

                    <x-ui.field label="No. Telepon (opsional)" for="telepon" error="telepon">
                        <x-ui.input id="telepon" wire:model="telepon" />
                    </x-ui.field>
                </div>

                <x-ui.field
                    :label="$sedangDiubah ? 'Kata Sandi Baru' : 'Kata Sandi Awal'"
                    for="kata-sandi"
                    error="kataSandi"
                    :hint="$sedangDiubah ? 'Kosongkan bila kata sandi tidak diganti.' : 'Minimal 8 karakter. Pengguna dapat menggantinya sendiri di Profil Saya.'"
                >
                    <x-ui.input type="password" id="kata-sandi" wire:model="kataSandi" autocomplete="new-password" :invalid="$errors->has('kataSandi')" />
                </x-ui.field>

                <p class="text-[13px] text-muted">Penetapan wali kelas dilakukan di menu Data Kelas, sedangkan mata pelajaran yang diampu diatur di menu Penugasan Mengajar.</p>
            </form>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" wire:click="tutup">Batal</x-ui.button>
                <x-ui.button type="submit" form="form-pengguna" variant="primary">Simpan</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
