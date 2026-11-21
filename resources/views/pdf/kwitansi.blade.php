<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $pembayaran->receipt_number }}</title>
    <style>
        /* A5 landscape = 210×148mm, harus muat SATU halaman.
           Ditulis untuk DomPDF: tata letak memakai tabel (flex/grid tidak
           didukung), tanpa outline, transform, maupun margin negatif. */
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px; color: #1B1D19;
            margin: 0; padding: 7mm 8mm;
        }
        p { margin: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 0; vertical-align: top; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .redup { color: #7A8074; }

        /* Lembar: sudut membulat + garis aksen hijau di atas. Aksen dibuat
           sebagai div tersendiri karena DomPDF tidak menggambar
           border-top berwarna beda di atas border-radius dengan rapi. */
        .lembar { border: 1px solid #E1E4D8; border-radius: 10px; }
        .aksen { height: 5px; background: #7EB842; border-radius: 10px 10px 0 0; }
        .isi-lembar { padding: 16px 22px 16px 22px; }

        /* ---------- Kop ---------- */
        .kop-nama { font-size: 15px; font-weight: bold; color: #3D5C24; }
        .kop-alamat { margin-top: 3px; font-size: 8.5px; color: #7A8074; line-height: 1.35; }
        .label-dok { font-size: 7.5px; font-weight: bold; letter-spacing: 2.5px; color: #649634; }
        .nomor { margin-top: 2px; font-size: 12.5px; font-weight: bold; color: #1B1D19; }
        .chip {
            display: inline-block; margin-top: 4px;
            padding: 2px 8px; border-radius: 9px;
            font-size: 7.8px; font-weight: bold; letter-spacing: .4px;
            background: #F4F9EC; color: #4D752A; border: 1px solid #CEE6AF;
        }

        /* ---------- Kartu nominal ---------- */
        /* Radius dipasang di div pembungkus: tabel dengan border-collapse
           mengabaikan border-radius, baik di DomPDF maupun di browser. */
        .hero { margin-top: 14px; background: #F6F7F2; border: 1px solid #E1E4D8; border-radius: 9px; }
        .hero td.isi-hero { padding: 13px 18px; vertical-align: middle; }
        .kecil-caps { font-size: 7.3px; font-weight: bold; letter-spacing: 1.2px; color: #7A8074; text-transform: uppercase; }
        .nominal { margin-top: 3px; font-size: 26px; font-weight: bold; color: #4D752A; letter-spacing: -.3px; }
        .terbilang { margin-top: 3px; font-size: 8.8px; font-style: italic; color: #5C6157; }

        /* Status: hijau = lunas / diterima, kuning = cicilan. */
        .status {
            display: inline-block; padding: 4px 11px; border-radius: 11px;
            font-size: 9.5px; font-weight: bold; letter-spacing: 1.5px;
        }
        .status-ok     { background: #E6F2D5; color: #3D5C24; border: 1px solid #B0D782; }
        .status-cicil  { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }
        .status-tgl { margin-top: 5px; font-size: 8.5px; color: #7A8074; }

        /* ---------- Rincian dua kolom ---------- */
        .rinci { margin-top: 12px; }
        .rinci td.sel { padding: 7px 0 7px 0; border-bottom: 1px solid #EDEFE6; }
        .rinci td.sel-kiri { width: 50%; padding-right: 14px; }
        .nilai { margin-top: 2px; font-size: 10.5px; color: #1B1D19; }
        .nilai-tebal { font-weight: bold; }

        /* ---------- Progres cicilan ---------- */
        .progres { margin-top: 11px; }
        .bar { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .bar td { height: 6px; padding: 0; }
        .bar-isi  { background: #7EB842; }
        .bar-sisa { background: #EDEFE6; }
        .sisa { color: #B45309; font-weight: bold; }

        /* ---------- Kaki ---------- */
        .kaki { margin-top: 16px; }
        .terima-kasih { font-size: 9.5px; color: #3D5C24; font-weight: bold; }
        .catatan-kaki { margin-top: 4px; font-size: 7.8px; color: #9CA196; line-height: 1.4; }
        .ttd-jabatan { font-size: 9.5px; color: #5C6157; }
        /* line-height & font-size nol: <img> inline duduk di baseline dan
           menyisakan ruang turunan huruf, mendorong nama menjauh. */
        .ttd-kotak { vertical-align: bottom; text-align: center; line-height: 0; font-size: 0; }
        .ttd-nama {
            display: inline-block; padding: 3px 14px 0 14px;
            border-top: 1px solid #7A8074;
            font-size: 10.5px; font-weight: bold; color: #1B1D19;
        }
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
    $cicilan = $pembayaran->tagihan && ! $lunas;
    $isInfaq = $pembayaran->kategori === 'infaq_arahan';

    // max(0, ...) menjaga sisa tidak pernah tercetak negatif bila ada
    // kelebihan bayar — angka minus di kwitansi selalu memicu pertanyaan.
    $totalTagihan = (float) ($pembayaran->tagihan?->total_amount ?? 0);
    $sisaTagihan  = max(0, (float) ($pembayaran->tagihan?->remaining_balance ?? 0));
    $sudahDibayar = max(0, $totalTagihan - $sisaTagihan);

    // Baris progres hanya bila menambah informasi: tagihan yang dicicil.
    // Pada lunas sekali bayar, akumulasinya sama persis dengan nominal.
    $tampilProgres = $pembayaran->tagihan && (int) $sudahDibayar !== (int) $pembayaran->nominal;
    $persen = $totalTagihan > 0 ? (int) round(min(100, $sudahDibayar / $totalTagihan * 100)) : 0;

    $untuk = $pembayaran->tagihan
        ? ($pembayaran->tagihan->header?->title ?? 'Tagihan')
        : $kategoriLabel;

    $rincianUntuk = collect([
        $pembayaran->tagihan?->invoice_number,
        $pembayaran->tagihan?->header?->period,
    ])->filter()->implode(' · ');

    // Nama & alamat di kop diambil dari pengaturan; APP_NAME hanya cadangan
    // karena ia juga menjadi judul tab browser di seluruh aplikasi.
    $kopNama   = \App\Models\Setting::get('kop_nama', config('app.name'));
    $kopAlamat = \App\Models\Setting::get('kop_alamat', config('app.school_address'));

    // Identitas penanda tangan — diatur super-admin di Pengaturan Sistem.
    // Semua boleh kosong: kwitansi kembali ke bentuk tanda tangan manual.
    $ttdAktif   = \App\Models\Setting::get('ttd_aktif') === '1';
    $ttdGambar  = $ttdAktif ? \App\Models\Setting::get('ttd_gambar') : null;
    $ttdNama    = \App\Models\Setting::get('ttd_nama');
    $ttdJabatan = \App\Models\Setting::get('ttd_jabatan');

    // display_name, bukan name: users.name berisi username login.
    $petugas = $pembayaran->penyetuju?->display_name;

    $tanggalBayar = $pembayaran->payment_date?->translatedFormat('d F Y');
@endphp

<div class="lembar">
    <div class="aksen"></div>
    <div class="isi-lembar">

        {{-- KOP --}}
        <table class="layout">
            <tr>
                @if($logo)
                    <td width="9%" style="vertical-align: middle;">
                        <img src="{{ $logo }}" height="40" alt="">
                    </td>
                    <td width="56%" style="vertical-align: middle; padding-left: 8px;">
                @else
                    <td width="65%" style="vertical-align: middle;">
                @endif
                    <p class="kop-nama">{{ $kopNama }}</p>
                    @if($kopAlamat)
                        <p class="kop-alamat">{{ $kopAlamat }}</p>
                    @endif
                </td>
                <td width="35%" class="kanan" style="vertical-align: middle;">
                    <p class="label-dok">KWITANSI</p>
                    <p class="nomor">{{ $pembayaran->receipt_number }}</p>
                    <span class="chip">{{ $kategoriLabel }}</span>
                </td>
            </tr>
        </table>

        {{-- NOMINAL + STATUS --}}
        <div class="hero">
        <table class="layout">
            <tr>
                <td class="isi-hero" width="68%">
                    <p class="kecil-caps">Jumlah diterima</p>
                    <p class="nominal">Rp {{ number_format($pembayaran->nominal, 0, ',', '.') }}</p>
                    <p class="terbilang">{{ ucfirst(trim(\App\Support\Terbilang::of((int) $pembayaran->nominal))) }} rupiah</p>
                </td>
                {{-- Kwitansi cicilan memakai label kuning "CICILAN" yang
                     eksplisit — tidak ada tanda apa pun yang bisa disalahbaca
                     sebagai pelunasan. --}}
                <td class="isi-hero kanan" width="32%">
                    @if($cicilan)
                        <span class="status status-cicil">CICILAN</span>
                    @elseif($lunas)
                        <span class="status status-ok">&#10003; LUNAS</span>
                    @else
                        <span class="status status-ok">&#10003; DITERIMA</span>
                    @endif
                    <p class="status-tgl">{{ $tanggalBayar }}</p>
                </td>
            </tr>
        </table>
        </div>

        {{-- RINCIAN --}}
        <table class="layout rinci">
            <tr>
                <td class="sel sel-kiri">
                    <p class="kecil-caps">Diterima dari</p>
                    <p class="nilai nilai-tebal">
                        {{ $pihak }}@if($pembayaran->siswa?->nis) <span class="redup" style="font-weight: normal;">· NIS {{ $pembayaran->siswa->nis }}</span>@endif
                    </p>
                </td>
                <td class="sel">
                    <p class="kecil-caps">Untuk pembayaran</p>
                    <p class="nilai nilai-tebal">
                        {{ $untuk }}@if($rincianUntuk) <span class="redup" style="font-weight: normal;">· {{ $rincianUntuk }}</span>@endif
                    </p>
                </td>
            </tr>
            <tr>
                <td class="sel sel-kiri">
                    <p class="kecil-caps">Metode</p>
                    <p class="nilai">{{ $pembayaran->payment_method }}</p>
                </td>
                <td class="sel">
                    <p class="kecil-caps">Diterima di</p>
                    <p class="nilai">{{ $pembayaran->rekening?->account_name ?? '—' }}</p>
                </td>
            </tr>
            @if($pembayaran->catatan)
                <tr>
                    <td class="sel" colspan="2">
                        <p class="kecil-caps">Catatan</p>
                        <p class="nilai">{{ \Illuminate\Support\Str::limit($pembayaran->catatan, 110) }}</p>
                    </td>
                </tr>
            @endif
        </table>

        {{-- PROGRES CICILAN --}}
        @if($tampilProgres)
            <div class="progres">
                <table class="layout">
                    <tr>
                        <td>
                            <p class="kecil-caps">Total pembayaran tagihan</p>
                        </td>
                        <td class="kanan" style="font-size: 9.5px;">
                            <strong>Rp {{ number_format($sudahDibayar, 0, ',', '.') }}</strong>
                            <span class="redup">dari Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span>
                            @if(! $lunas)
                                <span class="sisa">· sisa Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
                {{-- Sel selebar 0% tetap digambar DomPDF sebagai garis tipis,
                     jadi kedua ujung ditangani terpisah. --}}
                <table class="bar">
                    <tr>
                        @if($persen >= 100)
                            <td class="bar-isi" width="100%"></td>
                        @elseif($persen <= 0)
                            <td class="bar-sisa" width="100%"></td>
                        @else
                            <td class="bar-isi" width="{{ $persen }}%"></td>
                            <td class="bar-sisa" width="{{ 100 - $persen }}%"></td>
                        @endif
                    </tr>
                </table>
            </div>
        @endif

        {{-- KAKI: ucapan + tanda tangan --}}
        <table class="layout kaki">
            <tr>
                <td width="58%" style="vertical-align: bottom; padding-right: 12px;">
                    <p class="terima-kasih">
                        {{ $isInfaq ? 'Jazakumullahu khairan atas infaq Anda.' : 'Terima kasih atas pembayaran Anda.' }}
                    </p>
                    {{-- Nama petugas penginput sengaja tidak dicetak: kwitansi
                         ini dipegang wali, yang mengesahkan adalah lembaga.
                         Jejaknya tetap tersimpan di sistem. --}}
                    <p class="catatan-kaki">
                        Kwitansi ini sah tanpa tanda tangan basah.<br>
                        Dicetak {{ now()->translatedFormat('d/m/Y H:i') }} · {{ $pembayaran->receipt_number }}
                    </p>
                </td>
                <td width="42%" class="tengah">
                    <p class="ttd-jabatan">{{ $ttdJabatan ?: 'Penerima' }},</p>

                    {{-- Sel bertinggi tetap — bentuk paling andal di DomPDF
                         untuk ruang yang tingginya dijamin. 24px cukup untuk
                         tanda tangan pulpen; 54px bila gambar terpasang. --}}
                    <table class="layout">
                        <tr>
                            <td height="{{ $ttdGambar ? 54 : 24 }}" align="center" class="ttd-kotak">
                                @if($ttdGambar)
                                    <img src="{{ $ttdGambar }}" height="54" alt="">
                                @endif
                            </td>
                        </tr>
                    </table>

                    <p class="ttd-nama">{{ $ttdNama ?: ($petugas ?: 'Bendahara') }}</p>
                </td>
            </tr>
        </table>

    </div>
</div>

</body>
</html>