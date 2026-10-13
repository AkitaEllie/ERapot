{{-- Font stack for the report document.

     The Mandarin pages contain Chinese, which the app's Inter face cannot render.
     dompdf has no automatic fallback across families, so the report declares one
     stack and the CJK face is registered from storage/fonts. On screen the font is
     loaded normally; in the PDF dompdf reads it from the same path. --}}
<style>
    @font-face {
        font-family: 'NotoSansCJK';
        font-style: normal;
        font-weight: normal;
        src: url('{{ storage_path('fonts/NotoSansCJK.ttf') }}') format('truetype');
    }

    .rapor-dokumen {
        font-family: 'Inter', 'NotoSansCJK', sans-serif;
    }

    .rapor-dokumen :where(td, th, p, h1, h2, h3, span) {
        font-family: inherit;
    }
</style>
