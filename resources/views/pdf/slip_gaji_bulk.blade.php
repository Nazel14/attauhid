<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji {{ $periode }}</title>
    <style>
        /* Gaya disamakan dengan kwitansi: aksen hijau, label kecil kapital,
           angka utama di kartu. Ditulis untuk DomPDF — tata letak memakai
           tabel, tanpa flex/grid, outline, maupun margin negatif. */
        @page { margin: 12mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1B1D19; margin: 0; }
        p { margin: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 0; vertical-align: top; }
        .kanan { text-align: right; }
        .redup { color: #7A8074; }

        .slip { border: 1px solid #E1E4D8; border-radius: 10px; page-break-inside: avoid; }
        .slip + .slip { margin-top: 12px; }
        .aksen { height: 4px; background: #7EB842; border-radius: 10px 10px 0 0; }
        .isi-slip { padding: 12px 16px 12px 16px; }

        /* Kop */
        .kop-nama { font-size: 12.5px; font-weight: bold; color: #3D5C24; }
        .kop-alamat { margin-top: 2px; font-size: 7.5px; color: #7A8074; }
        .label-dok { font-size: 7px; font-weight: bold; letter-spacing: 2.5px; color: #649634; }
        .judul-dok { margin-top: 2px; font-size: 11.5px; font-weight: bold; }

        .kecil-caps { font-size: 6.8px; font-weight: bold; letter-spacing: 1.1px; color: #7A8074; text-transform: uppercase; }

        /* Identitas pegawai */
        .pegawai { margin-top: 10px; background: #F6F7F2; border: 1px solid #E1E4D8; border-radius: 8px; }
        .pegawai td.isi { padding: 8px 12px; }
        .nama-pegawai { margin-top: 2px; font-size: 11px; font-weight: bold; }

        /* Dua kolom rincian */
        .kolom-kiri { padding-right: 10px !important; }
        .kolom-kanan { padding-left: 10px !important; }
        .judul-kolom { padding: 12px 0 4px 0; border-bottom: 1.5px solid #E1E4D8; }
        .titik-hijau { color: #7EB842; }
        .titik-merah { color: #DC2626; }
        .baris td { padding: 3.5px 0; border-bottom: 1px solid #F1F2EC; }
        .subtotal td { padding: 5px 0 0 0; font-weight: bold; }
        .plus { color: #4D752A; }
        .minus { color: #B91C1C; }
        .kosong { padding: 4px 0; font-size: 8.5px; color: #9CA196; font-style: italic; }

        /* Take home pay */
        .thp { margin-top: 10px; background: #F4F9EC; border: 1px solid #CEE6AF; border-radius: 8px; }
        .thp td.isi { padding: 9px 14px; vertical-align: middle; }
        .thp-nominal { font-size: 17px; font-weight: bold; color: #3D5C24; }
        .terbilang { margin-top: 2px; font-size: 7.8px; font-style: italic; color: #5C6157; }

        .kaki { margin-top: 8px; font-size: 7.3px; color: #9CA196; }
    </style>
</head>
<body>

@php
    // Logo di-decode sekali di luar loop supaya render tetap cepat
    // meski satu batch berisi puluhan slip.
    $logo = null;
    foreach (['Logo.png', 'logo.png', 'favicon.ico'] as $k) {
        $p = public_path($k);
        if (file_exists($p)) {
            $logo = 'data:image/' . pathinfo($p, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($p));
            break;
        }
    }

    // Kop dibaca dari pengaturan, sama seperti kwitansi.
    $kopNama   = \App\Models\Setting::get('kop_nama', config('app.name'));
    $kopAlamat = \App\Models\Setting::get('kop_alamat', config('app.school_address'));

    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

@foreach ($header->details as $d)
    @php
        // Subtotal dihitung dari kolom snapshot yang sama dengan barisnya,
        // jadi selalu cocok dengan angka yang tercetak di atasnya.
        $totalTerima = (float) $d->gaji_pokok + ($d->nominal_tambahan > 0 ? (float) $d->nominal_tambahan : 0);
        foreach ($d->allowances as $a) { $totalTerima += (float) $a->nominal_snapshot; }

        $totalPotong = 0;
        foreach ($d->deductions as $x) { $totalPotong += (float) $x->nominal_snapshot; }
    @endphp

    <div class="slip">
        <div class="aksen"></div>
        <div class="isi-slip">

            {{-- KOP --}}
            <table class="layout">
                <tr>
                    @if($logo)
                        <td width="7%" style="vertical-align: middle;"><img src="{{ $logo }}" style="height: 30px;" alt=""></td>
                        <td width="58%" style="vertical-align: middle; padding-left: 6px;">
                    @else
                        <td width="65%" style="vertical-align: middle;">
                    @endif
                        <p class="kop-nama">{{ $kopNama }}</p>
                        @if($kopAlamat)
                            <p class="kop-alamat">{{ $kopAlamat }}</p>
                        @endif
                    </td>
                    <td width="35%" class="kanan" style="vertical-align: middle;">
                        <p class="label-dok">SLIP GAJI</p>
                        <p class="judul-dok">Periode {{ $periode }}</p>
                    </td>
                </tr>
            </table>

            {{-- PEGAWAI --}}
            <div class="pegawai">
                <table class="layout">
                    <tr>
                        <td class="isi" width="50%">
                            <p class="kecil-caps">Nama pegawai</p>
                            {{-- Nama cadangan dari snapshot: baris gaji yang sudah dibayar tetap
                                 disimpan walau gurunya dihapus. --}}
                            <p class="nama-pegawai">{{ $d->employee?->name ?? $d->nama_pegawai_snapshot ?? '—' }}</p>
                        </td>
                        <td class="isi" width="30%">
                            <p class="kecil-caps">Jabatan</p>
                            <p style="margin-top: 3px;">{{ $d->employee?->jabatan?->nama_jabatan ?? '—' }}</p>
                        </td>
                        <td class="isi kanan" width="20%">
                            @if($d->employee?->nip)
                                <p class="kecil-caps">NIY</p>
                                <p style="margin-top: 3px; font-weight: bold;">{{ $d->employee->nip }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            {{-- RINCIAN: penerimaan di kiri, potongan di kanan --}}
            <table class="layout">
                <tr>
                    <td width="50%" class="kolom-kiri">
                        <table class="layout">
                            <tr><td class="judul-kolom" colspan="2"><span class="kecil-caps"><span class="titik-hijau">&#9679;</span> Penerimaan</span></td></tr>
                            <tr class="baris">
                                <td>Gaji Pokok</td>
                                <td class="kanan">{{ $rp($d->gaji_pokok) }}</td>
                            </tr>
                            {{-- Nama & nominal dari kolom snapshot, bukan master
                                 komponen — slip lama tidak berubah bila master diedit. --}}
                            @foreach ($d->allowances as $a)
                                <tr class="baris">
                                    <td>{{ $a->nama_tunjangan_snapshot }}</td>
                                    <td class="kanan plus">+ {{ $rp($a->nominal_snapshot) }}</td>
                                </tr>
                            @endforeach
                            @if ($d->nominal_tambahan > 0)
                                <tr class="baris">
                                    <td>{{ $d->keterangan_tambahan ?: 'Tambahan' }}</td>
                                    <td class="kanan plus">+ {{ $rp($d->nominal_tambahan) }}</td>
                                </tr>
                            @endif
                            <tr class="subtotal">
                                <td>Jumlah penerimaan</td>
                                <td class="kanan">{{ $rp($totalTerima) }}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="50%" class="kolom-kanan">
                        <table class="layout">
                            <tr><td class="judul-kolom" colspan="2"><span class="kecil-caps"><span class="titik-merah">&#9679;</span> Potongan</span></td></tr>
                            @forelse ($d->deductions as $x)
                                <tr class="baris">
                                    <td>{{ $x->nama_potongan_snapshot }}</td>
                                    <td class="kanan minus">&minus; {{ $rp($x->nominal_snapshot) }}</td>
                                </tr>
                            @empty
                                <tr><td class="kosong" colspan="2">Tidak ada potongan</td></tr>
                            @endforelse
                            <tr class="subtotal">
                                <td>Jumlah potongan</td>
                                <td class="kanan {{ $totalPotong > 0 ? 'minus' : '' }}">{{ $totalPotong > 0 ? '− ' : '' }}{{ $rp($totalPotong) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- TAKE HOME PAY --}}
            <div class="thp">
                <table class="layout">
                    <tr>
                        <td class="isi" width="40%">
                            <p class="kecil-caps" style="color: #4D752A;">Take home pay</p>
                            <p class="terbilang">{{ ucfirst(trim(\App\Support\Terbilang::of((int) $d->take_home_pay))) }} rupiah</p>
                        </td>
                        <td class="isi kanan" width="60%">
                            <p class="thp-nominal">{{ $rp($d->take_home_pay) }}</p>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- KAKI --}}
            <table class="layout kaki">
                <tr>
                    <td width="65%">
                        Dibayarkan {{ $header->dibayar_at?->translatedFormat('d F Y') }}@if($header->rekening) melalui {{ $header->rekening->account_name }}@endif
                        · dokumen resmi tanpa tanda tangan basah
                    </td>
                    <td width="35%" class="kanan">
                        {{-- display_name, bukan name: users.name berisi username
                             login, dan slip ini dipegang pegawai. --}}
                        Disahkan: <strong style="color: #5C6157;">{{ $header->pembayar?->display_name ?? '—' }}</strong>
                    </td>
                </tr>
            </table>

        </div>
    </div>
@endforeach

</body>
</html>