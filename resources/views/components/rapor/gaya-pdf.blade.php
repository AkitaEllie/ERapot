{{-- Stylesheet for the exported document.

     The on-screen preview is styled with Tailwind utilities, which exist only as a
     compiled bundle the browser loads. dompdf renders standalone markup and cannot
     see that bundle, so the document's design is restated here in plain CSS and
     inlined into the export.

     It styles by element wherever the partials use plain markup, which keeps the
     two in step with less to remember. Keep this alongside the partials. --}}
<style>
    @font-face {
        font-family: 'NotoSansCJK';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path('fonts/NotoSansCJK.ttf') }}') format('truetype');
    }

    @page {
        margin: 28px 32px;
    }

    body {
        margin: 0;
    }

    .rapor-dokumen {
        font-family: 'Inter', 'NotoSansCJK', sans-serif;
        font-size: 13px;
        line-height: 1.55;
        color: #111;
    }

    .rapor-dokumen p {
        margin: 0 0 6px 0;
    }

    .rapor-dokumen h1,
    .rapor-dokumen h2 {
        margin: 0;
        font-weight: bold;
    }

    /* Headings: the cover's centred title block and each page's title. */
    .rapor-dokumen h1 {
        font-size: 15px;
        text-align: center;
        text-transform: uppercase;
    }

    .rapor-dokumen .judul-halaman {
        font-size: 15px;
        text-align: center;
        text-transform: uppercase;
    }

    /* Tables carry the document's structure: identity, indicator and signature. */
    .rapor-dokumen table {
        width: 100%;
        border-collapse: collapse;
    }

    .rapor-dokumen td,
    .rapor-dokumen th {
        border: 1px solid #333;
        padding: 3px 6px;
        vertical-align: top;
    }

    .rapor-dokumen thead th {
        background-color: #f2f2f2;
        text-align: center;
    }

    /* Buttons and spacers that exist only for the screen. */
    .rapor-dokumen .border-0 {
        border: 0;
        padding: 0;
    }

    /* Section heading bar, filled light blue as in the reference document. */
    .rapor-dokumen .kepala-seksi {
        background-color: #dfe8f5;
        text-align: center;
        font-weight: bold;
        text-transform: uppercase;
        border: 1px solid #333;
        padding: 4px 6px;
        margin: 0;
    }

    .rapor-dokumen .isi-seksi {
        border: 1px solid #333;
        border-top: 0;
        padding: 8px;
    }

    .rapor-dokumen .catatan {
        border: 1px solid #333;
        border-top: 0;
    }

    .rapor-dokumen .catatan td {
        border: 0;
    }

    .rapor-dokumen .foto-strip {
        margin-top: 8px;
    }

    .rapor-dokumen .foto-strip td {
        border: 1px solid #333;
        padding: 6px;
        text-align: center;
    }

    .rapor-dokumen img {
        border: 1px solid #333;
    }

    .rapor-dokumen .rata-kanan,
    .rapor-dokumen .text-right {
        text-align: right;
    }

    /* Keep each subject together on one page where it fits. */
    .rapor-dokumen section {
        page-break-inside: avoid;
    }
</style>
