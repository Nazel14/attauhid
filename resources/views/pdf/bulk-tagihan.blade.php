<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Massal Tagihan</title>
    <style>
        /* Gaya disamakan dengan kwitansi. DejaVu Sans — satu-satunya font
           bawaan DomPDF yang mendukung karakter non-ASCII dengan benar.
           Tata letak memakai tabel: DomPDF tidak mendukung flex/grid. */
        @page { margin: 14mm 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1B1D19; margin: 0; padding: 0; }
        p { margin: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 0; vertical-align: top; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .redup { color: #7A8074; }
        .tebal { font-weight: bold; }

        .aksen { height: 5px; background: #7EB842; border-radius: 3px; }

        /* Kop */
        .kop { margin-top: 14px; }
        .kop-nama { margin-top: 6px; font-size: 16px; font-weight: bold; color: #3D5C24; }
        .kop-alamat { margin-top: 3px; font-size: 9px; color: #7A8074; line-height: 1.4; }
        .label-dok { font-size: 8px; font-weight: bold; letter-spacing: 3px; color: #649634; }
        .nomor { margin-top: 3px; font-size: 15px; font-weight: bold; }

        .kecil-caps { font-size: 7.5px; font-weight: bold; letter-spacing: 1.3px; color: #7A8074; text-transform: uppercase; }

        /* Kartu info */
        .kartu { background: #F6F7F2; border: 1px solid #E1E4D8; border-radius: 9px; }
        .kartu td.isi { padding: 12px 14px; }
        .nama { margin-top: 4px; font-size: 13.5px; font-weight: bold; }
        .baris-info { margin-top: 2px; font-size: 9.5px; color: #5C6157; }

        .chip { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; letter-spacing: 1px; }
        .chip-belum { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .chip-cicil { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }

        .tempo { margin-top: 6px; font-size: 9.5px; }
        .tempo strong { color: #B45309; }

        /* Rincian item */
        .items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .items th {
            background: #F6F7F2; padding: 8px 10px; text-align: left;
            font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #7A8074;
            border-top: 1px solid #E1E4D8; border-bottom: 1px solid #E1E4D8;
        }
        /* Perataan kolom harus disebut eksplisit: aturan th di atas lebih
           spesifik daripada .kanan/.tengah sehingga menimpanya. */
        .items th.kanan { text-align: right; }
        .items th.tengah { text-align: center; }
        .items td { padding: 9px 10px; border-bottom: 1px solid #EDEFE6; }
        .no { color: #9CA196; }

        /* Instruksi + ringkasan */
        .instruksi { background: #F6F7F2; border: 1px solid #E1E4D8; border-radius: 9px; padding: 11px 14px; font-size: 9px; }
        .instruksi ol { margin: 5px 0 0 0; padding-left: 14px; color: #5C6157; line-height: 1.5; }
        .ringkas td { padding: 4px 0; }
        .total-sisa { margin-top: 8px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 9px; }
        .total-sisa td.isi { padding: 10px 14px; vertical-align: middle; }
        .sisa-nominal { font-size: 17px; font-weight: bold; color: #B91C1C; }

        .kaki { margin-top: 22px; padding-top: 8px; border-top: 1px solid #EDEFE6; font-size: 8px; color: #9CA196; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    @php
        // Logo di-decode sekali di luar loop supaya render PDF tetap cepat
        // meski satu batch berisi ratusan tagihan.
        $logo = null;
        foreach (['Logo.png', 'logo.png', 'favicon.ico'] as $kandidat) {
            $path = public_path($kandidat);
            if (file_exists($path)) {
                $logo = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION)
                      . ';base64,' . base64_encode(file_get_contents($path));
                break;
            }
        }

        // Identitas lembaga dari Pengaturan Sistem -> Identitas & Tanda Tangan.
        $namaSekolah   = \App\Models\Setting::get('kop_nama', config('app.name'));
        $alamatSekolah = \App\Models\Setting::get('kop_alamat', config('app.school_address', ''));

        // Dihitung sekali — sama untuk seluruh tagihan dalam batch ini.
        $jatuhTempo = $header->due_date ? \Carbon\Carbon::parse($header->due_date)->translatedFormat('d F Y') : '—';
        $dicetak    = now()->translatedFormat('d M Y');
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    @foreach($tagihans as $tagihan)
        <div class="aksen"></div>

        {{-- KOP --}}
        <table class="layout kop">
            <tr>
                <td width="62%">
                    @if($logo)
                        <img src="{{ $logo }}" style="height: 46px; width: auto;" alt="Logo">
                    @endif
                    <p class="kop-nama">{{ $namaSekolah }}</p>
                    @if($alamatSekolah)
                        <p class="kop-alamat">{{ $alamatSekolah }}</p>
                    @endif
                </td>
                <td width="38%" class="kanan" style="vertical-align: bottom;">
                    <p class="label-dok">TAGIHAN</p>
                    <p class="nomor">{{ $tagihan->invoice_number }}</p>
                    <p class="redup" style="margin-top: 3px; font-size: 9px;">Dicetak {{ $dicetak }}</p>
                </td>
            </tr>
        </table>

        {{-- INFO: penerima & gelombang --}}
        <table class="layout" style="margin-top: 16px;">
            <tr>
                <td width="49%">
                    <div class="kartu">
                        <table class="layout">
                            <tr>
                                <td class="isi">
                                    <p class="kecil-caps">Ditagihkan kepada</p>
                                    <p class="nama">{{ $tagihan->siswa?->name ?? '-' }}</p>
                                    <p class="baris-info">NIS {{ $tagihan->siswa?->nis ?? '-' }}</p>
                                    @if($tagihan->siswa?->parent_name)
                                        <p class="baris-info">Wali: {{ $tagihan->siswa->parent_name }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="2%"></td>
                <td width="49%">
                    {{-- Pakai $header dari controller, bukan $tagihan->header —
                         menghindari N+1 query pada batch berisi ratusan invoice. --}}
                    <div class="kartu">
                        <table class="layout">
                            <tr>
                                <td class="isi">
                                    <table class="layout">
                                        <tr>
                                            <td><p class="kecil-caps">Gelombang penagihan</p></td>
                                            <td class="kanan">
                                                <span class="chip {{ $tagihan->status === 'Installment' ? 'chip-cicil' : 'chip-belum' }}">
                                                    {{ $tagihan->status === 'Installment' ? 'DICICIL SEBAGIAN' : 'BELUM BAYAR' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                    <p class="nama" style="font-size: 12px;">{{ $header->title }}</p>
                                    <p class="baris-info">Periode {{ $header->period }}</p>
                                    <p class="tempo">Jatuh tempo <strong>{{ $jatuhTempo }}</strong></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- RINCIAN --}}
        <table class="items">
            <thead>
                <tr>
                    <th width="7%" class="tengah">No</th>
                    <th width="58%">Deskripsi</th>
                    <th width="35%" class="kanan">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihan->items as $index => $item)
                    <tr>
                        <td class="tengah no">{{ $index + 1 }}</td>
                        <td>{{ $item->deskripsi }}</td>
                        <td class="kanan">{{ $rp($item->nominal) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="tengah redup" style="padding: 16px;">Tidak ada rincian item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- INSTRUKSI + RINGKASAN --}}
        <table class="layout" style="margin-top: 16px;">
            <tr>
                <td width="53%">
                    <div class="instruksi">
                        <p class="tebal" style="color: #3D5C24;">Instruksi pembayaran</p>
                        <ol>
                            <li>Harap melunasi sebelum tanggal jatuh tempo.</li>
                            <li>Abaikan tagihan ini bila pembayaran sudah dilakukan.</li>
                            <li>Simpan kwitansi sebagai bukti pembayaran yang sah.</li>
                        </ol>
                    </div>
                </td>
                <td width="5%"></td>
                <td width="42%">
                    <table class="layout ringkas">
                        <tr>
                            <td class="redup">Total tagihan</td>
                            <td class="kanan">{{ $rp($tagihan->total_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="redup">Sudah dibayar</td>
                            <td class="kanan" style="color: #4D752A;">{{ $rp($tagihan->total_amount - $tagihan->remaining_balance) }}</td>
                        </tr>
                    </table>
                    <div class="total-sisa">
                        <table class="layout">
                            <tr>
                                <td class="isi"><p class="kecil-caps" style="color: #B91C1C;">Sisa tagihan</p></td>
                                <td class="isi kanan"><p class="sisa-nominal">{{ $rp($tagihan->remaining_balance) }}</p></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <p class="kaki">
            {{ $namaSekolah }} · {{ $tagihan->invoice_number }} · Dokumen ini dicetak dari sistem keuangan sekolah.
        </p>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>