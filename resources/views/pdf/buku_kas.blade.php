<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Kas Umum</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1c1917; margin: 0; }
        .kop { border-bottom: 2px solid #57534e; padding-bottom: 7px; margin-bottom: 10px; }
        .layout { width: 100%; border-collapse: collapse; }
        .primary { color: #D97757; }
        .muted { color: #78716c; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th { background: #f5f4ef; border: 1px solid #d6d3d1; padding: 5px 6px; text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; }
        table.data td { border: 1px solid #e7e5e4; padding: 4px 6px; }
        .debit { color: #059669; }
        .kredit { color: #dc2626; }
        .baris-awal td { background: #faf9f5; font-weight: bold; }
        .baris-total td { background: #f5f4ef; border-top: 1.5px solid #57534e; font-weight: bold; font-size: 10px; }
    </style>
</head>
<body>

<table class="layout kop">
    <tr>
        <td width="60%">
            <p class="primary" style="margin:0; font-size:14px; font-weight:bold;">{{ config('app.name') }}</p>
            @if(config('app.school_address'))
                <p class="muted" style="margin:2px 0 0 0; font-size:8px;">{{ config('app.school_address') }}</p>
            @endif
        </td>
        <td width="40%" class="text-right">
            <p style="margin:0; font-size:15px; font-weight:bold; letter-spacing:1px;">BUKU KAS UMUM</p>
            <p style="margin:3px 0 0 0;"><strong>{{ $rekening->account_name }}</strong> <span class="muted">({{ $rekening->type }})</span></p>
            <p class="muted" style="margin:1px 0 0 0;">
                {{ $dari->translatedFormat('d M Y') }} — {{ $sampai->translatedFormat('d M Y') }}
            </p>
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
            <th width="4%" class="text-center">No</th>
            <th width="13%">Tanggal</th>
            <th width="37%">Keterangan</th>
            <th width="15%" class="text-right">Debit</th>
            <th width="15%" class="text-right">Kredit</th>
            <th width="16%" class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <tr class="baris-awal">
            <td colspan="5">SALDO AWAL PERIODE</td>
            <td class="text-right">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
        </tr>

        @forelse($mutasi as $i => $m)
            @php
                if ($m->jenis === 'Debit') { $saldo += $m->nominal; $totalDebit += $m->nominal; }
                else { $saldo -= $m->nominal; $totalKredit += $m->nominal; }
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $m->tanggal_transaksi->translatedFormat('d M Y') }}</td>
                <td>
                    {{ $m->keterangan }}
                    @if($m->pembuat)
                        <br><span class="muted" style="font-size:7.5px;">oleh {{ $m->pembuat->name }}</span>
                    @endif
                </td>
                <td class="text-right debit">{{ $m->jenis === 'Debit' ? 'Rp ' . number_format($m->nominal, 0, ',', '.') : '—' }}</td>
                <td class="text-right kredit">{{ $m->jenis === 'Kredit' ? 'Rp ' . number_format($m->nominal, 0, ',', '.') : '—' }}</td>
                <td class="text-right">Rp {{ number_format($m->saldo_setelah_transaksi, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center muted" style="padding:18px;">Tidak ada mutasi pada periode ini.</td></tr>
        @endforelse

        <tr class="baris-total">
            <td colspan="3" class="text-right">TOTAL</td>
            <td class="text-right debit">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
            <td class="text-right kredit">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($saldo, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<table class="layout" style="margin-top:22px;">
    <tr>
        <td width="60%" class="muted" style="font-size:7.5px; vertical-align:bottom;">
            Dicetak {{ now()->translatedFormat('d F Y H:i') }} · dokumen sah tanpa tanda tangan basah
        </td>
        <td width="40%" class="text-center">
            <p class="muted" style="margin:0;">Mengetahui,</p>
            <div style="height:45px;"></div>
            <p style="margin:0; font-weight:bold; border-top:1px solid #57534e; display:inline-block; padding:3px 20px 0 20px;">Bendahara</p>
        </td>
    </tr>
</table>

</body>
</html>