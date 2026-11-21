<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapot {{ $santri->name }}</title>
@php
    // ====== FONT ======
    // Plus Jakarta Sans (SIL Open Font License) — font buatan desainer
    // Indonesia, jauh lebih modern daripada Helvetica/DejaVu. Berkasnya
    // ada di resources/fonts. DomPDF butuh folder storage/fonts yang bisa
    // ditulis untuk menyimpan cache metrik font; bila gagal dimuat, DomPDF
    // otomatis jatuh ke DejaVu Sans (lihat font-family body).
    $font = fn ($f) => str_replace('\\', '/', resource_path('fonts/' . $f));
@endphp
    <style>
        @font-face { font-family: 'Jakarta'; font-weight: normal; font-style: normal; src: url('{{ $font('PlusJakartaSans-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Jakarta'; font-weight: bold;   font-style: normal; src: url('{{ $font('PlusJakartaSans-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Jakarta'; font-weight: normal; font-style: italic; src: url('{{ $font('PlusJakartaSans-Italic.ttf') }}') format('truetype'); }
        /* Tebal ekstra untuk angka & judul besar. Keluarga terpisah karena
           DomPDF hanya membedakan normal dan bold. */
        @font-face { font-family: 'Jakarta Display'; font-weight: normal; font-style: normal; src: url('{{ $font('PlusJakartaSans-ExtraBold.ttf') }}') format('truetype'); }

        @page { margin: 14mm 14mm 20mm 14mm; }

        body {
            font-family: 'Jakarta', 'DejaVu Sans', sans-serif;
            font-size: 9.5px; line-height: 1.45; color: #23261F;
            margin: 0; padding: 0;
        }
        p { margin: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 0; vertical-align: top; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .redup { color: #7A8074; }
        .tebal { font-weight: bold; }
        .display { font-family: 'Jakarta Display', 'DejaVu Sans', sans-serif; }
        .caps { font-size: 7px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #8A9084; }

        .page-break { page-break-before: always; }
        .avoid-break { page-break-inside: avoid; }

        /* ================================================== */
        /* KOP                                                 */
        /* ================================================== */
        .kop td { vertical-align: middle; }
        .kop-logo img { width: 42px; height: 42px; }
        .kop-lembaga { font-size: 13px; font-weight: bold; color: #2F4A1C; letter-spacing: .2px; }
        .kop-sub { margin-top: 1px; font-size: 8px; font-weight: bold; color: #649634; letter-spacing: .3px; }
        .kop-alamat { margin-top: 2px; font-size: 7.5px; color: #8A9084; }
        .kop-pil {
            display: inline-block; padding: 4px 11px; border-radius: 12px;
            background: #2F4A1C; color: #FFFFFF;
            font-size: 7.5px; font-weight: bold; letter-spacing: 1.4px;
        }
        .kop-ta { margin-top: 5px; font-size: 8.5px; color: #4A4F45; font-weight: bold; }
        .garis { height: 1px; background: #E6E8DF; margin: 12px 0 14px 0; font-size: 0; line-height: 0; }

        /* ================================================== */
        /* HERO SANTRI                                         */
        /* ================================================== */
        .hero { background: #F3F8EB; border: 1px solid #D9EBC2; border-radius: 14px; }
        /* Selektor td.… sengaja: .layout td { padding: 0 } lebih spesifik
           daripada kelas tunggal dan akan menimpanya. */
        .hero td.hero-isi { padding: 18px 20px; }
        .hero-label { font-size: 7.5px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; color: #649634; }
        .hero-nama { margin-top: 4px; font-size: 21px; line-height: 1.15; color: #1B1D19; }
        .chip {
            display: inline-block; margin: 8px 4px 0 0; padding: 3px 9px; border-radius: 10px;
            background: #FFFFFF; border: 1px solid #D9EBC2;
            font-size: 8px; color: #3D5C24; font-weight: bold;
        }
        .chip span { color: #8A9084; font-weight: normal; }
        .hero-meta { margin-top: 10px; font-size: 8.5px; color: #5C6157; }
        .hero-meta strong { color: #23261F; }
        .hero td.foto { padding: 14px 18px 14px 0; width: 92px; vertical-align: middle; }
        .foto-box {
            width: 70px; height: 92px; margin: 0 auto;
            border: 3px solid #FFFFFF; border-radius: 10px; background: #E6EFDA;
            overflow: hidden;
        }
        .foto-box img { width: 70px; height: 92px; }
        .foto-ph { width: 70px; height: 92px; line-height: 92px; text-align: center; font-size: 9px; color: #8FB46A; }

        /* ================================================== */
        /* KPI                                                 */
        /* ================================================== */
        .kpi { margin-top: 14px; }
        .kpi-kartu { border: 1px solid #E6E8DF; border-radius: 12px; background: #FFFFFF; }
        .kpi-isi { padding: 11px 13px; }
        .kpi-angka { margin-top: 5px; font-size: 18px; line-height: 1; color: #1B1D19; }
        .kpi-satuan { font-size: 9px; color: #8A9084; font-family: 'Jakarta', 'DejaVu Sans', sans-serif; }
        .kpi-sub { margin-top: 4px; font-size: 7.5px; color: #8A9084; }

        /* ================================================== */
        /* KARTU                                               */
        /* ================================================== */
        .kartu { border: 1px solid #E6E8DF; border-radius: 12px; background: #FFFFFF; }
        .kartu-isi { padding: 13px 15px; }
        .kartu-judul { font-size: 10.5px; font-weight: bold; color: #1B1D19; }
        .kartu-sub { margin-top: 1px; font-size: 7.5px; color: #8A9084; }

        .target { margin-top: 11px; background: #F3F8EB; border-radius: 9px; }
        .target-isi { padding: 9px 11px; }
        .target-angka { font-size: 16px; color: #2F4A1C; }

        .baris-stat td { padding: 5px 0; border-bottom: 1px solid #F0F1EB; font-size: 8.8px; }
        .baris-stat td.angka { text-align: right; font-weight: bold; color: #1B1D19; white-space: nowrap; }
        .dot { font-size: 9px; }

        /* Bar: dua sel tabel berlebar persen — DomPDF tidak mendukung
           div bertumpuk dengan width persen secara andal. */
        .bar { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .bar td { height: 5px; padding: 0; font-size: 0; line-height: 0; }
        .bar-tipis td { height: 3px; }
        .bar-tebal td { height: 9px; }
        .bar-sisa { background: #E6E8DF; }

        /* ================================================== */
        /* HEADER HALAMAN 2 & 3                                */
        /* ================================================== */
        .hal-label { font-size: 7.5px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; color: #649634; }
        .hal-judul { margin-top: 3px; font-size: 19px; line-height: 1.15; color: #1B1D19; }
        .hal-sub { margin-top: 3px; font-size: 8.5px; color: #7A8074; }
        .hal-nomor {
            display: inline-block; padding: 3px 10px; border-radius: 10px;
            background: #F3F5EE; color: #7A8074; font-size: 7.5px; font-weight: bold;
        }

        /* ================================================== */
        /* KATEGORI SETORAN                                    */
        /* ================================================== */
        .kat { margin-bottom: 12px; border: 1px solid #E6E8DF; border-radius: 12px; page-break-inside: avoid; }
        .kat-kepala { padding: 10px 14px; border-bottom: 1px solid #F0F1EB; }
        .kat-nama { font-size: 10.5px; font-weight: bold; color: #1B1D19; }
        .kat-jumlah {
            display: inline-block; padding: 2px 9px; border-radius: 9px;
            font-size: 7.5px; font-weight: bold;
        }
        .k-hafalan  { color: #047857; background: #ECFDF5; }
        .k-murajaah { color: #1D4ED8; background: #EFF6FF; }
        .k-tilawah  { color: #7E22CE; background: #FAF5FF; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            padding: 7px 14px; text-align: left;
            font-size: 6.8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #9CA196;
            border-bottom: 1px solid #F0F1EB;
        }
        table.data th.tengah { text-align: center; }
        table.data td { padding: 7px 14px; border-bottom: 1px solid #F5F6F1; font-size: 9px; }
        .no { color: #B4B9AE; font-size: 8px; }
        .surat { font-weight: bold; color: #1B1D19; }
        .ayat { color: #9CA196; font-size: 8px; }
        .pil {
            display: inline-block; padding: 2px 8px; border-radius: 9px;
            font-size: 8.5px; font-weight: bold; background: #F3F5EE; color: #4A4F45;
        }
        .nilai { display: inline-block; min-width: 28px; padding: 3px 8px; border-radius: 9px; font-size: 9px; font-weight: bold; text-align: center; }
        .nilai-a { background: #E8F4D9; color: #2F4A1C; }
        .nilai-b { background: #E0ECFF; color: #1E40AF; }
        .nilai-c { background: #FEF3C7; color: #92400E; }

        /* ================================================== */
        /* PRESENSI                                            */
        /* ================================================== */
        .ubin { border-radius: 12px; }
        .ubin-isi { padding: 12px 14px; }
        .ubin-angka { margin-top: 4px; font-size: 20px; line-height: 1; }
        .ubin-satuan { font-size: 8.5px; font-family: 'Jakarta', 'DejaVu Sans', sans-serif; }
        .u-hadir { background: #ECFDF5; color: #047857; }
        .u-izin  { background: #EFF6FF; color: #1D4ED8; }
        .u-sakit { background: #FEF9E7; color: #A16207; }
        .u-alpha { background: #FEF2F2; color: #B91C1C; }
        /* Label ubin ikut warna ubinnya. Ditulis eksplisit — 'inherit'
           tidak selalu dihormati DomPDF. */
        .u-hadir .caps { color: #047857; }
        .u-izin  .caps { color: #1D4ED8; }
        .u-sakit .caps { color: #A16207; }
        .u-alpha .caps { color: #B91C1C; }

        /* ================================================== */
        /* KENAIKAN                                            */
        /* ================================================== */
        .naik-kartu { border-radius: 12px; page-break-inside: avoid; }
        .naik-kartu.naik    { background: #F3F8EB; border: 1px solid #D9EBC2; }
        .naik-kartu.tinggal { background: #FEF2F2; border: 1px solid #FECACA; }
        .naik-teks { margin-top: 4px; font-size: 14px; line-height: 1.2; color: #1B1D19; }
        .naik-pil { display: inline-block; padding: 5px 13px; border-radius: 13px; font-size: 8.5px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .naik-pil.naik    { background: #2F4A1C; color: #FFFFFF; }
        .naik-pil.tinggal { background: #B91C1C; color: #FFFFFF; }

        /* ================================================== */
        /* TANDA TANGAN                                        */
        /* ================================================== */
        .ttd td { width: 50%; text-align: center; vertical-align: top; padding: 0 20px; }
        .ttd-peran { font-size: 8.5px; color: #7A8074; }
        .ttd-jabatan { font-size: 9.5px; font-weight: bold; color: #23261F; }
        .ttd-ruang { height: 58px; }
        .ttd-nama { display: inline-block; min-width: 160px; padding-top: 6px; border-top: 1px solid #BFC4B8; font-size: 9.5px; font-weight: bold; color: #1B1D19; }
        .ttd-ket { margin-top: 2px; font-size: 7px; color: #ABB0A5; }

        /* ================================================== */
        /* KOSONG                                              */
        /* ================================================== */
        .kosong { padding: 26px 20px; text-align: center; background: #F8F9F5; border: 1px dashed #DADDD2; border-radius: 12px; color: #9CA196; font-size: 9px; }
        .kosong-judul { font-weight: bold; color: #5C6157; font-size: 10px; margin-bottom: 2px; }

        /* ================================================== */
        /* FOOTER                                              */
        /* ================================================== */
        .footer {
            position: fixed; bottom: -14mm; left: 0; right: 0;
            font-size: 7px; color: #ABB0A5;
            padding-top: 6px; border-top: 1px solid #ECEEE6;
        }
        .footer-tengah { text-align: center; color: #649634; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
    </style>
</head>
<body>

@php
    // ====== KOP ======
    // Nama & alamat diambil dari Pengaturan Sistem (sumber yang sama dengan
    // kwitansi). Nilai di sebelah kanan hanya cadangan bila belum diisi.
    $namaLembaga   = \App\Models\Setting::get('kop_nama', 'Pondok Pesantren Tahfizh');
    $subLembaga    = 'Program Tahfizh Al-Qur\'an';
    $alamatLembaga = \App\Models\Setting::get('kop_alamat', '');

    // ====== LOGO: public/images/logo-rapot.png ======
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
    $metodeLabel   = $statistikSetoran['metode_label'] ?? 'Iqro';
    $jilidAktif    = $statistikSetoran['target_jilid'] ?? 1;
    $halTersetor   = $statistikSetoran['total_halaman_iqro'] ?? 0;
    $halTotalJilid = $statistikSetoran['total_halaman_jilid'] ?? 0;
    $progressJilid = $statistikSetoran['progress_iqro'] ?? 0;
    $jilidDiketahui= $statistikSetoran['iqro_persen_diketahui'] ?? false;

    // Metode 2 (opsional): Al-Qur'an sebagai target sekunder untuk santri jilid.
    $hasQuran2   = ($statistikSetoran['has_quran_secondary'] ?? false) && $isJilid;
    $metodeTeks  = $isJilid ? $metodeLabel.' — Jilid '.$jilidAktif : "Al-Qur'an";
    if ($hasQuran2) $metodeTeks .= " + Al-Qur'an";

    $tglMulai = \Carbon\Carbon::parse($tanggalMulai)->locale('id');
    $tglAkhir = \Carbon\Carbon::parse($tanggalAkhir)->locale('id');
    $periodePanjang = $tglMulai->isoFormat('D MMMM Y') . ' – ' . $tglAkhir->isoFormat('D MMMM Y');
    $periodePendek  = $tglMulai->isoFormat('D MMM Y') . ' – ' . $tglAkhir->isoFormat('D MMM Y');

    $totalHari = (int) $statistikPresensi['total_hari'];
    $pctHadir  = $totalHari > 0 ? round(($statistikPresensi['hadir'] / $totalHari) * 100, 1) : 0;

    $totalSetoran = (int) $statistikSetoran['total_setoran'];
    $porsi = fn ($n) => $totalSetoran > 0 ? round($n / $totalSetoran * 100) : 0;

    // Bar progres. Sel 0% tetap digambar DomPDF sebagai garis tipis,
    // jadi kedua ujung (0 dan 100) ditangani terpisah.
    $bar = function ($persen, $warna = '#7EB842', $kelas = '') {
        $p = (int) round(max(0, min(100, (float) $persen)));
        $buka = '<table class="bar '.$kelas.'"><tr>';
        if ($p >= 100) return $buka.'<td style="background:'.$warna.';" width="100%"></td></tr></table>';
        if ($p <= 0)   return $buka.'<td class="bar-sisa" width="100%"></td></tr></table>';
        return $buka.'<td style="background:'.$warna.';" width="'.$p.'%"></td><td class="bar-sisa" width="'.(100 - $p).'%"></td></tr></table>';
    };

    // Bar bertumpuk presensi: hanya status yang jumlahnya > 0 yang digambar.
    $warnaPresensi = ['hadir' => '#10B981', 'izin' => '#3B82F6', 'sakit' => '#EAB308', 'alpha' => '#EF4444'];
    $barPresensi = function () use ($statistikPresensi, $totalHari, $warnaPresensi) {
        if ($totalHari <= 0) return '';
        $sel = '';
        foreach ($warnaPresensi as $k => $w) {
            $n = (int) $statistikPresensi[$k];
            if ($n > 0) $sel .= '<td style="background:'.$w.';" width="'.round($n / $totalHari * 100, 2).'%"></td>';
        }
        return '<table class="bar bar-tebal"><tr>'.$sel.'</tr></table>';
    };
@endphp

{{-- Footer di setiap halaman --}}
<div class="footer">
    <table class="layout">
        <tr>
            <td width="33%">Dicetak {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB</td>
            <td width="34%" class="footer-tengah">Sistem Monitoring Tahfidz</td>
            <td width="33%" class="kanan">{{ $santri->name }} · Dokumen resmi</td>
        </tr>
    </table>
</div>

{{-- ============================================================ --}}
{{-- HALAMAN 1 : PROFIL + RINGKASAN                               --}}
{{-- ============================================================ --}}

<table class="layout kop">
    <tr>
        @if($logoData)
            <td width="50" class="kop-logo"><img src="{{ $logoData }}" alt="Logo"></td>
            <td style="padding-left: 10px;">
        @else
            <td>
        @endif
            <p class="kop-lembaga">{{ $namaLembaga }}</p>
            <p class="kop-sub">{{ $subLembaga }}</p>
            @if($alamatLembaga)
                <p class="kop-alamat">{{ $alamatLembaga }}</p>
            @endif
        </td>
        <td class="kanan" width="38%">
            <span class="kop-pil">RAPOR SANTRI</span>
            @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
                <p class="kop-ta">
                    {{ $tahunAjaranAktif->nama ?? '-' }}@if($tahunAjaranAktif->semester) · Semester {{ $tahunAjaranAktif->semester == 1 ? 'Ganjil' : 'Genap' }}@endif
                </p>
            @endif
        </td>
    </tr>
</table>

<div class="garis"></div>

{{-- HERO --}}
<div class="hero">
    <table class="layout">
        <tr>
            <td class="hero-isi">
                <p class="hero-label">Laporan perkembangan santri</p>
                <p class="hero-nama display">{{ $santri->name }}</p>
                <div>
                    <span class="chip"><span>Kelas</span> {{ $kelasNama }}</span>
                    <span class="chip"><span>NIS</span> {{ $santri->nis ?? '-' }}</span>
                    <span class="chip"><span>Metode</span> {{ $metodeTeks }}</span>
                </div>
                <p class="hero-meta">
                    Guru pembimbing <strong>{{ $guruNama }}</strong>
                    &nbsp;·&nbsp; Periode <strong>{{ $periodePanjang }}</strong>
                </p>
            </td>
            <td class="foto">
                <div class="foto-box">
                    @if($fotoData)
                        <img src="{{ $fotoData }}" alt="Foto">
                    @else
                        <div class="foto-ph">3 × 4</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- KPI --}}
<table class="layout kpi">
    <tr>
        <td width="23.5%">
            <div class="kpi-kartu"><div class="kpi-isi">
                <p class="caps">Total setoran</p>
                <p class="kpi-angka display">{{ $totalSetoran }} <span class="kpi-satuan">kali</span></p>
                <p class="kpi-sub">selama periode ini</p>
            </div></div>
        </td>
        <td width="2%"></td>
        <td width="23.5%">
            <div class="kpi-kartu"><div class="kpi-isi">
                <p class="caps">Rata-rata nilai</p>
                <p class="kpi-angka display">{{ $statistikSetoran['rata_nilai'] }}</p>
                <p class="kpi-sub">dari seluruh setoran</p>
            </div></div>
        </td>
        <td width="2%"></td>
        <td width="23.5%">
            <div class="kpi-kartu"><div class="kpi-isi">
                @if($isJilid)
                    <p class="caps">Halaman disetor</p>
                    <p class="kpi-angka display">{{ $halTersetor }}@if($jilidDiketahui)<span class="kpi-satuan"> / {{ $halTotalJilid }}</span>@endif</p>
                    <p class="kpi-sub">{{ $metodeLabel }} Jilid {{ $jilidAktif }}</p>
                @else
                    <p class="caps">Total hafalan</p>
                    <p class="kpi-angka display">{{ $statistikSetoran['total_juz'] }} <span class="kpi-satuan">juz</span></p>
                    <p class="kpi-sub">Al-Qur'an</p>
                @endif
            </div></div>
        </td>
        <td width="2%"></td>
        <td width="23.5%">
            <div class="kpi-kartu"><div class="kpi-isi">
                <p class="caps">Kehadiran</p>
                <p class="kpi-angka display" style="color: #2F4A1C;">{{ $totalHari > 0 ? $pctHadir . '%' : '—' }}</p>
                <p class="kpi-sub">{{ $statistikPresensi['hadir'] }} dari {{ $totalHari }} hari</p>
            </div></div>
        </td>
    </tr>
</table>

{{-- DETAIL SETORAN & PRESENSI --}}
<table class="layout" style="margin-top: 14px;">
    <tr>
        <td width="51%">
            <div class="kartu"><div class="kartu-isi">
                <p class="kartu-judul">Setoran</p>
                <p class="kartu-sub">Sebaran jenis setoran dan pencapaian target</p>

                @if($isJilid && $jilidDiketahui)
                    <div class="target"><div class="target-isi">
                        <table class="layout">
                            <tr>
                                <td>
                                    <p class="caps" style="color: #4D752A;">Progress {{ $metodeLabel }} Jilid {{ $jilidAktif }}</p>
                                    <p class="redup" style="font-size: 8px; margin-top: 1px;">{{ $halTersetor }} dari {{ $halTotalJilid }} halaman (hafalan)</p>
                                </td>
                                <td class="kanan" style="vertical-align: middle;"><p class="target-angka display">{{ $progressJilid }}%</p></td>
                            </tr>
                        </table>
                        {!! $bar($progressJilid) !!}
                    </div></div>
                @endif

                @if($hasQuran2)
                    <div class="target" style="background: #ECFDF5;"><div class="target-isi">
                        <table class="layout">
                            <tr>
                                <td>
                                    <p class="caps" style="color: #047857;">Target Al-Qur'an</p>
                                    <p class="redup" style="font-size: 8px; margin-top: 1px;">{{ $statistikSetoran['total_juz_hafalan'] }} dari {{ $statistikSetoran['target_juz'] }} juz</p>
                                </td>
                                <td class="kanan" style="vertical-align: middle;"><p class="target-angka display" style="color: #065F46;">{{ $statistikSetoran['persentase_target'] }}%</p></td>
                            </tr>
                        </table>
                        {!! $bar($statistikSetoran['persentase_target'], '#10B981') !!}
                    </div></div>
                @endif

                @if(!$isJilid)
                    <div class="target"><div class="target-isi">
                        <table class="layout">
                            <tr>
                                <td>
                                    <p class="caps" style="color: #4D752A;">Target hafalan</p>
                                    <p class="redup" style="font-size: 8px; margin-top: 1px;">{{ $statistikSetoran['total_juz_hafalan'] }} dari {{ $statistikSetoran['target_juz'] }} juz</p>
                                </td>
                                <td class="kanan" style="vertical-align: middle;"><p class="target-angka display">{{ $statistikSetoran['persentase_target'] }}%</p></td>
                            </tr>
                        </table>
                        {!! $bar($statistikSetoran['persentase_target']) !!}
                    </div></div>
                @endif

                <table class="layout baris-stat" style="margin-top: 8px;">
                    @foreach([['Hafalan', 'hafalan', '#10B981'], ['Murajaah', 'murajaah', '#3B82F6'], ['Tilawah', 'tilawah', '#A855F7']] as [$label, $kunci, $warna])
                        <tr>
                            <td width="30%"><span class="dot" style="color: {{ $warna }};">&#9679;</span> {{ $label }}</td>
                            <td width="48%" style="vertical-align: middle; padding-right: 10px;">{!! $bar($porsi($statistikSetoran[$kunci]), $warna, 'bar-tipis') !!}</td>
                            <td width="22%" class="angka">{{ $statistikSetoran[$kunci] }} kali</td>
                        </tr>
                    @endforeach
                </table>
            </div></div>
        </td>
        <td width="2%"></td>
        <td width="47%">
            <div class="kartu"><div class="kartu-isi">
                <p class="kartu-judul">Presensi</p>
                <p class="kartu-sub">{{ $totalHari }} hari tercatat dalam periode</p>

                @if($totalHari > 0)
                    <div style="margin-top: 10px;">{!! $barPresensi() !!}</div>
                @endif

                <table class="layout baris-stat" style="margin-top: 8px;">
                    @foreach([['Hadir', 'hadir'], ['Izin', 'izin'], ['Sakit', 'sakit'], ['Alpha', 'alpha']] as [$label, $kunci])
                        <tr>
                            <td><span class="dot" style="color: {{ $warnaPresensi[$kunci] }};">&#9679;</span> {{ $label }}</td>
                            <td class="angka">{{ $statistikPresensi[$kunci] }} hari</td>
                        </tr>
                    @endforeach
                </table>
            </div></div>
        </td>
    </tr>
</table>

{{-- ============================================================ --}}
{{-- HALAMAN 2 : RIWAYAT SETORAN                                  --}}
{{-- ============================================================ --}}
<div class="page-break"></div>

<table class="layout">
    <tr>
        <td>
            <p class="hal-label">Detail pencatatan</p>
            <p class="hal-judul display">Riwayat Setoran</p>
            <p class="hal-sub">{{ $santri->name }} · {{ $kelasNama }} · {{ $periodePendek }}</p>
        </td>
        <td class="kanan" width="25%"><span class="hal-nomor">Halaman 2 / 3</span></td>
    </tr>
</table>
<div class="garis"></div>

@if(count($setorans) > 0)
    @foreach(['Hafalan', 'Murajaah', 'Tilawah'] as $kategori)
        @if(isset($setorans[$kategori]) && count($setorans[$kategori]) > 0)
            @php
                $kelasKat = ['Hafalan' => 'k-hafalan', 'Murajaah' => 'k-murajaah', 'Tilawah' => 'k-tilawah'][$kategori];
                $warnaKat = ['Hafalan' => '#10B981', 'Murajaah' => '#3B82F6', 'Tilawah' => '#A855F7'][$kategori];
            @endphp
            <div class="kat">
                <div class="kat-kepala">
                    <table class="layout">
                        <tr>
                            <td style="vertical-align: middle;">
                                <span class="dot" style="color: {{ $warnaKat }}; font-size: 11px;">&#9679;</span>
                                <span class="kat-nama">{{ $kategori }}</span>
                            </td>
                            <td class="kanan" style="vertical-align: middle;">
                                <span class="kat-jumlah {{ $kelasKat }}">{{ count($setorans[$kategori]) }} {{ $hasQuran2 ? 'entri' : ($isJilid ? 'jilid' : 'surat') }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width: 7%;" class="tengah">No</th>
                            <th style="width: 55%;">{{ $hasQuran2 ? 'Materi Setoran' : ($isJilid ? 'Jilid & Halaman' : 'Surah & Ayat') }}</th>
                            <th style="width: 18%;" class="tengah">Jumlah</th>
                            <th style="width: 20%;" class="tengah">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($setorans[$kategori] as $suratKey => $data)
                            @php
                                // Baris ini jilid-based? (Iqro / Aisar / Ummi)
                                $barisJilid = (bool) preg_match('/^(Iqro|Aisar|Ummi)\s+Jilid\s+\d+/i', (string) $data['surat_start']);
                                $kelasNilai = $data['rata_nilai'] >= 90 ? 'nilai-a' : ($data['rata_nilai'] >= 75 ? 'nilai-b' : 'nilai-c');
                            @endphp
                            <tr>
                                <td class="tengah"><span class="no">{{ str_pad($no++, 2, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <span class="surat">{{ $data['surat_start'] }}@if($data['surat_start'] != $data['surat_end']) — {{ $data['surat_end'] }}@endif</span>
                                    <span class="ayat">&nbsp;{{ $barisJilid ? 'Halaman' : 'Ayat' }} {{ $data['ayat_start_min'] }}–{{ $data['ayat_end_max'] }}</span>
                                </td>
                                <td class="tengah"><span class="pil">{{ $data['jumlah_setoran'] }}×</span></td>
                                <td class="tengah"><span class="nilai {{ $kelasNilai }}">{{ $data['rata_nilai'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach
@else
    <div class="kosong">
        <p class="kosong-judul">Tidak ada data setoran</p>
        <p>Belum ada catatan setoran dalam periode yang dipilih</p>
    </div>
@endif

{{-- ============================================================ --}}
{{-- HALAMAN 3 : PRESENSI + KEPUTUSAN + TANDA TANGAN               --}}
{{-- ============================================================ --}}
<div class="page-break"></div>

<table class="layout">
    <tr>
        <td>
            <p class="hal-label">Detail pencatatan</p>
            <p class="hal-judul display">Kehadiran &amp; Keputusan</p>
            <p class="hal-sub">{{ $santri->name }} · {{ $kelasNama }} · {{ $periodePendek }}</p>
        </td>
        <td class="kanan" width="25%"><span class="hal-nomor">Halaman 3 / 3</span></td>
    </tr>
</table>
<div class="garis"></div>

@if($totalHari > 0)
    <table class="layout">
        <tr>
            @foreach([['Hadir', 'hadir', 'u-hadir'], ['Izin', 'izin', 'u-izin'], ['Sakit', 'sakit', 'u-sakit'], ['Alpha', 'alpha', 'u-alpha']] as $i => [$label, $kunci, $kelas])
                @if($i > 0)<td width="2%"></td>@endif
                <td width="23.5%">
                    <div class="ubin {{ $kelas }}"><div class="ubin-isi">
                        <p class="caps">{{ $label }}</p>
                        <p class="ubin-angka display">{{ $statistikPresensi[$kunci] }} <span class="ubin-satuan">hari</span></p>
                    </div></div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="kartu" style="margin-top: 12px;"><div class="kartu-isi">
        <table class="layout">
            <tr>
                <td style="vertical-align: middle;">
                    <p class="caps" style="color: #4D752A;">Persentase kehadiran</p>
                    <p class="redup" style="margin-top: 2px; font-size: 8.5px;">{{ $statistikPresensi['hadir'] }} dari {{ $totalHari }} hari presensi</p>
                </td>
                <td class="kanan" style="vertical-align: middle;">
                    <p class="display" style="font-size: 24px; line-height: 1; color: #2F4A1C;">{{ $pctHadir }}%</p>
                </td>
            </tr>
        </table>
        <div style="margin-top: 6px;">{!! $barPresensi() !!}</div>
        <p style="margin-top: 6px; font-size: 7.5px; color: #8A9084;">
            @foreach($warnaPresensi as $k => $w)
                <span style="color: {{ $w }};">&#9679;</span> {{ ucfirst($k) }}&nbsp;&nbsp;
            @endforeach
        </p>
    </div></div>
@else
    <div class="kosong">
        <p class="kosong-judul">Tidak ada data presensi</p>
        <p>Belum ada catatan presensi dalam periode yang dipilih</p>
    </div>
@endif

{{-- ===== KEPUTUSAN KENAIKAN KELAS (hanya semester genap) ===== --}}
@php $sk = $statusKenaikan ?? null; @endphp
@if($sk)
    <div class="naik-kartu {{ $sk['naik'] ? 'naik' : 'tinggal' }}" style="margin-top: 14px;">
        <table class="layout">
            <tr>
                <td style="padding: 15px 18px; vertical-align: middle;">
                    <p class="caps">Keputusan akhir tahun ajaran</p>
                    <p class="naik-teks display">{{ $sk['teks'] }}</p>
                    <p class="redup" style="margin-top: 3px; font-size: 8.5px;">
                        Kelas asal: {{ $sk['kelas_asal'] }}@if($sk['keterangan']) · {{ $sk['keterangan'] }}@endif
                    </p>
                </td>
                <td class="kanan" width="130" style="padding: 15px 18px; vertical-align: middle;">
                    <span class="naik-pil {{ $sk['naik'] ? 'naik' : 'tinggal' }}">{{ $sk['judul'] }}</span>
                </td>
            </tr>
        </table>
    </div>
@endif

{{-- Tanda tangan --}}
<table class="layout ttd avoid-break" style="margin-top: 40px;">
    <tr>
        <td>
            <p class="ttd-peran">Mengetahui,</p>
            <p class="ttd-jabatan">Ketua Yayasan</p>
            <div class="ttd-ruang"></div>
            <p class="ttd-nama">&nbsp;</p>
            <p class="ttd-ket">Tanda tangan &amp; nama lengkap</p>
        </td>
        <td>
            <p class="ttd-peran">Hormat kami,</p>
            <p class="ttd-jabatan">Guru Pembimbing</p>
            <div class="ttd-ruang"></div>
            <p class="ttd-nama">{{ $guruNama !== '-' ? $guruNama : ' ' }}</p>
            <p class="ttd-ket">Tanda tangan &amp; nama lengkap</p>
        </td>
    </tr>
</table>

</body>
</html>