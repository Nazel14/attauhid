<?php

namespace App\Http\Controllers\Keuangan\Utils;

use App\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Bukti pembayaran disimpan di storage/app (tidak publik), jadi
     * penyajiannya harus lewat route yang mengecek izin lebih dulu.
     */
    public function show(Media $media): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('pembayaran.lihat'), 403);

        $path = $media->getPath();

        abort_unless(file_exists($path), 404, 'Berkas tidak ditemukan.');

        return response()->file($path);
    }
}