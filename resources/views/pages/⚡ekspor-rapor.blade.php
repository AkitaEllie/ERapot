<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Placeholder page: the sidebar names this screen but the wireframe never
 * designed it, so there is nothing functional here yet.
 */
new #[Layout('layouts::app', ['active' => 'ekspor-rapor'])]
class extends Component
{
    //
};

?>

<x-app.placeholder-page
    title="Daftar Rapor & Export"
    subtitle="Daftar dan ekspor rapor seluruh kelas yang diampu"
    figma-screen="Tidak ada — hanya disebut di sidebar frame 02, 05, 06"
    method="Guru::eksporRaporPDF(rapor): string, Rapor::eksporPDF(): string"
/>
