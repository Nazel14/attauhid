<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Massal Tagihan</title>
    <style>
        /* DejaVu Sans — satu-satunya font bawaan DomPDF yang mendukung
           karakter non-ASCII dengan benar. Konsisten dengan PDF raport. */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #334155; margin: 0; padding: 0; }
        .table-layout { width: 100%; border-collapse: collapse; }
        .table-items { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; }
        .table-items th { background-color: #f5f4ef; border: 1px solid #d6d3d1; padding: 9px; text-align: left; font-weight: bold; color: #1c1917; }
        .table-items td { border: 1px solid #d6d3d1; padding: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-primary { color: #D97757; }
        .text-red { color: #dc2626; }
        .text-muted { color: #78716c; }
        .header-line { border: 0; border-bottom: 2px solid #e7e5e4; margin: 18px 0; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; border: 1px solid; text-transform: uppercase; }
        .badge-unpaid { background-color: #fef2f2; color: #dc2626; border-color: #fca5a5; }
        .badge-installment { background-color: #eff6ff; color: #2563eb; border-color: #93c5fd; }
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

        // Identitas sekolah — sementara diambil dari config app.
        // Untuk versi jual, ini sebaiknya jadi tabel pengaturan tersendiri
        // agar tiap klien bisa mengubahnya dari UI.
        $namaSekolah  = config('app.name');
        $alamatSekolah = config('app.school_address', '');
    @endphp

    @foreach($tagihans as $tagihan)
        <table class="table-layout">
            <tr>
                <td width="60%">
                    @if($logo)
                        <img src="{{ $logo }}" style="max-height: 60px; width: auto;" alt="Logo">
                    @endif
                    <h2 class="text-primary" style="margin: 6px 0 0 0; font-size: 16px;">{{ $namaSekolah }}</h2>
                    @if($alamatSekolah)
                        <p style="margin: 4px 0 0 0; font-size: 10px;" class="text-muted">{{ $alamatSekolah }}</p>
                    @endif
                </td>
                <td width="40%" class="text-right" valign="top">
                    <h1 style="margin: 0; font-size: 22px; color: #1c1917;">INVOICE</h1>
                    <p style="margin: 5px 0 0 0; font-size: 13px;" class="font-bold">{{ $tagihan->invoice_number }}</p>
                    <p style="margin: 2px 0 0 0; font-size: 11px;" class="text-muted">Dicetak {{ now()->translatedFormat('d M Y') }}</p>
                </td>
            </tr>
        </table>

        <hr class="header-line">

        <table class="table-layout">
            <tr>
                <td width="55%" valign="top">
                    <p style="margin: 0; font-size: 10px;" class="text-muted">DITAGIHKAN KEPADA</p>
                    <h3 style="margin: 5px 0 2px 0; font-size: 14px;">{{ $tagihan->siswa?->name ?? '-' }}</h3>
                    <p style="margin: 0;">NIS: {{ $tagihan->siswa?->nis ?? '-' }}</p>
                    @if($tagihan->siswa?->parent_name)
                        <p style="margin: 2px 0 0 0; font-size: 11px;" class="text-muted">Wali: {{ $tagihan->siswa->parent_name }}</p>
                    @endif
                </td>
                <td width="45%" valign="top" class="text-right">
                    {{-- Pakai $header dari controller, bukan $tagihan->header —
                         menghindari N+1 query pada batch berisi ratusan invoice. --}}
                    <p style="margin: 0; font-size: 10px;" class="text-muted">GELOMBANG PENAGIHAN</p>
                    <p style="margin: 5px 0 2px 0;" class="font-bold">{{ $header->title }}</p>
                    <p style="margin: 0;">Periode: {{ $header->period }}</p>
                    <p style="margin: 0;">Tempo: {{ \Carbon\Carbon::parse($header->due_date)->translatedFormat('d F Y') }}</p>
                    <div style="margin-top: 8px;">
                        <span class="badge {{ $tagihan->status === 'Installment' ? 'badge-installment' : 'badge-unpaid' }}">
                            {{ $tagihan->status === 'Installment' ? 'DICICIL SEBAGIAN' : 'BELUM BAYAR' }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="table-items">
            <thead>
                <tr>
                    <th width="8%" class="text-center">No</th>
                    <th width="57%">Deskripsi Tagihan</th>
                    <th width="35%" class="text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihan->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->deskripsi }}</td>
                        <td class="text-right">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted" style="padding: 16px;">Tidak ada rincian item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="table-layout">
            <tr>
                <td width="55%" valign="top">
                    <div style="background-color: #faf9f5; border: 1px solid #e7e5e4; padding: 10px; border-radius: 8px; font-size: 10px;">
                        <p style="margin: 0 0 5px 0;" class="font-bold">Instruksi Pembayaran</p>
                        <ol style="margin: 0; padding-left: 15px;" class="text-muted">
                            <li>Harap melunasi sebelum tanggal jatuh tempo.</li>
                            <li>Abaikan tagihan ini bila pembayaran sudah dilakukan.</li>
                            <li>Simpan kwitansi sebagai bukti pembayaran yang sah.</li>
                        </ol>
                    </div>
                </td>
                <td width="5%"></td>
                <td width="40%" valign="top">
                    <table class="table-layout">
                        <tr>
                            <td style="padding-bottom: 5px;">Total Tagihan</td>
                            <td class="text-right" style="padding-bottom: 5px;">Rp {{ number_format($tagihan->total_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="padding-bottom: 5px;">Sudah Dibayar</td>
                            <td class="text-right text-muted" style="padding-bottom: 5px;">Rp {{ number_format($tagihan->total_amount - $tagihan->remaining_balance, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="border-top: 2px solid #d6d3d1; padding-top: 8px; font-size: 13px;"><strong>SISA PIUTANG</strong></td>
                            <td class="text-right text-red" style="border-top: 2px solid #d6d3d1; padding-top: 8px; font-size: 13px; font-weight: bold;">Rp {{ number_format($tagihan->remaining_balance, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>