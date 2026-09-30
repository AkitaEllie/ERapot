<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::guest')]
    #[Validate('required|email')]
    public string $Email = '';

    #[Validate('required|string')]
    public string $Password = '';

    public bool $Ingat_saya = false;

    public bool $Lihat_sandi = false;

    /**
     * @throws ValidationException
     */
    public function masuk(): void
    {
        $this->validate();

        if (! Auth::attempt(
            ['Email' => $this->Email, 'password' => $this->Password],
            $this->Ingat_saya,
        )) {
            throw ValidationException::withMessages([
                'Email' => __('Email atau kata sandi salah'),
            ]);
        }

        $this->reset(['Password']);

        // Auth::attempt() already rotates the session id to prevent fixation.

        // Not on ERD/CD: the intended URL is dropped rather than followed. Laravel's
        // intended() replays the stored absolute URL with no host check, so a stale or
        // tampered value can bounce the user to another origin, and the landing page here
        // is role-dependent anyway (see the dashboard route).
        session()->forget('url.intended');

        $this->redirect(route('dashboard'), navigate: true);
    }
};
?>

<div class="flex min-h-screen items-center justify-center p-6">
    <div class="w-full max-w-[500px] rounded-xl border border-hairline bg-surface px-10 pt-14 pb-4">
        <div class="flex flex-col items-center">
            <div class="flex size-24 items-center justify-center rounded-2xl bg-sunken">
                <img src="/images/logo-tk-cktc.png" alt="Logo TK Cinta Kasih Tzu Chi" class="h-[52px] w-[86px] object-contain">
            </div>

            <h1 class="mt-7 text-[22px] font-semibold text-heading">Website E-Rapor</h1>
            <p class="text-[13px] text-muted-soft">TK Cinta Kasih Tzu Chi</p>
        </div>

        <form wire:submit="masuk" class="mt-[42px] space-y-5">
            <div>
                <x-ui.label for="email">Email</x-ui.label>
                <x-ui.text-input
                    id="email"
                    type="email"
                    wire:model="Email"
                    autocomplete="username"
                    class="mt-1"
                    :error="$errors->has('Email')"
                    placeholder="nama@sekolah.sch.id"
                />
            </div>

            <div>
                <x-ui.label for="password">Kata Sandi</x-ui.label>
                <div class="relative mt-1">
                    <x-ui.text-input
                        id="password"
                        :type="$Lihat_sandi ? 'text' : 'password'"
                        wire:model="Password"
                        autocomplete="current-password"
                        class="pr-11"
                        :error="$errors->has('Password')"
                    />
                    <button
                        type="button"
                        wire:click="$toggle('Lihat_sandi')"
                        class="absolute top-1/2 right-3 flex size-5 -translate-y-1/2 items-center justify-center rounded bg-sunken text-muted transition-colors hover:bg-sunken-strong"
                        aria-label="{{ $Lihat_sandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi' }}"
                    >
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" class="size-3.5">
                            @if ($Lihat_sandi)
                                <path d="M3 3l14 14M8.2 8.3a2.4 2.4 0 0 0 3.4 3.4M6.3 6.4C4.4 7.5 3 9 2.5 10c1 2.2 4 5 7.5 5 1.2 0 2.3-.3 3.3-.8M12.6 12.1c2-1.2 3.5-2.7 4-3.6-1-2.2-4-5-7.5-5-.5 0-1 0-1.4.1" stroke-linecap="round" stroke-linejoin="round"/>
                            @else
                                <path d="M2.5 10C3.5 7.8 6.5 5 10 5s6.5 2.8 7.5 5c-1 2.2-4 5-7.5 5s-6.5-2.8-7.5-5Z" stroke-linejoin="round"/>
                                <circle cx="10" cy="10" r="2.2"/>
                            @endif
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2.5 text-[12px] text-muted">
                    <input
                        type="checkbox"
                        wire:model="Ingat_saya"
                        class="size-4 rounded border-hairline-strong text-primary accent-primary focus:ring-0"
                    >
                    Ingat saya
                </label>

                <a href="#" class="text-[12px] text-muted transition-colors hover:text-label">Lupa sandi?</a>
            </div>

            <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Masuk</span>
                <span wire:loading>Memasukkan...</span>
            </x-ui.button>
        </form>

        @if ($errors->has('Email') || $errors->has('Password'))
            <div class="mt-5 flex items-center gap-3 rounded-lg border border-notice-border bg-notice-bg px-4 py-3" role="alert">
                <svg viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0 text-sunken-strong">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm0-11.5a.9.9 0 0 1 .9.9v4a.9.9 0 1 1-1.8 0v-4a.9.9 0 0 1 .9-.9Zm0 7.4a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd"/>
                </svg>
                <p class="text-[12px] text-body">{{ $errors->first('Email') ?: $errors->first('Password') }}</p>
            </div>
        @endif
    </div>
</div>
