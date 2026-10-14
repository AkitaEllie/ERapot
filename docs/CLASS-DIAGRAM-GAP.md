# Class Diagram Gap Analysis

Audit of `docs/copy of master -DIAGRAM SKRIPSI.drawio.xml` against the
implementation, as of the report-layout build.

**Source:** 25 methods across 14 classes.
**Split:** the persona classes `Guru`, `TataUsaha` and `KepalaSekolah` were
deliberately **not** created, so their behaviour lives on the domain models. Every
method on a real model is audited below.

| Bucket | Count |
| --- | --- |
| Implemented | 23 |
| Deliberate stubs | 2 |
| Missing | 0 |

---

## Implemented (23)

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
| `Narasi` | `susunPrompt(): string` | Builds the prompt from the chosen indicators and the teacher's note |
| `Narasi` | `simpanDraft(teks): void` | Saves the draft text |
| `Narasi` | `simpanNarasiFinal(teks): void` | Saves the final text |
| `GeminiApi` | `kirimPrompt(prompt): string` | Delegates to the configured provider driver |
| `Dokumentasi` | `unggahFoto(file): void` | Stores the file, honours `MAKS_FOTO` |
| `Dokumentasi` | `hapusFoto(): void` | Deletes the file and the row |
| `Pembelajaran` | `getGuru(): User` | Relation-backed accessor |

Covered by `tests/Feature/DiagramAccessorsTest.php`, `SalinIndikatorTest.php`,
`GantiSemesterTest.php`, `SetujuiRaporTest.php`, `LogoutTest.php`,
`UbahPasswordTest.php`, `DokumentasiTest.php`, `EksporRaporTest.php`,
`PenyediaNarasiTest.php` and `SusunPromptTest.php`.

## Deliberate stubs (2)

| Method | State | Why it waits |
| --- | --- | --- |
| `TahunAjaran::aktifkan(): void` | empty | No screen sets the active year |
| `Kelas::hitungProgres(): int` | empty | Removed from the master diagram; the dashboard counts drafts itself |

## Additions beyond the diagram

Recorded here because the class diagram does not carry them.

### Narration providers

The diagram models one «service» `GeminiAPI` with `API_Key`, `Model_AI` and
`kirimPrompt()`, which ties the application to a single vendor. The behaviour is
the same whatever answers the prompt, so the vendor now sits behind a contract:

| Piece | Role |
| --- | --- |
| `App\Contracts\PenyediaNarasi` | The contract: `kirimPrompt(prompt): string` and `nama()` |
| `App\Services\Narasi\PenyediaHttp` | Shared config, timeout and failure handling |
| `App\Services\Narasi\GeminiPenyedia` | Google's `generateContent` endpoint |
| `App\Services\Narasi\OpenAiPenyedia` | The chat-completions shape |
| `App\Services\Narasi\InstruksiNarasi` | The standing instructions sent as the system message |
| `config/ai.php` | Which provider, and each one's key, model, base URL and timeout |

The class keeps its diagram name and signature, so nothing calling `GeminiApi`
changed; it now delegates to the configured driver. `API_Key` and `Model_AI`
survive as configuration rather than literals.

The OpenAI driver is not only for OpenAI: pointing `OPENAI_BASE_URL` at a host
that speaks the same shape (Groq, Together, Ollama, vLLM) is enough to switch to
it, which is why the second driver covers most alternatives for the price of one.

Adding a provider is one class implementing the contract plus one config entry.

### The prompt is split, and the split is deliberate

`Narasi::susunPrompt()` returns the **user** prompt: only the facts that differ
between students (name, class, semester, subject, the selected indicators with
their levels, the teacher's note), in a fixed section order.

The **system** prompt is `InstruksiNarasi::SISTEM`: generic, identical on every
call, and held in the driver rather than passed by the caller. Two reasons:

1. It is not per-student, so it does not belong in the caller's data.
2. A prefix that never changes is what a provider can cache.

That second point is measured, not assumed. On the configured endpoint the same
request reports `cached_tokens` rising to the full prompt on the second call:

| Call | Prompt tokens | Cached |
| --- | --- | --- |
| 1 (cold) | 471 | 427 |
| 2 | 471 | 471 |
| 3 | 471 | 471 |

`PenyediaHttp::catatPemakaian()` logs prompt, cached and completion tokens at
debug level, so whether the cache is helping stays answerable.

The system prompt is written in Indonesian, because the earlier English phrasing
let English leak into the narration. Its length and structure rules follow the
school's own report samples, and its worked example uses `[nama]` rather than a
real name, which would otherwise teach the model to reuse that name.

### Narration failure handling

The diagram gives no failure contract. An absent key, a rejected request or an
empty answer now raises `App\Exceptions\NarasiTidakTersedia`, which carries a
message naming the provider and, when the provider supplies one, its own error
text. The screen shows it and invites the teacher to write the narrative by hand,
rather than the stub's silent empty string.

### Model pinning

`AI_PROVIDER`'s sibling setting `OPENAI_MODEL` is pinned to a concrete model
rather than an endpoint's router alias. A router alias (`kenari-free`) answered
from a different model on each call, which showed up as an unpredictable latency
spread (2.6s to 68s) and inconsistent narration quality.

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
