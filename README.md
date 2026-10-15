# E-Rapor

Aplikasi rapor untuk KB/TK Cinta Kasih Tzu Chi. Guru wali kelas menyusun narasi
perkembangan tiap anak, mata pelajaran diisi oleh guru pengampunya, dan kepala
sekolah menyetujui sebelum rapor dicetak menjadi berkas PDF.

Dibangun dengan Laravel 13, Livewire dan Tailwind CSS. Basis data MySQL.

## Kebutuhan

- PHP 8.3 atau lebih baru
- Composer 2
- Node.js 22 atau lebih baru
- MySQL atau MariaDB

## Pemasangan cepat

```sh
./install.sh          # Linux dan macOS
install.bat           # Windows
```

Skrip memasang dependensi, membuat `.env`, mengisi kunci aplikasi, menjalankan
migrasi beserta data contoh, menautkan penyimpanan, lalu membangun aset frontend.

Bila basis data belum ada, skrip berhenti dan memberi tahu perintah yang perlu
dijalankan. Skrip tidak membuat basis data sendiri, karena itu memerlukan
kredensial root dan bisa berbeda di tiap komputer.

Setelah itu jalankan aplikasi:

```sh
./run.sh              # Linux dan macOS
run.bat               # Windows
```

Aplikasi tersedia di <http://localhost:8000>.

### Akun contoh

Seluruh akun memakai kata sandi `rahasia-kuat`.

| Peran | Surel | Bisa apa |
| --- | --- | --- |
| Guru Wali Kelas | `sari@tkcktc.sch.id` | Satu kelas, menyusun dan mengekspor seluruh buku rapor |
| Guru Mata Pelajaran | `ratna@tkcktc.sch.id` | Mata pelajaran yang diampu di semua kelas |
| Tata Usaha | `andre@tkcktc.sch.id` | Akun, siswa, kelas, mata pelajaran, penugasan |
| Kepala Sekolah | `mei@tkcktc.sch.id` | Meninjau dan menyetujui rapor |

## Pemasangan manual

```sh
composer setup        # memasang, memigrasi, mengisi data contoh, membangun aset
composer dev          # menjalankan server, queue, log dan Vite sekaligus
```

Perintah lepas yang lain:

```sh
composer test         # Pint, PHPStan dan Pest
composer lint         # memperbaiki gaya penulisan kode
composer types:check  # PHPStan
```

## Penyiapan

Salin `.env.example` menjadi `.env` lalu sesuaikan bila perlu.

### Basis data

```env
DB_CONNECTION=mysql
DB_DATABASE=ERapot
DB_USERNAME=root
DB_PASSWORD=
```

### Bahasa dan tanggal

```env
APP_LOCALE=id
```

Rapor adalah dokumen berbahasa Indonesia dan mencetak tanggal dengan
`translatedFormat`, jadi lokal wajib `id` agar nama bulan terbaca "Oktober",
bukan "October".

### Pembuatan narasi

Narasi bisa disusun dengan bantuan model bahasa. Penyedianya dapat diganti
lewat konfigurasi, tidak tertanam pada satu vendor.

```env
AI_PROVIDER=openai

OPENAI_API_KEY=isi-kunci-anda
OPENAI_MODEL=deepseek-v4-1-flash
OPENAI_BASE_URL=https://kenari.id/v1
```

Penyedia `openai` memakai bentuk chat-completions, sehingga Groq, Together,
Ollama dan vLLM dapat dipakai hanya dengan mengubah `OPENAI_BASE_URL`. Penyedia
`gemini` memakai endpoint `generateContent` milik Google. Menambah penyedia baru
berarti satu kelas yang mengimplementasikan `App\Contracts\PenyediaNarasi` dan
satu entri di `config/ai.php`.

Bila kunci belum diisi, tombol Generate Narasi menampilkan pesan yang
menjelaskan dan guru dapat menulis narasinya sendiri.

## Arsip rujukan

Berkas rujukan asli (ERD, diagram kelas, contoh rapor) berada di `docs/` dan
sengaja tidak ikut disimpan di repositori ini. `docs/CLASS-DIAGRAM-GAP.md` akan
muncul kembali setelah berkas tersebut disalin ke folder itu, dan berisi catatan
tentang apa saja yang menyimpang dari ERD maupun diagram kelas.

Aturan yang dipakai: apa pun yang tidak dideklarasikan ERD atau diagram kelas
dicatat di berkas itu, bukan lewat penanda di dalam kode.

## Struktur

```
app/
  Contracts/         kontrak layanan, termasuk penyedia narasi
  Enums/             nilai tetap dari ERD, beserta pembantu tampilan
  Exceptions/        galat domain
  Http/Middleware/   EnsureHakAkses, penjaga hak akses per peran
  Models/            model Eloquent, satu per tabel ERD
  Services/          layanan domain, termasuk penyedia narasi
config/ai.php        pemilihan penyedia narasi
resources/views/
  components/rapor/  bagian-bagian dokumen rapor, dipakai pratinjau dan PDF
  pages/             layar Livewire
tests/Feature/       seluruh pengujian, berbasis basis data
```

## Catatan teknis

**Pembagian peran guru.** Guru wali kelas terikat pada satu kelas lewat
`Kelas.User_ID`, sedangkan guru mata pelajaran mengajar di banyak kelas lewat
`Pembelajaran.User_ID`. Keduanya sudah ada di ERD; yang ditambahkan hanya baris
`Role` agar aplikasi dapat membedakan keduanya.

**Bentuk narasi mengikuti halaman.** Mata pelajaran `intrakurikuler` dan
`kokurikuler` mengisi bagian prosa buku rapor dengan dua sampai tiga paragraf,
sedangkan `muatan_lokal` mengisi kotak Catatan yang lebih sempit dengan satu
paragraf. `Jenis_Mapel` yang menentukan, sama seperti penentuan templat halaman.

**Berkas PDF.** Ekspor memakai dompdf. Berkasnya besar, sekitar 6 MB, karena
font CJK harus disematkan utuh: penyaring font bawaan dompdf merusak font
tersebut. Penjelasannya ada di `storage/fonts/README.md`.

**Pengujian.** Seluruh pengujian berada di `tests/Feature` karena
`tests/Pest.php` hanya mengikat `TestCase` dan `RefreshDatabase` di sana.
