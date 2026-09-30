# Class Diagram Gap Analysis

Audit of `docs/-DIAGRAM SKRIPSI.drawio.xml` against the implementation, as of the
11-screen build.

**Source:** 43 methods across 17 classes.
**Split:** 17 methods belong to `Guru`, `TataUsaha` and `KepalaSekolah` — three
persona classes that were deliberately **not** created, so their logic was
redistributed onto domain models. The remaining **26** sit on real models and are
audited below.

| Bucket | Count |
| --- | --- |
| A. In diagram, not implemented | 12 |
| B. Not in diagram, but needed | 7 |
| C. In diagram, not used | 6 + 17 persona methods |

---

## A. In the diagram, not implemented

### A1. Stubs that break a shipped screen

These are the ones a reviewer will click on. The screens render completely, but
the control does nothing.

- [ ] **`Dokumentasi::unggahFoto(UploadedFile $file): void`** — empty body.
      Screen 04's "Unggah" slot accepts no photo.
- [ ] **`Dokumentasi::hapusFoto(): void`** — empty body. The photo delete (×) on
      screen 04 does nothing.
- [ ] **`Narasi::susunPrompt(): string`** — returns `''`. Feeds the prompt builder.
- [ ] **`GeminiApi::kirimPrompt(string $prompt): string`** — returns `''`. Screen
      04's "Generate Narasi" reports that AI is unconfigured.
      *Diagram gives no provider, model, key or failure contract — needs a spec
      before it can be built.*
- [ ] **`Rapor::eksporPDF(): string`** — returns `''`. Screen 06's "Export PDF"
      does nothing. `barryvdh/laravel-dompdf` is already installed, so this is the
      cheapest of the five.
- [x] **`IndikatorCapaian::salinIndikator(Semester $semester): void`** —
      implemented as a per-indicator copy that keeps `Program_ID`, `Jenjang`,
      `Tipe` and the school year, changes the semester, and takes a fresh
      `Kode_KD`. It skips indicators that already exist in the target semester,
      so running it twice is safe. The screen 05 action copies the previous term
      forward into the active semester, honouring the mapel/jenjang/tipe filters,
      and reports how many were added. Covered by
      `tests/Feature/SalinIndikatorTest.php` (7 tests) and verified in the
      browser.
- [x] **`TahunAjaran::gantiSemester(): int`** — implemented. Flips
      `Semester_Aktif` and opens a `BelumDiisi` draft for every already-placed
      student, keeping each in the class from their most recent report. Students
      who have never been placed are skipped, because a report needs a class;
      they are handled by Penempatan Siswa. Returns the number of drafts created,
      which the dashboard reports. Covered by `tests/Feature/GantiSemesterTest.php`
      (8 tests) and verified live: semester flipped to Genap, 7 ganjil reports
      preserved, 7 new `belum_diisi` drafts created.

### A2. Stubs with no screen depending on them

- [x] **`Role::getHakAkses(): array`** — implemented as the capability map that
      now backs B1. `punyaHakAkses()` was added alongside it.
- [x] **`User::logout(): void`** — implemented: ends the session for that user,
      invalidates it so a fixated session id cannot survive, and rotates the CSRF
      token. The logout route now calls it instead of inlining the calls, so the
      method is live code for the interface's logout button to use. Covered by
      `tests/Feature/LogoutTest.php` (8 tests) and verified in the browser.
- [x] **`User::login(string $email, string $password): bool`** — **removed.** It
      returned `false` unconditionally and nothing called it (the login screen
      uses `Auth::attempt`), so it was a method that could only fail. **The class
      diagram still lists it and should drop it** — see C3.
- [ ] **`User::ubahPassword(string $password): void`** — empty body. No profile
      screen exists (see B5).
- [ ] **`TahunAjaran::aktifkan(): void`** — empty body. No screen sets the active
      year; the seeder does it directly.

---

## B. Not in the diagram, but needed

