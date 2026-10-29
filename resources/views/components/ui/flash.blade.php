{{-- Session feedback shared by every screen: one green line for success, one red for errors. --}}
@if (session('sukses'))
    <div role="status" class="border-b border-hairline bg-accent-soft px-8 py-2.5 text-[14px] text-accent-deep">{{ session('sukses') }}</div>
@endif
@if (session('galat'))
    <div role="alert" class="border-b border-hairline bg-rose-50 px-8 py-2.5 text-[14px] text-rose-800">{{ session('galat') }}</div>
@endif
