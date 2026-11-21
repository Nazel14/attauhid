<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Kas Umum</title>
    <style>
        /* Gaya disamakan dengan kwitansi. Ditulis untuk DomPDF — tata letak
           memakai tabel, tanpa flex/grid. */
        @page { margin: 12mm 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1B1D19; margin: 0; }
        p { margin: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 0; vertical-align: top; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .redup { color: #7A8074; }

        .aksen { height: 4px; background: #7EB842; border-radius: 3px; }

        /* Kop */
        .kop { margin-top: 10px; }
        .kop-nama { font-size: 14px; font-weight: bold; color: #3D5C24; }
        .kop-alamat { margin-top: 2px; font-size: 8px; color: #7A8074; }
        .label-dok { font-size: 7.5px; font-weight: bold; letter-spacing: 2.5px; color: #649634; }
        .rekening { margin-top: 3px; font-size: 12px; font-weight: bold; }

        .kecil-caps { font-size: 6.8px; font-weight: bold; letter-spacing: 1.1px; color: #7A8074; text-transform: uppercase; }

        /* Ringkasan empat kartu */
        .ringkas { margin-top: 12px; }
        .kartu { background: #F6F7F2; border: 1px solid #E1E4D8; border-radius: 8px; }
        .kartu td.isi { padding: 8px 11px; }
        .angka { margin-top: 3px; font-size: 11.5px; font-weight: bold; }
        .kartu-akhir { background: #F4F9EC; border-color: #CEE6AF; }

        /* Tabel mutasi */
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.data th {
            background: #F6F7F2; padding: 6px 7px; text-align: left;
            font-size: 6.8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #7A8074;
            border-top: 1px solid #E1E4D8; border-bottom: 1px solid #E1E4D8;
        }
        /* Perataan kolom harus disebut eksplisit: aturan th di atas lebih
           spesifik daripada .kanan/.tengah sehingga menimpanya. */
        table.data th.kanan { text-align: right; }
        table.data th.tengah { text-align: center; }
        table.data td { padding: 5px 7px; border-bottom: 1px solid #EDEFE6; }
        /* Belang dari kelas, bukan :nth-child — dukungan DomPDF tidak
           konsisten untuk selektor itu. */
        .belang td { background: #FBFCF8; }
        .debit { color: #4D752A; }
        .kredit { color: #B91C1C; }
        .kosong-nilai { color: #C8CCC2; }
        .baris-awal td { background: #F6F7F2; font-weight: bold; }
        .baris-total td { background: #F4F9EC; border-top: 1.5px solid #B0D782; border-bottom: 1.5px solid #B0D782; font-weight: bold; font-size: 9.5px; }

        .ttd-jabatan { font-size: 8.5px; color: #5C6157; }
        .ttd-nama { display: inline-block; padding: 3px 20px 0 20px; border-top: 1px solid #7A8074; font-weight: bold; font-size: 9.5px; }
    </style>
</head>
<body>

@php
    // Kop dibaca dari pengaturan, sama seperti kwitansi.
    $kopNama   = \App\Models\Setting::get('kop_nama', config('app.name'));
    $kopAlamat = \App\Models\Setting::get('kop_alamat', config('app.school_address'));

    // Tanda tangan lembaga, sumber yang sama dengan kwitansi.
    $ttdAktif   = \App\Models\Setting::get('ttd_aktif') === '1';
    $ttdGambar  = $ttdAktif ? \App\Models\Setting::get('ttd_gambar') : null;
    $ttdNama    = \App\Models\Setting::get('ttd_nama');
    $ttdJabatan = \App\Models\Setting::get('ttd_jabatan');

    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    // Ringkasan dihitung lebih dulu supaya bisa tampil DI ATAS tabel.
    // Rumusnya sama persis dengan akumulasi di dalam tabel di bawah.
    $ringkasDebit = 0;
    $ringkasKredit = 0;
    foreach ($mutasi as $m) {
        if ($m->jenis === 'Debit') { $ringkasDebit += $m->nominal; }
        else { $ringkasKredit += $m->nominal; }
    }
    $ringkasAkhir = $saldoAwal + $ringkasDebit - $ringkasKredit;
@endphp

<div class="aksen"></div>

{{-- KOP --}}
<table class="layout kop">
    <tr>
        <td width="58%" style="vertical-align: bottom;">
            <p class="kop-nama">{{ $kopNama }}</p>
            @if($kopAlamat)
                <p class="kop-alamat">{{ $kopAlamat }}</p>
            @endif
        </td>
        <td width="42%" class="kanan" style="vertical-align: bottom;">
            <p class="label-dok">BUKU KAS UMUM</p>
            <p class="rekening">{{ $rekening->account_name }} <span class="redup" style="font-weight: normal; font-size: 9px;">({{ $rekening->type }})</span></p>
            <p class="redup" style="margin-top: 2px;">
                {{ $dari->translatedFormat('d M Y') }} — {{ $sampai->translatedFormat('d M Y') }}
            </p>
        </td>
    </tr>
</table>

{{-- RINGKASAN --}}
<table class="layout ringkas">
    <tr>
        <td width="24%">
            <div class="kartu"><table class="layout"><tr><td class="isi">
                <p class="kecil-caps">Saldo awal</p>
                <p class="angka">{{ $rp($saldoAwal) }}</p>
            </td></tr></table></div>
        </td>
        <td width="1.3%"></td>
        <td width="24%">
            <div class="kartu"><table class="layout"><tr><td class="isi">
                <p class="kecil-caps">Total masuk</p>
                <p class="angka debit">{{ $rp($ringkasDebit) }}</p>
            </td></tr></table></div>
        </td>
        <td width="1.3%"></td>
        <td width="24%">
            <div class="kartu"><table class="layout"><tr><td class="isi">
                <p class="kecil-caps">Total keluar</p>
                <p class="angka kredit">{{ $rp($ringkasKredit) }}</p>
            </td></tr></table></div>
        </td>
        <td width="1.3%"></td>
        <td width="24%">
            <div class="kartu kartu-akhir"><table class="layout"><tr><td class="isi">
                <p class="kecil-caps" style="color: #4D752A;">Saldo akhir</p>
                <p class="angka" style="color: #3D5C24;">{{ $rp($ringkasAkhir) }}</p>
            </td></tr></table></div>
        </td>
    </tr>
</table>

@php
    $saldo = $saldoAwal;
    $totalDebit = 0;
    $totalKredit = 0;
@endphp

<table class="data">
    <thead>
        <tr>
            <th width="4%" class="tengah">No</th>
            <th width="12%">Tanggal</th>
            <th width="38%">Keterangan</th>
            <th width="15%" class="kanan">Debit</th>
            <th width="15%" class="kanan">Kredit</th>
            <th width="16%" class="kanan">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <tr class="baris-awal">
            <td colspan="5">Saldo awal periode</td>
            <td class="kanan">{{ $rp($saldoAwal) }}</td>
        </tr>

        @forelse($mutasi as $i => $m)
            @php
                if ($m->jenis === 'Debit') { $saldo += $m->nominal; $totalDebit += $m->nominal; }
                else { $saldo -= $m->nominal; $totalKredit += $m->nominal; }
            @endphp
            <tr class="{{ $i % 2 === 1 ? 'belang' : '' }}">
                <td class="tengah redup">{{ $i + 1 }}</td>
                <td>{{ $m->tanggal_transaksi->translatedFormat('d M Y') }}</td>
                <td>
                    {{ $m->keterangan }}
                    @if($m->pembuat)
                        <br><span class="redup" style="font-size: 7.3px;">oleh {{ $m->pembuat->display_name }}</span>
                    @endif
                </td>
                <td class="kanan {{ $m->jenis === 'Debit' ? 'debit' : 'kosong-nilai' }}">{{ $m->jenis === 'Debit' ? $rp($m->nominal) : '—' }}</td>
                <td class="kanan {{ $m->jenis === 'Kredit' ? 'kredit' : 'kosong-nilai' }}">{{ $m->jenis === 'Kredit' ? $rp($m->nominal) : '—' }}</td>
                <td class="kanan">{{ $rp($m->saldo_setelah_transaksi) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="tengah redup" style="padding: 18px;">Tidak ada mutasi pada periode ini.</td></tr>
        @endforelse

        <tr class="baris-total">
            <td colspan="3" class="kanan">TOTAL</td>
            <td class="kanan debit">{{ $rp($totalDebit) }}</td>
            <td class="kanan kredit">{{ $rp($totalKredit) }}</td>
            <td class="kanan">{{ $rp($saldo) }}</td>
        </tr>
    </tbody>
</table>

<table class="layout" style="margin-top: 22px; page-break-inside: avoid;">
    <tr>
        <td width="60%" class="redup" style="font-size: 7.5px; vertical-align: bottom;">
            Dicetak {{ now()->translatedFormat('d F Y H:i') }} · dokumen sah tanpa tanda tangan basah
        </td>
        <td width="40%" class="tengah">
            <p class="ttd-jabatan">Mengetahui,</p>

            {{-- Sel bertinggi tetap: 45px ruang tanda tangan pulpen, 60px
                 bila gambar tanda tangan dipasang. Pola yang sama dengan kwitansi. --}}
            <table class="layout">
                <tr>
                    <td height="{{ $ttdGambar ? 60 : 45 }}" align="center"
                        style="vertical-align: bottom; text-align: center; line-height: 0; font-size: 0;">
                        @if($ttdGambar)
                            <img src="{{ $ttdGambar }}" height="60" alt="">
                        @endif
                    </td>
                </tr>
            </table>

            <p class="ttd-nama">{{ $ttdNama ?: 'Bendahara' }}</p>
            @if($ttdJabatan)
                <p class="redup" style="margin-top: 2px; font-size: 7.5px;">{{ $ttdJabatan }}</p>
            @endif
        </td>
    </tr>
</table>

</body>
</html>