- [x] **B1. Authorization enforcement — CLOSED.**
      `Role::getHakAkses()` now returns a capability map, `Role::punyaHakAkses()`
      checks it (`*` is the administrator wildcard), and
      `App\Http\Middleware\EnsureHakAkses` (aliased `hak`) guards each route with
      `['auth', 'hak:<capability>']`. Capability keys double as sidebar entry keys,
      so the menu and the guard read one vocabulary.

      Verified over real HTTP — the matrix is now strict:

      | Route | Guru | Tata Usaha | Kepala Sekolah |
      | --- | --- | --- | --- |
      | `/rapor`, `/indikator` | 200 | 403 | 403 |
      | `/siswa`, `/penempatan`, `/dashboard/tata-usaha` | 403 | 200 | 403 |
      | `/review-rapor`, `/dashboard/progres` | 403 | 403 | 200 |
      | `/dashboard` (role dispatcher) | 200 | 200 | 200 |

      Covered by `tests/Feature/AuthorizationTest.php` (26 tests).
- [ ] **B2. Student placement (screen 09).** No diagram method exists. Built
      inline as `Rapor::updateOrCreate`, which is the behaviour the design
      describes but the diagram never asked for.
- [ ] **B3. Tata Usaha dashboard computation (screen 07).** The "Perlu
      Dikerjakan" panel — unplaced students, classes without a homeroom teacher,
      subjects without an assigned teacher — has no method. The only progress
      method, `pantauProgresRapor()`, is on `KepalaSekolah`.
- [ ] **B4. Student CRUD and Excel import (screen 08).**
      `TataUsaha::kelolaDataSiswa(): void` carries no validation, return shape or
      import behaviour. "Import Excel" and "Tambah Siswa" are both placeholder
      flashes.
- [ ] **B5. Student detail and history (screen 08).** "Detail / Ubah" is a dead
      button, and `Siswa::getRiwayatRapor()` has no screen to serve.
- [x] **B6. Narrative finalisation on approval — closed.**
      `Rapor::setujui()` now promotes every written narrative to final, so the
      rule holds for any caller rather than only the review screen. Narratives
      with no text are deliberately left as drafts: there is nothing to finalise,
      and marking an empty one final would hide the gap. Narratives already final
      are left untouched, timestamp included. Covered by
      `tests/Feature/SetujuiRaporTest.php` (8 tests) and verified in the browser:
      approving a report moved all three of its narratives from `draft` to `final`.
- [x] **B7. Photo cap and indicator ordering — both closed.**
      The 1–3 photo cap is now a model invariant (`Dokumentasi::MAKS_FOTO`).
      Indicator ordering turned out to share a root cause with the copy work:
      `IndikatorCapaianFactory` set `Kode_KD` to `fake()->unique()->bothify('KD-##?')`,
      which filled the column with random values like `KD-42r`. Because
      `Kode_KD` is derived from `Indikator_ID` in a `creating` hook, a non-blank
      factory value suppressed the derivation, so `orderBy('Kode_KD')` sorted
      randomly and screen 04 rendered indicators shuffled. Removing `Kode_KD`
      from the factory makes the model derive it, giving `KD-2600001…5` in
      insertion order — which also happens to be the order the design shows.

---

## C. Settled against the master copy

Four earlier divergences were closed rather than recorded:

| Item | Resolution |
| --- | --- |
| `Rapor::hitungProgres()` | Removed by decision; the master copy already omits it |
| `Pembelajaran::getGuru(): Guru` | Retyped to `User` in code; the master copy already reflects it |
| 5 accessors typed `: list` | Equivalent to `Collection`; the master copy will be updated |
| `TahunAjaran::gantiSemester(): void` | Restored to the diagram's signature; the dashboard measures the draft count itself |
| `User::login(email, password): bool` | Reinstated and implemented — verifies the credentials then signs in |

