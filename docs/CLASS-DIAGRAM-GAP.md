# Class Diagram Gap Analysis

Audit of `docs/copy of master -DIAGRAM SKRIPSI.drawio.xml` against the
implementation, as of the report-layout build.

**Source:** 25 methods across 14 classes.
**Split:** the persona classes `Guru`, `TataUsaha` and `KepalaSekolah` were
deliberately **not** created, so their behaviour lives on the domain models. Every
method on a real model is audited below.

| Bucket | Count |
| --- | --- |
| Implemented | 21 |
| Deliberate stubs | 4 |
| Missing | 0 |

---

## Implemented (21)

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
| `Rapor` | `eksporPDF(): string` | Renders the document to PDF, stores it, returns the path |
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
`UbahPasswordTest.php`, `DokumentasiTest.php` and `EksporRaporTest.php`.

## Deliberate stubs (4)

| Method | State | Why it waits |
| --- | --- | --- |
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

### Report document methods

The diagram names `Rapor::eksporPDF()` but not the work around it, so three
additions carry it:

| Method | Why it exists |
| --- | --- |
| `Rapor::bagian(): Collection` | The document's subject list, shared by the preview and the export |
| `Rapor::seksi(): array` | That list grouped into Section A, Section B and the table pages |
| `Rapor::berkasPdf(): string` | Where the exported file lives, so the row and the disk agree |
| `Dokumentasi::url()` / `berkasAbsolut()` | The browser needs a web URL, dompdf needs a filesystem path |

`eksporPDF()` renders the report's **complete** document and takes no viewer, as
the diagram declares. Which parts a teacher may download is the caller's concern:
the preview component narrows the sections for a subject teacher, so one renderer
serves both requirements.

`RaporDownloadController` is also beyond the diagram. The diagram models producing
the file but not delivering it, and that transport is HTTP's business.

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

## Export notes

`eksporPDF()` writes to `rapor/{Rapor_ID}.pdf` on the public disk and remembers
the path in `Rapor.File_PDF`, so a repeat export overwrites the same file and the
stored copy cannot drift from the report it came from.

The document is shared with the preview: one view (`components/rapor/dokumen`)
renders both, so they cannot disagree. The two targets differ in styling only —
the browser uses the compiled Tailwind bundle, dompdf cannot see it, so the
document's design is restated in `gaya-pdf.blade.php` as plain CSS and uses tables
rather than flex or grid, which dompdf does not lay out.

Two points where the export is not a faithful copy of the reference:

- **Parent reflection.** The two free-text boxes are a printed form for
  handwriting, so they render empty and need no storage.
- **Growth status.** `Rapor` stores a single `Status_Pertumbuhan`, but the
  reference prints a growth status beside each of tinggi, berat and lingkar
  kepala. The same stored value is printed three times.

Exported reports are ~6 MB because dompdf must embed the whole CJK font; see
`storage/fonts/README.md` for why it cannot be subsetted further.

## Settled against the master copy

| Item | Resolution |
| --- | --- |
| `Rapor::hitungProgres()` | Removed; the master copy already omits it |
| `Pembelajaran::getGuru(): Guru` | Retyped to `User`; the master copy reflects it |
| `TahunAjaran::gantiSemester(): void` | Restored to the diagram's signature; the dashboard measures the draft count itself |
| Persona classes | Not created; behaviour redistributed onto domain models |
