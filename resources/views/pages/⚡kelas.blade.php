<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Placeholder page: the sidebar names this screen but the wireframe never
 * designed it, so there is nothing functional here yet.
 */
new #[Layout('layouts::app', ['active' => 'kelas'])]
class extends Component
{
    //
};

?>

<x-app.placeholder-page
    title="Data Kelas"
    subtitle="Kelola daftar kelas dan wali kelas"
    figma-screen="Tidak ada — hanya disebut di sidebar frame 07"
    method="TataUsaha::kelolaDataKelas(): void"
/>