`User::login()` is the diagram's signature working as intended. The login screen
still uses `Auth::attempt`, because it has no `User` instance to call the method
on; the method is available to anything that does.

### C1. Accessor methods — implemented

`Siswa::getRiwayatRapor()`, `Kelas::getDaftarRapor(?Semester)`,
`MataPelajaran::getProgram()`, `ProgramPengembangan::getIndikator(?Semester)` and
`Pembelajaran::getGuru()` are real methods over the relations they summarise.
Covered by `tests/Feature/DiagramAccessorsTest.php`.

### C2. Nothing in the diagram is missing

All 26 methods declared on real models now exist. The ERD needed no edits: it
records column types only, with no enum value sets and no nullability markers, so
the `Jenjang` reformat, the `BelumDiisi` state and the nullable homeroom teacher
could not have drifted it.

## What still is not aligned

**21 of 43 implemented. The 22 outstanding, in full:**

### 17 persona methods — behaviour exists, location differs

`Guru`, `TataUsaha` and `KepalaSekolah` were not created, so their methods sit on
the domain models. Nothing is missing behaviourally:

| Diagram | Implemented as |
| --- | --- |
| `Guru::isiNilaiIndikator` | `NilaiSiswa::isiNilai()` |
| `Guru::isiCatatanPersonal` | `Narasi::isiCatatanPersonal()` |
| `Guru::editNarasi` | `Narasi::editNarasi()` |
| `Guru::generateNarasi` | `GeminiApi::kirimPrompt()` + `Narasi::susunPrompt()` |
| `Guru::unggahDokumentasi` | `Dokumentasi::unggahFoto()` |
| `Guru::ajukanRapor` | `Rapor::ajukanPersetujuan()` |
| `Guru::eksporRaporPDF` | `Rapor::eksporPDF()` |
| `KepalaSekolah::setujuiRapor` | `Rapor::setujui()` |
| `KepalaSekolah::kirimCatatanRevisi` | `Rapor::kembalikanRevisi()` |
| `KepalaSekolah::reviewDrafRapor` | `Rapor::reviewDrafRapor()` |
| `KepalaSekolah::pantauProgresRapor` | none — screen 10 queries directly |
| `TataUsaha::*` (6) | none — see B2, B3, B4 |

`pantauProgresRapor()` and the six `TataUsaha` methods are the only ones with no
implementation anywhere.

### 5 stubs, all deliberate

| Method | State | Why it waits |
| --- | --- | --- |
| `Rapor::eksporPDF()` | `return ''` | deferred by request; dompdf is installed |
| `GeminiApi::kirimPrompt()` | `return ''` | deferred; the diagram gives no provider or key contract |
| `Narasi::susunPrompt()` | `return ''` | deferred; feeds the above |
| `TahunAjaran::aktifkan()` | `//` | no screen sets the active year |
| `User::ubahPassword()` | `//` | no profile screen |

The last two need screens rather than implementations — they have nowhere to be
called from.

## Progress

1. ~~**B1 authorization**~~ — **done** (26 tests, verified over HTTP).
2. ~~**A1 file handling**~~ — **done** (6 tests, verified in the browser; the
   1–3 cap is a model invariant, and the component no longer puts an
   `UploadedFile` into the `Foto` column).
3. ~~**A1 `gantiSemester`**~~ — **done** (8 tests, verified live).
4. ~~**A1 `salinIndikator`**~~ — **done** (7 tests, verified in the browser);
   it also uncovered and fixed the B7 ordering bug.
5. ~~**A2 `User::logout` / `User::login`**~~ — **done**: `logout()` implemented and
   wired to the route, `login()` deleted. The interface still has no logout
   button; when it arrives it can simply post to the existing route.
6. **C1–C3** — update the diagram so it matches the code, then re-audit. Removing
   `User::login` is part of this.

Deferred by request: `Rapor::eksporPDF()` (A1) and the AI narrative pair
(`Narasi::susunPrompt` / `GeminiApi::kirimPrompt`).
