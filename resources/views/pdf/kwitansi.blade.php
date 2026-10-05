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

        /* padding-bottom HARUS di sel, bukan di tabel: DomPDF mengabaikan
           padding pada elemen <table>, itulah sebab badge "PEMBAYARAN
           TAGIHAN" menempel di garis kop. */
        .kop { border-bottom: 2px solid #57534e; }
        .kop td { padding-bottom: 10px; }
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

        /* Jumlah yang diterima pada kwitansi INI. Kotak besar di bawah
           sudah dihapus, jadi angka ini yang menggantikan perannya. */
        .jumlah { font-size: 14px; font-weight: bold; }
        .terbilang { font-size: 8.5px; font-style: italic; color: #57534e; }
        .ttd-nama { margin: 0; font-weight: bold; border-top: 1px solid #57534e; display: inline-block; padding: 3px 12px 0 12px; font-size: 10px; }
        /* Kotak tanda tangan: tinggi tetap, isinya boleh ada boleh tidak.
           Tidak memakai margin negatif — DomPDF tidak menanganinya dengan
           benar, gambarnya tertarik naik ke baris jabatan dan berhimpitan
           dengan nama di bawahnya. */
        /* line-height & font-size nol: <img> adalah elemen inline yang
           duduk di garis baseline, sehingga menyisakan ruang turunan huruf
           di bawahnya. Itu yang membuat nama penanda tangan terdorong
           menjauh dari tanda tangannya. */
        .ttd-kotak { vertical-align: bottom; text-align: center; line-height: 0; font-size: 0; }
        .footer { font-size: 7px; color: #a8a29e; margin: 3px 0 0 0; }
        .lunas { color: #059669; font-weight: bold; }
        .belum { color: #dc2626; font-weight: bold; }
        /* Stempel LUNAS. Bingkai ganda, tidak dimiringkan: DomPDF
           menangani transform: rotate() dengan tidak konsisten, dan
           stempel miring separuh jauh lebih buruk daripada yang lurus. */
        .stempel {
            display: inline-block;
            border: 2.5px solid #059669; outline: 1px solid #059669;
            outline-offset: 2px; border-radius: 5px;
            color: #059669; padding: 5px 18px;
            font-size: 17px; font-weight: bold; letter-spacing: 4px;
        }
        .stempel-tgl { margin: 6px 0 0 0; font-size: 7.5px; color: #059669; letter-spacing: .5px; }
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

    // max(0, ...) menjaga sisa tidak pernah tercetak negatif bila ada
    // kelebihan bayar — angka minus di kwitansi selalu memicu pertanyaan
    // yang tidak ada jawabannya.
    $totalTagihan = (float) ($pembayaran->tagihan?->total_amount ?? 0);
    $sisaTagihan  = max(0, (float) ($pembayaran->tagihan?->remaining_balance ?? 0));
    $sudahDibayar = max(0, $totalTagihan - $sisaTagihan);

    $untuk = $pembayaran->tagihan
        ? ($pembayaran->tagihan->header?->title ?? 'Tagihan')
        : $kategoriLabel;

    // Nama & alamat di kop. Diambil dari pengaturan supaya pemilik bisa
    // mengubahnya sendiri; config('app.name') hanya cadangan untuk
    // instalasi yang belum mengisinya. APP_NAME tidak dipakai langsung
    // karena ia juga menjadi judul tab browser di seluruh aplikasi —
    // mengubahnya demi kwitansi akan mengubah hal lain tanpa diduga.
    $kopNama   = \App\Models\Setting::get('kop_nama', config('app.name'));
    $kopAlamat = \App\Models\Setting::get('kop_alamat', config('app.school_address'));

    // Identitas penanda tangan — diatur super-admin di Pengaturan Sistem.
    // Semua nilainya boleh kosong: kwitansi tetap tercetak, hanya kembali
    // ke bentuk lama yang ditandatangani manual dengan pulpen.
    $ttdAktif   = \App\Models\Setting::get('ttd_aktif') === '1';
    $ttdGambar  = $ttdAktif ? \App\Models\Setting::get('ttd_gambar') : null;
    $ttdNama    = \App\Models\Setting::get('ttd_nama');
    $ttdJabatan = \App\Models\Setting::get('ttd_jabatan');

    // Petugas yang menginput. display_name dipakai, bukan name, karena
    // kolom users.name berisi username login — "demo.bendahara" tidak
    // pantas tercetak di berkas yang dipegang wali santri.
    $petugas = $pembayaran->penyetuju?->display_name;
@endphp

<div class="kwitansi">

    {{-- KOP --}}
    <table class="layout kop">
        <tr>
            @if($logo)
                <td width="12%" style="vertical-align: middle;">
                    <img src="{{ $logo }}" height="48" alt="">
                </td>
                <td width="48%" style="vertical-align: middle; padding-left: 10px;">
            @else
                <td width="60%" style="vertical-align: middle;">
            @endif
                <p class="kop-nama">{{ $kopNama }}</p>
                @if($kopAlamat)
                    <p class="kop-alamat">{{ $kopAlamat }}</p>
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
        {{-- Nominal kwitansi ini. Kotak besar di bawah sudah dihapus,
             jadi tanpa baris ini kwitansi tidak menyebutkan sama sekali
             berapa uang yang baru diterima. --}}
        <tr>
            <td class="label">Jumlah dibayar</td>
            <td class="titik">:</td>
            <td class="nilai">
                <span class="jumlah">Rp {{ number_format($pembayaran->nominal, 0, ',', '.') }},-</span>
            </td>
        </tr>

        {{-- HANYA muncul bila menambah informasi, yaitu saat tagihannya
             dicicil. Pada pembayaran lunas sekali bayar, akumulasinya
             sama persis dengan baris di atas. Kata LUNAS juga tidak
             diulang di sini — stempel di bawah sudah menyatakannya. --}}
        @if($pembayaran->tagihan && (int) $sudahDibayar !== (int) $pembayaran->nominal)
            <tr>
                <td class="label">Total pembayaran</td>
                <td class="titik">:</td>
                <td class="nilai">
                    <strong>Rp {{ number_format($sudahDibayar, 0, ',', '.') }}</strong>
                    <span class="muted">dari Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span>
                    @if(! $lunas)
                        <span class="belum">· sisa Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
        @endif
    </table>

    {{-- Terbilang berdiri di luar tabel: tanpa label, tanpa titik-titik.
         Itu bentuk yang dikenal orang dari kwitansi kertas. --}}
    <p class="terbilang" style="margin: 7px 0 0 0;">
        # {{ ucfirst(trim(\App\Support\Terbilang::of((int) $pembayaran->nominal))) }} rupiah #
    </p>

    {{-- STEMPEL & TANDA TANGAN --}}
    <table class="layout" style="margin-top: 14px;">
        <tr>
            {{-- Saat belum lunas kolom ini dibiarkan kosong: kwitansi
                 cicilan tidak boleh membawa tanda apa pun yang bisa
                 disalahbaca sebagai pelunasan. --}}
            <td width="55%" valign="middle" style="padding-top: 8px;">
                @if($lunas)
                    <span class="stempel">LUNAS</span>
                    <p class="stempel-tgl">{{ $pembayaran->payment_date?->translatedFormat('d F Y') }}</p>
                @endif
            </td>
            <td width="45%" class="text-center">
                <p class="muted" style="margin: 0; font-size: 8.5px;">
                    {{ $pembayaran->payment_date?->translatedFormat('d F Y') }}
                </p>

                {{-- Jabatan pimpinan bila tanda tangan lembaga dipasang;
                     kalau belum, kembali ke "Penerima," seperti semula. --}}
                <p style="margin: 2px 0 0 0; font-size: 9.5px;">{{ $ttdJabatan ?: 'Penerima' }},</p>

                {{-- Satu sel tabel bertinggi tetap. Ini bentuk paling andal
                     di DomPDF untuk ruang kosong yang tingginya dijamin:
                     div dengan height sering diabaikan, dan gambar inline
                     ikut mengalir ke baris tetangganya. Tingginya sama
                     baik tanda tangan terpasang maupun tidak, sehingga
                     kwitansi tetap muat satu halaman A5. --}}
                <table class="layout">
                    <tr>
                        {{-- Dua tinggi berbeda, dan itu disengaja. Tanpa
                             tanda tangan, 26px adalah ruang yang cukup untuk
                             membubuhkan tanda tangan dengan pulpen — sama
                             persis dengan versi sebelum fitur ini ada, jadi
                             kwitansi instalasi yang belum memasang tanda
                             tangan tidak berubah tingginya sedikit pun.
                             Dengan tanda tangan, 62px supaya coretannya
                             terbaca; 28px membuatnya seperti noda. Ini
                             kira-kira batas praktisnya — lebih dari ini
                             dan kwitansi berisiko pecah jadi dua halaman.
                             Tingginya dibatasi, lebarnya ikut proporsi —
                             tanda tangan paling memanjang sekalipun masih
                             muat di kolom kanan yang selebar 45%. --}}
                        <td height="{{ $ttdGambar ? 60 : 26 }}" align="center" class="ttd-kotak">
                            @if($ttdGambar)
                                <img src="{{ $ttdGambar }}" height="60" alt="">
                            @endif
                        </td>
                    </tr>
                </table>

                <p class="ttd-nama">{{ $ttdNama ?: ($petugas ?: 'Bendahara') }}</p>

                {{-- Petugas tetap tercatat meski yang menandatangani
                     lembaga. Ditaruh di kaki, bukan sebagai baris tabel,
                     supaya tidak menambah tinggi halaman. --}}
                {{-- Nama petugas penginput sengaja TIDAK dicetak di sini.
                     Kwitansi ini dipegang wali santri, dan yang mengesahkan
                     adalah lembaga lewat tanda tangan di atas. Jejak siapa
                     yang menginput tetap tersimpan di sistem (kolom
                     dibuat_oleh pada pembayaran) dan terlihat di panel
                     keuangan — hanya tidak ikut ke kertas. --}}
                <p class="footer">
                    Sah tanpa tanda tangan basah · dicetak {{ now()->translatedFormat('d/m/Y H:i') }}
                </p>
            </td>
        </tr>
    </table>

</div>

</body>
</html>