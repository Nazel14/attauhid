<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapot {{ $santri->name }}</title>
    <style>
        @page {
            margin: 18mm 18mm 22mm 18mm;
        }

        * {
            box-sizing: border-box;
            }

            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                font-size: 10px;
                line-height: 1.55;
                color: #1f2937;
                background: #ffffff;
                margin: 0;
                padding: 0;
            }

        /* ================================================== */
        /* ACCENT BAR (atas dokumen)                           */
        /* ================================================== */
        .accent-bar {
            height: 4px;
            background-color: #D97757;
            margin: 0 0 22px 0;
            border-radius: 2px;
            font-size: 0;
            line-height: 0;
        }

        /* ================================================== */
        /* HEADER                                              */
        /* ================================================== */
        .doc-header {
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-header-table {
            width: 100%;
        }
        .doc-header-table td {
            vertical-align: top;
        }
        .doc-brand {
            font-size: 8.5px;
            font-weight: 700;
            color: #D97757;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .doc-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.5px;
            line-height: 1.1;
            margin-bottom: 3px;
        }
        .doc-subtitle {
            font-size: 9.5px;
            color: #6b7280;
            font-weight: 400;
        }
        .doc-meta {
            text-align: right;
            font-size: 8.5px;
            color: #6b7280;
            line-height: 1.6;
        }
        .doc-meta-label {
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 7.5px;
            margin-bottom: 1px;
        }
        .doc-meta-value {
            font-size: 9.5px;
            color: #111827;
            font-weight: 600;
        }

        /* ================================================== */
        /* INFO SANTRI                                         */
        /* ================================================== */
        .santri-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }
        .santri-table {
            width: 100%;
        }
        .santri-table td {
            vertical-align: top;
            padding: 3px 0;
            width: 50%;
        }
        .field-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 1px;
        }
        .field-value {
            font-size: 10px;
            color: #111827;
            font-weight: 600;
        }

        /* ================================================== */
        /* PERIODE BADGE                                       */
        /* ================================================== */
        .period-line {
            text-align: center;
            margin-bottom: 18px;
            padding: 8px 14px;
            background: #FBF0EB;
            border-left: 3px solid #D97757;
            border-radius: 3px;
            font-size: 9px;
            color: #A85638;
        }
        .period-line .period-label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 8px;
            margin-right: 4px;
        }
        .period-line .period-value {
            font-weight: 700;
            color: #7C3D26;
        }

        /* ================================================== */
        /* SECTION HEADING                                     */
        /* ================================================== */
        .sect-heading {
            margin: 0 0 12px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        .sect-heading-table {
            width: 100%;
        }
        .sect-heading-table td {
            vertical-align: middle;
        }
        .sect-heading-bar {
            width: 3px;
            background: #D97757;
            height: 14px;
            display: inline-block;
        }
        .sect-heading-text {
            font-size: 11px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding-left: 8px;
        }
        .sect-heading-meta {
            text-align: right;
            font-size: 8px;
            color: #9ca3af;
            font-weight: 500;
        }

        /* ================================================== */
        /* STATS CARDS                                         */
        /* ================================================== */
        .stats-grid {
            width: 100%;
            margin-bottom: 14px;
        }
        .stats-grid td {
            vertical-align: top;
            width: 50%;
            padding: 0 4px;
        }
        .stats-grid td:first-child { padding-left: 0; }
        .stats-grid td:last-child  { padding-right: 0; }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        .stat-card-header {
            background: #f9fafb;
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8.5px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card-body {
            padding: 10px 12px 12px 12px;
        }

        .stat-row {
            width: 100%;
        }
        .stat-row td {
            padding: 4px 0;
            border-bottom: 1px dotted #e5e7eb;
        }
        .stat-row tr:last-child td {
            border-bottom: none;
        }
        .stat-row td.lbl {
            font-size: 9px;
            color: #6b7280;
        }
        .stat-row td.val {
            text-align: right;
            font-size: 9.5px;
            font-weight: 700;
            color: #111827;
        }

        /* Highlighted total */
        .stat-highlight {
            background: #f3f4f6;
            margin-top: 8px;
            padding: 8px 10px;
            border-radius: 3px;
            border-left: 3px solid #D97757;
        }
        .stat-highlight-table {
            width: 100%;
        }
        .stat-highlight-lbl {
            font-size: 8px;
            font-weight: 700;
            color: #D97757;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .stat-highlight-val {
            text-align: right;
            font-size: 14px;
            font-weight: 700;
            color: #7C3D26;
        }

        /* ================================================== */
        /* PAGE BREAK                                          */
        /* ================================================== */
        .page-break { page-break-before: always; }
        .avoid-break { page-break-inside: avoid; }

        /* ================================================== */
        /* CATEGORY BLOCK                                      */
        /* ================================================== */
        .cat-block {
            margin-bottom: 14px;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        .cat-heading {
            background: #f9fafb;
            padding: 9px 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        .cat-heading-table {
            width: 100%;
        }
        .cat-heading-table td {
            vertical-align: middle;
        }
        .cat-name {
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cat-count {
            text-align: right;
            font-size: 8.5px;
            color: #6b7280;
            font-weight: 600;
        }
        .cat-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #D97757;
            margin-right: 6px;
        }
        .cat-dot-hafalan  { background: #D97757; }
        .cat-dot-murajaah { background: #F59E0B; }
        .cat-dot-tilawah  { background: #14B8A6; }

        /* ================================================== */
        /* TABLE                                               */
        /* ================================================== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin: 0;
        }
        table.data-table thead th {
            background: #ffffff;
            padding: 8px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        table.data-table tbody td {
            padding: 7px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            font-size: 9px;
        }
        table.data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .tc { text-align: center; }
        .surat-name {
            font-weight: 700;
            color: #111827;
            font-size: 9.5px;
        }
        .ayat-range {
            color: #9ca3af;
            font-size: 8.5px;
            margin-left: 2px;
        }
        .row-num {
            color: #9ca3af;
            font-size: 8.5px;
            font-weight: 500;
        }

        /* ================================================== */
        /* NILAI BADGE                                         */
        /* ================================================== */
        .nilai {
            display: inline-block;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 10px;
            font-size: 9px;
            min-width: 30px;
            text-align: center;
        }
        .nilai-a {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .nilai-b {
            background: #FBF0EB;
            color: #A85638;
            border: 1px solid #F0CBBC;
        }
        .nilai-c {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde68a;
        }
        .count-pill {
            display: inline-block;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8.5px;
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        /* ================================================== */
        /* PRESENSI TABLE                                      */
        /* ================================================== */
        .presensi-wrap {
            width: 100%;
            margin: 0 0 18px 0;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        .presensi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .presensi-table thead th {
            background: #f9fafb;
            color: #6b7280;
            padding: 9px 14px;
            text-align: left;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            border-bottom: 1px solid #e5e7eb;
        }
        .presensi-table tbody td {
            padding: 9px 14px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        .presensi-table tbody tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background: #f9fafb !important;
            font-weight: 700;
        }
        .total-row td {
            border-top: 2px solid #D97757 !important;
            color: #111827 !important;
            padding-top: 11px !important;
            padding-bottom: 11px !important;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-hadir {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-izin {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .badge-sakit {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde68a;
        }
        .badge-alpha {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* ================================================== */
        /* SIGNATURE                                           */
        /* ================================================== */
        .signature-section {
            margin-top: 50px;
            width: 100%;
        }
        .signature-section td {
            width: 50%;
            text-align: center;
            padding: 0 24px;
            vertical-align: top;
        }
        .signature-role {
            font-size: 9.5px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 60px;
        }
        .signature-name {
            border-top: 1px solid #1f2937;
            padding-top: 7px;
            font-weight: 600;
            font-size: 9px;
            color: #111827;
            display: inline-block;
            min-width: 170px;
        }
        .signature-role-sub {
            font-size: 8px;
            color: #9ca3af;
            margin-top: 2px;
            font-weight: 400;
        }

        /* ================================================== */
        /* FOOTER                                              */
        /* ================================================== */
        .footer {
            position: fixed;
            bottom: -18mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #9ca3af;
            padding: 8px 25mm;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }
        .footer-table {
            width: 100%;
        }
        .footer-left {
            text-align: left;
            color: #9ca3af;
        }
        .footer-center {
            text-align: center;
            color: #D97757;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .footer-right {
            text-align: right;
            color: #9ca3af;
        }
        .footer-spacer {
            height: 35px;
        }

        /* ================================================== */
        /* NO DATA                                             */
        /* ================================================== */
        .no-data {
            text-align: center;
            padding: 30px 20px;
            color: #9ca3af;
            font-size: 9.5px;
            background: #f9fafb;
            border: 1px dashed #e5e7eb;
            border-radius: 5px;
            margin-bottom: 14px;
        }
        .no-data-title {
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 2px;
        }

        /* ================================================== */
        /* KOP SURAT (gaya rapor)                              */
        /* ================================================== */
        .kop { width:100%; border-collapse:collapse; }
        .kop td { vertical-align:middle; }
        .kop-logo { width:72px; text-align:center; }
        .kop-logo img { width:64px; height:64px; }
        .kop-logo-ph {
            width:58px; height:58px; margin:0 auto;
            border:1px dashed #cbd5e1; border-radius:50%;
            color:#9ca3af; font-size:7px; line-height:58px; text-align:center;
        }
        .kop-center { text-align:center; padding:0 8px; }
        .kop-lembaga {
            font-size:16px; font-weight:700; color:#111827;
            letter-spacing:0.5px; text-transform:uppercase; line-height:1.15;
        }
        .kop-sub {
            font-size:9px; font-weight:700; color:#D97757;
            letter-spacing:1.2px; text-transform:uppercase; margin-top:2px;
        }
        .kop-alamat { font-size:8px; color:#6b7280; margin-top:4px; line-height:1.5; }
        .kop-rule-thick { border-top:2.5px solid #111827; height:0; font-size:0; line-height:0; margin-top:8px; }
        .kop-rule-thin  { border-top:0.8px solid #111827; height:0; font-size:0; line-height:0; margin-top:2px; }

        /* ================================================== */
        /* JUDUL RAPOR                                         */
        /* ================================================== */
        .report-title {
            text-align:center; font-size:13px; font-weight:700; color:#111827;
            letter-spacing:1.5px; text-transform:uppercase; margin:18px 0 2px 0;
        }
        .report-subtitle {
            text-align:center; font-size:9px; color:#6b7280;
            letter-spacing:0.5px; margin-bottom:16px;
        }

        /* ================================================== */
        /* IDENTITAS + FOTO 3x4                                */
        /* ================================================== */
        .identity-card {
            width:100%; border:1px solid #e5e7eb; border-radius:5px;
            border-collapse:separate; margin-bottom:14px;
        }
        .identity-card > tbody > tr > td { vertical-align:top; padding:14px 16px; }
        .id-row { width:100%; }
        .id-row td { padding:3.5px 0; font-size:10px; vertical-align:top; }
        .id-label { width:40%; color:#6b7280; font-weight:600; }
        .id-sep   { width:14px; color:#9ca3af; }
        .id-value { color:#111827; font-weight:700; }
        .identity-photo-cell { width:86px; text-align:center; border-left:1px solid #f3f4f6; }
        .photo-box {
            width:60px; height:78px; margin:0 auto;
            border:1px solid #d1d5db; border-radius:3px;
            overflow:hidden; background:#f9fafb;
        }
        .photo-box img { width:60px; height:78px; object-fit:cover; object-position:top; }
        .photo-ph { width:60px; height:78px; color:#9ca3af; font-size:9px; line-height:78px; text-align:center; }
    </style>
</head>
<body>

@php
    // ====== KONFIGURASI KOP (silakan edit sesuai data pondok) ======
    $namaLembaga   = 'Pondok Pesantren Tahfizh';
    $subLembaga    = 'Program Tahfizh Al-Qur\'an';
    $alamatLembaga = 'Jl. .............. No. .., Kota ........, Jawa Barat';
    $kontakLembaga = 'Telp. 0000-0000-0000  ·  email@domain.sch.id';

    // ====== LOGO: taruh file di public/images/logo-rapot.png ======
    $logoAbs  = public_path('images/logo-rapot.png');
    $logoData = file_exists($logoAbs)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoAbs))
        : null;

    // ====== FOTO SANTRI (otomatis dari users.profile_photo) ======
    $fotoRel  = $santri->user->profile_photo ?? null;
    $fotoAbs  = $fotoRel ? public_path('storage/'.$fotoRel) : null;
    $fotoData = ($fotoAbs && file_exists($fotoAbs))
        ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($fotoAbs))
        : null;

    // Kelas & guru sesuai KONTEKS rapot (histori → kelas lama; aktif → kelas sekarang)
    $kelasNama = ($kelasRapot->name ?? null) ?? ($santri->kelas->name ?? '-');
    $guruNama  = ($guruRapot->name  ?? null) ?? ($santri->guru->name  ?? '-');

    // ====== METODE: jilid-based (Iqro / Aisar) atau Al-Qur'an ======
    $isJilid       = $statistikSetoran['is_iqro'] ?? false;
    $metodeLabel   = $statistikSetoran['metode_label'] ?? 'Iqro';          // "Iqro" / "Aisar"
    $jilidAktif    = $statistikSetoran['target_jilid'] ?? 1;
    $halTersetor   = $statistikSetoran['total_halaman_iqro'] ?? 0;
    $halTotalJilid = $statistikSetoran['total_halaman_jilid'] ?? 0;
    $progressJilid = $statistikSetoran['progress_iqro'] ?? 0;
    $jilidDiketahui= $statistikSetoran['iqro_persen_diketahui'] ?? false;
    $metodeTeks    = $isJilid ? $metodeLabel.' — Jilid '.$jilidAktif : "Al-Qur'an";
@endphp

{{-- ============================================================ --}}
{{-- HALAMAN 1 : INFO SANTRI + STATISTIK                          --}}
{{-- ============================================================ --}}

<!-- KOP SURAT -->
<table class="kop">
    <tr>
        <td class="kop-logo">
            @if($logoData)
                <img src="{{ $logoData }}" alt="Logo">
            @else
                <div class="kop-logo-ph">LOGO</div>
            @endif
        </td>
        <td class="kop-center">
            <div class="kop-lembaga">{{ $namaLembaga }}</div>
            <div class="kop-sub">{{ $subLembaga }}</div>
            <div class="kop-alamat">{{ $alamatLembaga }}<br>{{ $kontakLembaga }}</div>
        </td>
        <td class="kop-logo"></td>
    </tr>
</table>
<div class="kop-rule-thick"></div>
<div class="kop-rule-thin"></div>

<!-- JUDUL RAPOR -->
<div class="report-title">Laporan Perkembangan Santri</div>
<div class="report-subtitle">Dicetak {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</div>

<!-- IDENTITAS SANTRI + FOTO -->
<table class="identity-card">
    <tr>
        <td class="identity-fields">
            <table class="id-row">
                <tr><td class="id-label">Nama Santri</td><td class="id-sep">:</td><td class="id-value">{{ $santri->name }}</td></tr>
                <tr><td class="id-label">Nomor Induk</td><td class="id-sep">:</td><td class="id-value">{{ $santri->nis ?? '-' }}</td></tr>
                <tr><td class="id-label">Kelas</td><td class="id-sep">:</td><td class="id-value">{{ $kelasNama }}</td></tr>
                <tr><td class="id-label">Guru Pembimbing</td><td class="id-sep">:</td><td class="id-value">{{ $guruNama }}</td></tr>
                <tr><td class="id-label">Metode</td><td class="id-sep">:</td><td class="id-value">{{ $metodeTeks }}</td></tr>
                @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <tr><td class="id-label">Tahun Ajaran</td><td class="id-sep">:</td><td class="id-value">{{ $tahunAjaranAktif->nama ?? '-' }}@if($tahunAjaranAktif->semester) · Semester {{ $tahunAjaranAktif->semester == 1 ? 'Ganjil' : 'Genap' }}@endif</td></tr>
                @endif
            </table>
        </td>
        <td class="identity-photo-cell">
            <div class="photo-box">
                @if($fotoData)
                    <img src="{{ $fotoData }}" alt="Foto">
                @else
                    <div class="photo-ph">3 × 4</div>
                @endif
            </div>
            <div class="photo-caption">Foto Santri</div>
        </td>
    </tr>
</table>

<!-- Periode -->
<div class="period-line">
    <span class="period-label">Periode</span>
    <span class="period-value">
        {{ \Carbon\Carbon::parse($tanggalMulai)->locale('id')->isoFormat('D MMMM Y') }}
        &nbsp;—&nbsp;
        {{ \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y') }}
    </span>
</div>

<!-- Section Heading -->
<div class="sect-heading">
    <table class="sect-heading-table">
        <tr>
            <td>
                <span class="sect-heading-bar"></span>
                <span class="sect-heading-text">Ringkasan Statistik</span>
            </td>
            <td class="sect-heading-meta">Halaman 1 / 3</td>
        </tr>
    </table>
</div>

<!-- Statistik -->
<table class="stats-grid">
    <tr>
        <!-- Statistik Setoran -->
        <td>
            <div class="stat-card">
                <div class="stat-card-header">Statistik Setoran</div>
                <div class="stat-card-body">
                <table class="stat-row">
                    <tr>
                        <td class="lbl">Total Setoran</td>
                        <td class="val">{{ $statistikSetoran['total_setoran'] }} kali</td>
                    </tr>
                    <tr>
                        <td class="lbl">Rata-rata Nilai</td>
                        <td class="val">{{ $statistikSetoran['rata_nilai'] }}</td>
                    </tr>
                    @if($isJilid)
                    <tr>
                        <td class="lbl">Jilid Aktif</td>
                        <td class="val">{{ $metodeLabel }} Jilid {{ $jilidAktif }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Halaman Disetor</td>
                        <td class="val">
                            {{ $halTersetor }}@if($jilidDiketahui) / {{ $halTotalJilid }}@endif halaman
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td class="lbl">Total Juz</td>
                        <td class="val">{{ $statistikSetoran['total_juz'] }} Juz</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#059669;margin-right:6px;"></span>Hafalan</td>
                        <td class="val">{{ $statistikSetoran['hafalan'] }} kali</td>
                    </tr>
                    <tr>
                        <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#2563eb;margin-right:6px;"></span>Murajaah</td>
                        <td class="val">{{ $statistikSetoran['murajaah'] }} kali</td>
                    </tr>
                    <tr>
                        <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#9333ea;margin-right:6px;"></span>Tilawah</td>
                        <td class="val">{{ $statistikSetoran['tilawah'] }} kali</td>
                    </tr>
                </table>

                @if($isJilid && $jilidDiketahui)
                <div class="stat-highlight">
                    <table class="stat-highlight-table">
                        <tr>
                            <td>
                                <div class="stat-highlight-lbl">Progress {{ $metodeLabel }} Jilid {{ $jilidAktif }}</div>
                                <div style="font-size:8.5px;color:#6b7280;">{{ $halTersetor }} dari {{ $halTotalJilid }} halaman (Hafalan)</div>
                            </td>
                            <td class="stat-highlight-val">{{ $progressJilid }}%</td>
                        </tr>
                    </table>
                </div>
                @elseif(!$isJilid)
                <div class="stat-highlight">
                    <table class="stat-highlight-table">
                        <tr>
                            <td>
                                <div class="stat-highlight-lbl">Pencapaian Target Hafalan</div>
                                <div style="font-size:8.5px;color:#6b7280;">{{ $statistikSetoran['total_juz_hafalan'] }} dari {{ $statistikSetoran['target_juz'] }} Juz</div>
                            </td>
                            <td class="stat-highlight-val">{{ $statistikSetoran['persentase_target'] }}%</td>
                        </tr>
                    </table>
                </div>
                @endif
            </div>
            </div>
        </td>

        <!-- Statistik Presensi -->
        <td>
            <div class="stat-card">
                <div class="stat-card-header">Statistik Presensi</div>
                <div class="stat-card-body">
                    <table class="stat-row">
                        <tr>
                            <td class="lbl">Total Hari</td>
                            <td class="val">{{ $statistikPresensi['total_hari'] }} hari</td>
                        </tr>
                        <tr>
                            <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#10b981;margin-right:6px;"></span>Hadir</td>
                            <td class="val">{{ $statistikPresensi['hadir'] }} hari</td>
                        </tr>
                        <tr>
                            <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#3b82f6;margin-right:6px;"></span>Izin</td>
                            <td class="val">{{ $statistikPresensi['izin'] }} hari</td>
                        </tr>
                        <tr>
                            <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#eab308;margin-right:6px;"></span>Sakit</td>
                            <td class="val">{{ $statistikPresensi['sakit'] }} hari</td>
                        </tr>
                        <tr>
                            <td class="lbl"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#ef4444;margin-right:6px;"></span>Alpha</td>
                            <td class="val">{{ $statistikPresensi['alpha'] }} hari</td>
                        </tr>
                    </table>

                    @if($statistikPresensi['total_hari'] > 0)
                    @php
                        $pctHadir = round(($statistikPresensi['hadir'] / $statistikPresensi['total_hari']) * 100, 1);
                    @endphp
                    <div class="stat-highlight">
                        <table class="stat-highlight-table">
                            <tr>
                                <td>
                                    <div class="stat-highlight-lbl">Persentase Kehadiran</div>
                                </td>
                                <td class="stat-highlight-val">{{ $pctHadir }}%</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- ============================================================ --}}
{{-- HALAMAN 2 : RIWAYAT SETORAN                                  --}}
{{-- ============================================================ --}}
<div class="page-break"></div>

<table class="accent-bar" style="width:100%;border-collapse:collapse;"><tr><td style="height:4px;background-color:#D97757;font-size:0;line-height:0;border-radius:2px;">&nbsp;</td></tr></table>

<div class="doc-header">
    <table class="doc-header-table">
        <tr>
            <td>
                <div class="doc-brand">Detail Pencatatan</div>
                <div class="doc-title">Riwayat Setoran</div>
                <div class="doc-subtitle">
                    {{ $santri->name }} &nbsp;·&nbsp; {{ $kelasNama }}
                    &nbsp;·&nbsp;
                    {{ \Carbon\Carbon::parse($tanggalMulai)->locale('id')->isoFormat('D MMM Y') }}
                    – {{ \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMM Y') }}
                </div>
            </td>
            <td class="doc-meta">
                <div class="doc-meta-label">Halaman</div>
                <div class="doc-meta-value">2 / 3</div>
            </td>
        </tr>
    </table>
</div>

<div class="sect-heading">
    <table class="sect-heading-table">
        <tr>
            <td>
                <span class="sect-heading-bar"></span>
                <span class="sect-heading-text">Setoran Berdasarkan Kategori</span>
            </td>
        </tr>
    </table>
</div>

@if(count($setorans) > 0)
    @foreach(['Hafalan', 'Murajaah', 'Tilawah'] as $kategori)
        @if(isset($setorans[$kategori]) && count($setorans[$kategori]) > 0)
        @php
            $dotClass = strtolower($kategori) === 'hafalan' ? 'cat-dot-hafalan' : (strtolower($kategori) === 'murajaah' ? 'cat-dot-murajaah' : 'cat-dot-tilawah');
        @endphp
        <div class="cat-block">
            <div class="cat-heading">
                <table class="cat-heading-table">
                    <tr>
                        <td>
                            <span class="cat-dot {{ $dotClass }}"></span>
                            <span class="cat-name">{{ $kategori }}</span>
                        </td>
                        <td class="cat-count">{{ count($setorans[$kategori]) }} {{ $isJilid ? 'jilid' : 'surat' }}</td>
                    </tr>
                </table>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:6%;" class="tc">No</th>
                        <th style="width:56%;">{{ $isJilid ? 'Jilid & Halaman' : 'Surah & Ayat' }}</th>
                        <th style="width:18%;" class="tc">Jumlah</th>
                        <th style="width:20%;" class="tc">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach($setorans[$kategori] as $suratKey => $data)
                    @php
                        // Baris ini jilid-based? (Iqro / Aisar / Ummi)
                        $barisJilid = (bool) preg_match('/^(Iqro|Aisar|Ummi)\s+Jilid\s+\d+/i', (string) $data['surat_start']);
                    @endphp
                    <tr>
                        <td class="tc"><span class="row-num">{{ str_pad($no++, 2, '0', STR_PAD_LEFT) }}</span></td>
                        <td>
                            <span class="surat-name">{{ $data['surat_start'] }}@if($data['surat_start'] != $data['surat_end']) — {{ $data['surat_end'] }}@endif</span>
                            <span class="ayat-range">{{ $barisJilid ? 'Halaman' : 'Ayat' }} {{ $data['ayat_start_min'] }}–{{ $data['ayat_end_max'] }}</span>
                        </td>
                        <td class="tc"><span class="count-pill">{{ $data['jumlah_setoran'] }}×</span></td>
                        <td class="tc">
                            <span class="nilai
                                @if($data['rata_nilai'] >= 90) nilai-a
                                @elseif($data['rata_nilai'] >= 75) nilai-b
                                @else nilai-c
                                @endif">
                                {{ $data['rata_nilai'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach
@else
    <div class="no-data">
        <div class="no-data-title">Tidak Ada Data Setoran</div>
        <div>Belum ada catatan setoran dalam periode yang dipilih</div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- HALAMAN 3 : PRESENSI + TANDA TANGAN                          --}}
{{-- ============================================================ --}}
<div class="page-break"></div>

<table class="accent-bar" style="width:100%;border-collapse:collapse;"><tr><td style="height:4px;background-color:#D97757;font-size:0;line-height:0;border-radius:2px;">&nbsp;</td></tr></table>

<div class="doc-header">
    <table class="doc-header-table">
        <tr>
            <td>
                <div class="doc-brand">Detail Pencatatan</div>
                <div class="doc-title">Ringkasan Presensi</div>
                <div class="doc-subtitle">
                    {{ $santri->name }} &nbsp;·&nbsp; {{ $kelasNama }}
                    &nbsp;·&nbsp;
                    {{ \Carbon\Carbon::parse($tanggalMulai)->locale('id')->isoFormat('D MMM Y') }}
                    – {{ \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMM Y') }}
                </div>
            </td>
            <td class="doc-meta">
                <div class="doc-meta-label">Halaman</div>
                <div class="doc-meta-value">3 / 3</div>
            </td>
        </tr>
    </table>
</div>

<div class="sect-heading">
    <table class="sect-heading-table">
        <tr>
            <td>
                <span class="sect-heading-bar"></span>
                <span class="sect-heading-text">Data Kehadiran</span>
            </td>
        </tr>
    </table>
</div>

@if($statistikPresensi['total_hari'] > 0)
<div class="presensi-wrap">
    <table class="presensi-table">
        <thead>
            <tr>
                <th style="width:65%;">Status Kehadiran</th>
                <th style="width:35%; text-align:right;">Jumlah Hari</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-hadir">Hadir</span></td>
                <td style="text-align:right; font-weight:700; color:#111827;">{{ $statistikPresensi['hadir'] > 0 ? $statistikPresensi['hadir'].' hari' : '—' }}</td>
            </tr>
            <tr>
                <td><span class="badge badge-izin">Izin</span></td>
                <td style="text-align:right; font-weight:700; color:#111827;">{{ $statistikPresensi['izin'] > 0 ? $statistikPresensi['izin'].' hari' : '—' }}</td>
            </tr>
            <tr>
                <td><span class="badge badge-sakit">Sakit</span></td>
                <td style="text-align:right; font-weight:700; color:#111827;">{{ $statistikPresensi['sakit'] > 0 ? $statistikPresensi['sakit'].' hari' : '—' }}</td>
            </tr>
            <tr>
                <td><span class="badge badge-alpha">Alpha</span></td>
                <td style="text-align:right; font-weight:700; color:#111827;">{{ $statistikPresensi['alpha'] > 0 ? $statistikPresensi['alpha'].' hari' : '—' }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Hari Presensi</strong></td>
                <td style="text-align:right; font-size:11px;">{{ $statistikPresensi['total_hari'] }} hari</td>
            </tr>
        </tbody>
    </table>
</div>

@php
    $pctHadirFull = $statistikPresensi['total_hari'] > 0 ? round(($statistikPresensi['hadir'] / $statistikPresensi['total_hari']) * 100, 1) : 0;
@endphp
<div style="margin-top:6px; padding:14px 16px; background:#FBF0EB; border-left:3px solid #D97757; border-radius:3px;">
    <table style="width:100%;">
        <tr>
            <td>
                <div style="font-size:8px; font-weight:700; color:#D97757; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:2px;">Persentase Kehadiran</div>
                <div style="font-size:8.5px; color:#6b7280;">{{ $statistikPresensi['hadir'] }} dari {{ $statistikPresensi['total_hari'] }} hari</div>
            </td>
            <td style="text-align:right;">
                <div style="font-size:22px; font-weight:700; color:#7C3D26; line-height:1;">{{ $pctHadirFull }}%</div>
            </td>
        </tr>
    </table>
</div>
@else
    <div class="no-data">
        <div class="no-data-title">Tidak Ada Data Presensi</div>
        <div>Belum ada catatan presensi dalam periode yang dipilih</div>
    </div>
@endif

<!-- Tanda Tangan -->
<table class="signature-section avoid-break">
    <tr>
        <td>
            <div class="signature-role">Mengetahui,<br/>Ketua Yayasan</div>
            <div class="signature-name">( __________________ )</div>
            <div class="signature-role-sub">Tanda tangan &amp; nama lengkap</div>
        </td>
        <td>
            <div class="signature-role">Hormat kami,<br/>Guru Pembimbing</div>
            <div class="signature-name">( {{ $guruNama !== '-' ? $guruNama : '__________________' }} )</div>
            <div class="signature-role-sub">Tanda tangan &amp; nama lengkap</div>
        </td>
    </tr>
</table>

<!-- Footer -->
<div class="footer-spacer"></div>
<div class="footer">
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                Dicetak {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB
            </td>
            <td class="footer-center">
                Sistem Monitoring Tahfidz
            </td>
            <td class="footer-right">
                Dokumen Resmi
            </td>
        </tr>
    </table>
</div>

</body>
</html>