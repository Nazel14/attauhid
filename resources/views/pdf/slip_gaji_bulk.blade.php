<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji {{ $periode }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1c1917; margin: 0; }
        .slip { border: 1px solid #a8a29e; border-radius: 5px; padding: 12px 14px; page-break-inside: avoid; }
        .slip + .slip { margin-top: 12px; }
        .layout { width: 100%; border-collapse: collapse; }
        .layout td { padding: 2px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .muted { color: #78716c; }
        .primary { color: #D97757; }
        .kop { border-bottom: 1.5px solid #57534e; padding-bottom: 7px; margin-bottom: 9px; }
        .rincian { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .rincian th { text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: .4px;
                      color: #78716c; border-bottom: 1px solid #e7e5e4; padding: 4px 0; }
        .rincian td { padding: 2.5px 0; }
        .total-row td { border-top: 1.5px solid #57534e; padding-top: 5px; font-weight: bold; font-size: 12px; }
        .thp { color: #059669; }
        .plus { color: #059669; }
        .minus { color: #dc2626; }
        .terbilang { font-size: 8px; font-style: italic; color: #57534e; margin: 3px 0 0 0; }
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

    // Kop dibaca dari pengaturan, sama seperti kwitansi. Sebelumnya tiap
    // PDF memakai config('app.name') sendiri-sendiri, sehingga setelah
    // pemilik mengubah nama lembaga di Pengaturan Sistem, hanya kwitansi
    // yang ikut berubah — tiga dokumen lain tetap memakai nama lama.
    $kopNama   = \App\Models\Setting::get('kop_nama', config('app.name'));
    $kopAlamat = \App\Models\Setting::get('kop_alamat', config('app.school_address'));
@endphp

@foreach ($header->details as $d)
    <div class="slip">
        <table class="layout kop">
            <tr>
                @if($logo)
                    <td width="8%" style="vertical-align: middle;"><img src="{{ $logo }}" style="height: 28px;" alt=""></td>
                    <td width="52%" style="vertical-align: middle; padding-left: 6px;">
                @else
                    <td width="60%" style="vertical-align: middle;">
                @endif
                    <p class="primary" style="margin: 0; font-size: 12px; font-weight: bold;">{{ $kopNama }}</p>
                    @if($kopAlamat)
                        <p class="muted" style="margin: 1px 0 0 0; font-size: 7.5px;">{{ $kopAlamat }}</p>
                    @endif
                </td>
                <td width="40%" class="text-right">
                    <p style="margin: 0; font-size: 13px; font-weight: bold; letter-spacing: 1px;">SLIP GAJI</p>
                    <p class="muted" style="margin: 2px 0 0 0; font-size: 9px;">Periode {{ $periode }}</p>
                </td>
            </tr>
        </table>

        <table class="layout">
            <tr>
                <td width="55%">
                    <span class="muted" style="font-size: 8px;">NAMA PEGAWAI</span><br>
                    <strong style="font-size: 11px;">{{ $d->employee?->name ?? '—' }}</strong><br>
                    <span class="muted">{{ $d->employee?->jabatan?->nama_jabatan ?? '—' }}</span>
                </td>
                <td width="45%" class="text-right">
                    @if($d->employee?->nip)
                        <span class="muted" style="font-size: 8px;">NIY</span><br>
                        <strong>{{ $d->employee->nip }}</strong>
                    @endif
                </td>
            </tr>
        </table>

        <table class="rincian">
            <tr>
                <th width="60%">Komponen</th>
                <th width="40%" class="text-right">Nominal</th>
            </tr>

            <tr>
                <td>Gaji Pokok</td>
                <td class="text-right">Rp {{ number_format($d->gaji_pokok, 0, ',', '.') }}</td>
            </tr>

            {{-- Nama & nominal diambil dari kolom snapshot, bukan master komponen,
                 sehingga slip lama tidak berubah bila master diedit/dihapus. --}}
            @foreach ($d->allowances as $a)
                <tr>
                    <td class="plus">{{ $a->nama_tunjangan_snapshot }}</td>
                    <td class="text-right plus">+ Rp {{ number_format($a->nominal_snapshot, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            @if ($d->nominal_tambahan > 0)
                <tr>
                    <td class="plus">{{ $d->keterangan_tambahan ?: 'Tambahan' }}</td>
                    <td class="text-right plus">+ Rp {{ number_format($d->nominal_tambahan, 0, ',', '.') }}</td>
                </tr>
            @endif

            @foreach ($d->deductions as $p)
                <tr>
                    <td class="minus">{{ $p->nama_potongan_snapshot }}</td>
                    <td class="text-right minus">− Rp {{ number_format($p->nominal_snapshot, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td>TAKE HOME PAY</td>
                <td class="text-right thp">Rp {{ number_format($d->take_home_pay, 0, ',', '.') }}</td>
            </tr>
        </table>

        <p class="terbilang">
            # {{ ucfirst(trim(\App\Support\Terbilang::of((int) $d->take_home_pay))) }} rupiah #
        </p>

        <table class="layout" style="margin-top: 8px;">
            <tr>
                <td width="60%" class="muted" style="font-size: 7.5px;">
                    Dibayarkan {{ $header->dibayar_at?->translatedFormat('d F Y') }}
                    @if($header->rekening) melalui {{ $header->rekening->account_name }} @endif
                    · dokumen resmi tanpa tanda tangan basah
                </td>
                <td width="40%" class="text-right muted" style="font-size: 7.5px;">
                    {{-- display_name, bukan name: kolom users.name berisi
                         username login, dan slip gaji ini dipegang pegawai. --}}
                    Disahkan: {{ $header->pembayar?->display_name ?? '—' }}
                </td>
            </tr>
        </table>
    </div>
@endforeach

</body>
</html>