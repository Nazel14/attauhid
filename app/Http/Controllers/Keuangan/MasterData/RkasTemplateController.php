<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\RkasTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RkasTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Keuangan/MasterData/DataRkasTemplate', [
            'templates' => $this->getFlattenedTree(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_judul' => 'required|string|max:255',
            'parent_id'  => 'nullable|exists:rkas_templates,id',
        ]);

        if ($request->parent_id) {
            $parent     = RkasTemplate::findOrFail($request->parent_id);
            $kodeJudul  = $parent->kode_judul . '.' . ($parent->children()->count() + 1);
            $level      = $parent->level + 1;
        } else {
            $kodeJudul  = (string) (RkasTemplate::whereNull('parent_id')->count() + 1);
            $level      = 1;
        }

        RkasTemplate::create([
            'parent_id'  => $request->parent_id,
            'kode_judul' => $kodeJudul,
            'nama_judul' => $request->nama_judul,
            'level'      => $level,
        ]);

        return redirect()->back()->with('success', 'Struktur RKAS berhasil ditambahkan.');
    }

    public function update(Request $request, RkasTemplate $template)
    {
        $request->validate([
            'nama_judul' => 'required|string|max:255',
        ]);

        $template->update(['nama_judul' => $request->nama_judul]);

        return redirect()->back()->with('success', 'Nama judul berhasil diperbarui.');
    }

    public function destroy(RkasTemplate $template)
    {
        // Model ini pakai SoftDeletes, sehingga ON DELETE CASCADE di database
        // TIDAK ikut terpicu — versi asli meninggalkan sub-judul jadi yatim.
        // Karena itu anak-anaknya dihapus manual secara rekursif.
        DB::transaction(function () use ($template) {
            $this->hapusRekursif($template);
        });

        return redirect()->back()->with('success', 'Judul dan seluruh sub-judul di bawahnya berhasil dihapus.');
    }

    private function hapusRekursif(RkasTemplate $node): void
    {
        foreach ($node->children as $anak) {
            $this->hapusRekursif($anak);
        }

        $node->delete();
    }

    /**
     * Ratakan pohon hierarki jadi satu koleksi berurutan induk → anak,
     * supaya mudah dirender sebagai tabel di React.
     */
    private function getFlattenedTree($parentId = null)
    {
        $nodes = RkasTemplate::where('parent_id', $parentId)->orderBy('id')->get();
        $tree  = collect();

        foreach ($nodes as $node) {
            $tree->push($node);
            $tree = $tree->merge($this->getFlattenedTree($node->id));
        }

        return $tree;
    }
}