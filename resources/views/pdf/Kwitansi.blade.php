<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $pembayaran->receipt_number }}</title>
    <style>
        /* A5 landscape = 210×148mm. Margin dikecilkan dan seluruh ukuran
           dipadatkan agar kwitansi muat dalam SATU halaman — versi
           sebelumnya pecah jadi dua. */
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px; color: #1c1917;
            margin: 0; padding: 7mm;
        }
        .kwitansi { border: 1.5px solid #57534e; border-radius: 6px; padding: 12px 16px; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 0; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #78716c; }
        .primary { color: #D97757; }

        .kop { border-bottom: 2px solid #57534e; padding-bottom: 8px; }
        .kop-nama { margin: 0; font-size: 14px; font-weight: bold; color: #D97757; }
        .kop-alamat { margin: 2px 0 0 0; font-size: 8px; color: #78716c; }
        .judul { margin: 0; font-size: 17px; letter-spacing: 2px; font-weight: bold; }
        .nomor { margin: 3px 0 0 0; font-size: 9px; }
        .badge {
            display: inline-block; background: #FBF5F1; color: #A34F33;
            border: 1px solid #EDD3C4; border-radius: 8px;
            padding: 1px 6px; font-size: 7.5px; font-weight: bold;
            text-transform: uppercase; letter-spacing: .4px; margin-top: 3px;
        }

        .isi td { padding: 3.5px 0; }
        .label { width: 24%; font-weight: bold; color: #57534e; }
        .titik { width: 2%; text-align: center; color: #78716c; }
        .nilai { width: 74%; border-bottom: 1px dotted #a8a29e; }

        .nominal-box {
            background: #faf9f5; border: 1.5px solid #57534e;
            border-radius: 4px; padding: 7px 14px;
            font-size: 15px; font-weight: bold; display: inline-block;
        }
        .terbilang { font-size: 8px; font-style: italic; color: #57534e; margin: 5px 0 0 0; }
        .ttd-nama { margin: 0; font-weight: bold; border-top: 1px solid #57534e; display: inline-block; padding: 3px 12px 0 12px; font-size: 10px; }
        .footer { font-size: 7px; color: #a8a29e; margin: 3px 0 0 0; }
        .lunas { color: #059669; font-weight: bold; }
        .belum { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>

@php
    $logo = null;
    foreach (['Logo.png', 'logo.png', 'favicon.ico'] as $kandidat) {
        $p = public_path($kandidat);
        if (file_exists($p)) {
            $logo = 'data:image/' . pathinfo($p, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($p));
            break;
        }
    }

    $kategoriLabel = \App\Models\Keuangan\Inbound\Pembayaran::KATEGORI[$pembayaran->kategori] ?? 'Pemasukan';
    $pihak  = $pembayaran->siswa?->name ?: ($pembayaran->nama_pihak ?: 'Pihak Umum');
    $lunas  = $pembayaran->tagihan && $pembayaran->tagihan->remaining_balance <= 0;

    $untuk = $pembayaran->tagihan
        ? ($pembayaran->tagihan->header?->title ?? 'Tagihan')
        : $kategoriLabel;
@endphp

<div class="kwitansi">

    {{-- KOP --}}
    <table class="layout kop">
        <tr>
            @if($logo)
                <td width="9%" style="vertical-align: middle;">
                    <img src="{{ $logo }}" style="height: 34px; width: auto;" alt="">
                </td>
                <td width="51%" style="vertical-align: middle; padding-left: 8px;">
            @else
                <td width="60%" style="vertical-align: middle;">
            @endif
                <p class="kop-nama">{{ config('app.name') }}</p>
                @if(config('app.school_address'))
                    <p class="kop-alamat">{{ config('app.school_address') }}</p>
                @endif
            </td>
            <td width="40%" class="text-right">
                <p class="judul">KWITANSI</p>
                <p class="nomor">No. <strong>{{ $pembayaran->receipt_number }}</strong></p>
                <span class="badge">{{ $kategoriLabel }}</span>
            </td>
        </tr>
    </table>

    {{-- ISI --}}
    <table class="layout isi" style="margin-top: 10px;">
        <tr>
            <td class="label">Telah terima dari</td>
            <td class="titik">:</td>
            <td class="nilai">
                <strong>{{ $pihak }}</strong>@if($pembayaran->siswa?->nis) <span class="muted">· NIS {{ $pembayaran->siswa->nis }}</span>@endif
            </td>
        </tr>
        <tr>
            <td class="label">Untuk pembayaran</td>
            <td class="titik">:</td>
            <td class="nilai">
                {{ $untuk }}
                @if($pembayaran->tagihan)
                    <span class="muted">· {{ $pembayaran->tagihan->invoice_number }}@if($pembayaran->tagihan->header?->period) · {{ $pembayaran->tagihan->header->period }}@endif</span>
                @endif
                @if($pembayaran->catatan)
                    <span class="muted">· {{ \Illuminate\Support\Str::limit($pembayaran->catatan, 60) }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Metode</td>
            <td class="titik">:</td>
            <td class="nilai">
                {{ $pembayaran->payment_method }}
                <span class="muted">· diterima di {{ $pembayaran->rekening?->account_name ?? '—' }}</span>
            </td>
        </tr>
        @if($pembayaran->tagihan)
            <tr>
                <td class="label">Sisa tagihan</td>
                <td class="titik">:</td>
                <td class="nilai">
                    <span class="{{ $lunas ? 'lunas' : 'belum' }}">
                        Rp {{ number_format($pembayaran->tagihan->remaining_balance, 0, ',', '.') }}@if($lunas) — LUNAS @endif
                    </span>
                </td>
            </tr>
        @endif
    </table>

    {{-- NOMINAL & TANDA TANGAN --}}
    <table class="layout" style="margin-top: 14px;">
        <tr>
            <td width="55%" valign="top">
                <div class="nominal-box">Rp {{ number_format($pembayaran->nominal, 0, ',', '.') }},-</div>
                <p class="terbilang">
                    # {{ ucfirst(trim(\App\Support\Terbilang::of((int) $pembayaran->nominal))) }} rupiah #
                </p>
            </td>
            <td width="45%" class="text-center">
                <p class="muted" style="margin: 0; font-size: 8.5px;">
                    {{ $pembayaran->payment_date?->translatedFormat('d F Y') }}
                </p>
                <p style="margin: 2px 0 26px 0; font-size: 9.5px;">Penerima,</p>
                <p class="ttd-nama">{{ $pembayaran->penyetuju?->name ?? 'Bendahara' }}</p>
                <p class="footer">
                    Sah tanpa tanda tangan basah · dicetak {{ now()->translatedFormat('d/m/Y H:i') }}
                </p>
            </td>
        </tr>
    </table>

</div>

</body>
</html>