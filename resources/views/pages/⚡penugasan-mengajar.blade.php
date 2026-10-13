<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Placeholder page: the sidebar names this screen but the wireframe never
 * designed it, so there is nothing functional here yet.
 */
new #[Layout('layouts::app', ['active' => 'penugasan-mengajar'])]
class extends Component
{
    //
};

?>

<x-app.placeholder-page
    title="Penugasan Mengajar"
    subtitle="Tetapkan guru pengampu per kelas dan mata pelajaran"
    figma-screen="Tidak ada — hanya disebut di sidebar frame 07"
    method="TataUsaha::aturPembelajaran(): void, Pembelajaran::tugaskan(kelas, mapel, user)"
/>
