<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Placeholder page: the sidebar names this screen but the wireframe never
 * designed it, so there is nothing functional here yet.
 */
new #[Layout('layouts::app', ['active' => 'tahun-ajaran'])]
class extends Component
{
    //
};

?>

<x-app.placeholder-page
    title="Tahun Ajaran"
    subtitle="Kelola periode tahun ajaran dan semester"
    figma-screen="Tidak ada — hanya disebut di sidebar frame 07"
    method="TataUsaha::kelolaTahunAjaran(): void, TahunAjaran::aktifkan(): void"
/>
