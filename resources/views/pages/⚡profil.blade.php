<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app', ['active' => 'profil'])]
class extends Component
{
    public string $Password_sekarang = '';

    public string $Password = '';

    public string $Password_confirmation = '';

    /**
     * Change the signed-in user's password.
     *
     * The current password is required so an unattended session cannot be used to
     * lock the real owner out of their own account.
     */
    public function simpan(): void
    {
        $user = auth()->user();

        $this->validate([
            'Password_sekarang' => ['required', 'string'],
            'Password' => ['required', 'string', 'min:8', 'confirmed', Rule::notIn([$this->Password_sekarang])],
        ]);

        if (! Hash::check($this->Password_sekarang, $user->Password)) {
            $this->addError('Password_sekarang', 'Kata sandi saat ini tidak cocok.');

            return;
        }

        $user->ubahPassword($this->Password);

        $this->reset('Password_sekarang', 'Password', 'Password_confirmation');

        session()->flash('sukses', 'Kata sandi berhasil diperbarui.');
    }
};

?>

<div>
    <x-app.header
        title="Profil Saya"
        subtitle="Ubah kata sandi akun Anda"
    />

    @if (session('sukses'))
        <div class="border-b border-hairline bg-accent-soft px-8 py-2.5 text-[14px] text-accent-deep">{{ session('sukses') }}</div>
    @endif

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="grid grid-cols-[360px_1fr] items-start gap-6">
            {{-- Identity --}}
            <section class="rounded border border-hairline bg-surface p-6">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-full bg-sunken text-[15px] font-semibold text-label">
                        {{ auth()->user()?->initials() }}
                    </span>
                    <div class="leading-tight">
                        <p class="text-[16px] font-semibold text-heading">{{ auth()->user()?->Nama }}</p>
                        <p class="text-[14px] text-muted">{{ auth()->user()?->role?->Nama_Role }}</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-3 text-[15px]">
                    <div class="flex gap-3">
                        <dt class="w-28 shrink-0 text-muted">Email</dt>
                        <dd class="min-w-0 truncate text-body">{{ auth()->user()?->Email }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-28 shrink-0 text-muted">NIP</dt>
                        <dd class="text-body">{{ auth()->user()?->NIP ?: '-' }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-28 shrink-0 text-muted">No. Telepon</dt>
                        <dd class="text-body">{{ auth()->user()?->No_Telepon ?: '-' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Password form --}}
            <section class="rounded border border-hairline bg-surface p-6">
                <h2 class="text-[16px] font-semibold text-heading">Ubah Kata Sandi</h2>
                <p class="mt-1 text-[14px] text-muted">Minimal 8 karakter.</p>

                <form wire:submit="simpan" class="mt-5 max-w-[420px] space-y-4">
                    <div>
                        <x-ui.label for="sekarang">Kata Sandi Saat Ini</x-ui.label>
                        <x-ui.text-input
                            id="sekarang"
                            type="password"
                            wire:model="Password_sekarang"
                            autocomplete="current-password"
                            class="mt-1"
                            :error="$errors->has('Password_sekarang')"
                        />
                        @error('Password_sekarang') <p class="mt-1.5 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-ui.label for="baru">Kata Sandi Baru</x-ui.label>
                        <x-ui.text-input
                            id="baru"
                            type="password"
                            wire:model="Password"
                            autocomplete="new-password"
                            class="mt-1"
                            :error="$errors->has('Password')"
                        />
                        @error('Password') <p class="mt-1.5 text-[13px] text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-ui.label for="ulangi">Ulangi Kata Sandi Baru</x-ui.label>
                        <x-ui.text-input
                            id="ulangi"
                            type="password"
                            wire:model="Password_confirmation"
                            autocomplete="new-password"
                            class="mt-1"
                        />
                    </div>

                    <x-ui.button type="submit" variant="primary">Simpan Kata Sandi</x-ui.button>
                </form>
            </section>
        </div>
    </div>
</div>
