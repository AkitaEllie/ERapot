# Class Diagram Gap Analysis

Audit of `docs/copy of master -DIAGRAM SKRIPSI.drawio.xml` against the
implementation, as of the report-layout build.

**Source:** 25 methods across 14 classes.
**Split:** the persona classes `Guru`, `TataUsaha` and `KepalaSekolah` were
deliberately **not** created, so their behaviour lives on the domain models. Every
method on a real model is audited below.

| Bucket | Count |
| --- | --- |
| Implemented | 20 |
| Deliberate stubs | 5 |
| Missing | 0 |

---

## Implemented (20)

| Class | Method | Notes |
| --- | --- | --- |
| `Role` | `getHakAkses(): array` | Backs `punyaHakAkses()` and the `hak` route middleware |
| `User` | `login(email, password): bool` | Verifies credentials, then `Auth::login` |
| `User` | `logout(): void` | Ends the session, invalidates it, rotates CSRF |
| `User` | `ubahPassword(password): void` | Called from the profile screen |
| `Kelas` | `getDaftarRapor(?Semester)` | Relation-backed accessor |
| `Rapor` | `ajukanPersetujuan(): void` | Draft → waiting approval |
| `Rapor` | `setujui(user): void` | Approves and finalises written narratives |
| `Rapor` | `kembalikanRevisi(catatan): void` | Returns with a revision note |
| `TahunAjaran` | `gantiSemester(): void` | Flips the active term and opens drafts |
| `Siswa` | `getRiwayatRapor()` | Relation-backed accessor |
| `ProgramPengembangan` | `getIndikator(?Semester)` | Relation-backed accessor |
| `IndikatorCapaian` | `salinIndikator(Semester): void` | Copies a term forward, idempotently |
| `NilaiSiswa` | `isiNilai(nilai): void` | Sets the level |
| `NilaiSiswa` | `tandaiUntukNarasi(): void` | Flags the row for the narrative |
| `MataPelajaran` | `getProgram()` | Relation-backed accessor |
| `Narasi` | `simpanDraft(teks): void` | Saves the draft text |
| `Narasi` | `simpanNarasiFinal(teks): void` | Saves the final text |
| `Dokumentasi` | `unggahFoto(file): void` | Stores the file, honours `MAKS_FOTO` |
| `Dokumentasi` | `hapusFoto(): void` | Deletes the file and the row |
| `Pembelajaran` | `getGuru(): User` | Relation-backed accessor |

Covered by `tests/Feature/DiagramAccessorsTest.php`, `SalinIndikatorTest.php`,
`GantiSemesterTest.php`, `SetujuiRaporTest.php`, `LogoutTest.php`,
`UbahPasswordTest.php` and `DokumentasiTest.php`.

## Deliberate stubs (5)

| Method | State | Why it waits |
| --- | --- | --- |
| `Rapor::eksporPDF(): string` | `return ''` | Deferred by request until the layout is agreed. dompdf is installed; the on-screen layout now matches `docs/contoh-rapor.pdf`, so this is the next step. |
| `GeminiApi::kirimPrompt(prompt): string` | `return ''` | The diagram gives no provider, model, key or failure contract |
| `Narasi::susunPrompt(): string` | `return ''` | Feeds the method above |
| `TahunAjaran::aktifkan(): void` | empty | No screen sets the active year |
| `Kelas::hitungProgres(): int` | empty | Removed from the master diagram; the dashboard counts drafts itself |

## Additions beyond the diagram

Recorded here because the class diagram does not carry them.

### Accessor methods

`Siswa::belumDitempatkan()`, `Kelas::denganProgres()`,
`Pembelajaran::tugaskan()` and `Rapor::pempatkan()` fill gaps the screens need
which the diagram does not name. They sit on the classes whose data they shape.

### Role rows

The diagram models one `Role` class with no teacher sub-roles. Two role rows now
exist for teachers, because the export scope differs:

| Role | Binding | Produces |
| --- | --- | --- |
| `GURUWALI` | `Kelas.User_ID` (one class) | The full report book |
| `GURU` | `Pembelajaran.User_ID` (all classes they teach) | Their own subject page |

The binding already existed in the ERD — `Kelas.User_ID` marks the homeroom
teacher and `Pembelajaran.User_ID` the subject teacher. Only the `Role` row is
new, so the application can tell the two apart for menus and exports.

### Row-level capabilities

`dashboard.guru` is redundant now that `/dashboard` dispatches on role, and
`persetujuan` duplicates `review` on the same route. Both are dead weight.

### `ADMIN` has no sidebar or dashboard branch

`/dashboard` renders an empty sidebar for `ADMIN`, because the role dispatcher's
`match` has no `ADMIN` arm. Administrators can still reach every guarded route via
the wildcard capability.

## Export layout notes

The on-screen preview now reproduces `docs/contoh-rapor.pdf`; `eksporPDF()` will
reuse the same three partials (`identitas`, `seksi-prosa`, `seksi-tabel`,
`penutup`).

Two gaps remain for a faithful PDF:

- **CJK fonts.** The `Bahasa Mandarin` page contains Chinese. dompdf's default
  Helvetica stack has no coverage, so a CJK-capable TTF must be bundled.
- **Parent reflection.** The two free-text boxes are a printed form for
  handwriting, so they render empty and need no storage.

One ERD limitation shows in the output: `Rapor` stores a single
`Status_Pertumbuhan`, but the reference prints a growth status beside each of
tinggi, berat and lingkar kepala. The same stored value is printed three times.

## Settled against the master copy

| Item | Resolution |
| --- | --- |
| `Rapor::hitungProgres()` | Removed; the master copy already omits it |
| `Pembelajaran::getGuru(): Guru` | Retyped to `User`; the master copy reflects it |
| `TahunAjaran::gantiSemester(): void` | Restored to the diagram's signature; the dashboard measures the draft count itself |
| Persona classes | Not created; behaviour redistributed onto domain models |
