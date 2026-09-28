<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LogActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = LogActivity::with('user')->latest();

        $tab = $request->input('tab', 'all');

        match ($tab) {
            'auth'   => $query->whereIn('activity_type', ['login', 'logout']),
            'crud'   => $query->whereIn('activity_type', ['create', 'update', 'delete']),
            'admin'  => $query->where('role', 'admin'),
            'guru'   => $query->where('role', 'guru'),
            'santri' => $query->where('role', 'santri'),
            default  => null,
        };

        if ($request->filled('role') && $tab === 'all') {
            $query->byRole($request->role);
        }
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }
        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $logs = $query->paginate(20)->withQueryString()->onEachSide(0);

        // ✅ Serialize setiap item log jadi array plain agar field yang dipakai
        // di frontend (formatted_activity_type, formatted_module, dsb) selalu
        // ikut terkirim, terlepas dari $appends di model.
        $logs->getCollection()->transform(function (LogActivity $log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'role' => $log->role,
                'activity_type' => $log->activity_type,
                'formatted_activity_type' => $log->formatted_activity_type,
                'module' => $log->module,
                'formatted_module' => $log->formatted_module,
                'description' => $log->description,
                'created_at_full' => optional($log->created_at)->format('d/m/Y H:i'),
                'created_at_short' => optional($log->created_at)->format('d/m H:i'),
            ];
        });

        $users         = User::orderBy('name')->get(['id', 'name', 'role']);
        // Dibaca dari tabel users, bukan ditulis tetap — database sudah punya
        // sembilan role, dan daftar tetap akan ketinggalan lagi begitu
        // bertambah satu.
        $roles         = User::query()
            ->select('role')->distinct()->orderBy('role')
            ->pluck('role')->filter()->values();
        $modules       = $this->getModules();
        $activityTypes = $this->getActivityTypes();
        $stats         = $this->getStatistics($request);

        // ✅ FIXED: sebelumnya view('admin.logs.index', ...) padahal frontend-nya
        // React/Inertia. Ini menyebabkan halaman gagal merender komponen React.
        return Inertia::render('Admin/Logs/Index', [
            'logs'          => $logs,
            'users'         => $users,
            'roles'         => $roles,
            'modules'       => $modules,
            'activityTypes' => $activityTypes,
            'stats'         => $stats,
            'filters'       => $request->only([
                'tab', 'role', 'module', 'activity_type', 'user_id', 'date_from', 'date_to', 'search',
            ]),
            'canManage' => auth()->user()->hasPermissionTo('kelola-admin'),
        ]);
    }

    public function show(LogActivity $log)
    {
        $log->load('user');

        // ✅ FIXED: view() -> Inertia::render(), data di-flatten jadi array plain.
        return Inertia::render('Admin/Logs/Show', [
            'log' => [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'role' => $log->role,
                'activity_type' => $log->activity_type,
                'formatted_activity_type' => $log->formatted_activity_type,
                'module' => $log->module,
                'formatted_module' => $log->formatted_module,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'model_type' => $log->model_type,
                'model_type_short' => $log->model_type ? class_basename($log->model_type) : null,
                'model_id' => $log->model_id,
                'created_at_date' => optional($log->created_at)->format('d F Y'),
                'created_at_time' => optional($log->created_at)->format('H:i:s'),
                'created_at_human' => optional($log->created_at)->diffForHumans(),
            ],
            'canManage' => auth()->user()->hasPermissionTo('kelola-admin'),
        ]);
    }

    public function cleanup(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('kelola-admin')) {
            return redirect()->route('admin.logs.index')
                ->with('error', 'Hanya Super Admin yang dapat menghapus log activity!');
        }

        $request->validate(['days' => 'required|integer|min:1']);

        $days       = $request->days;
        $cutoffDate = now()->subDays($days);
        $count      = LogActivity::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            return redirect()->route('admin.logs.index')
                ->with('success', 'Tidak ada log yang perlu dihapus untuk periode yang dipilih.');
        }

        LogActivity::where('created_at', '<', $cutoffDate)->delete();

        return redirect()->route('admin.logs.index')
            ->with('success', "Berhasil menghapus {$count} log activity yang lebih dari {$days} hari.");
    }

    public function destroy(LogActivity $log)
    {
        if (!auth()->user()->hasPermissionTo('kelola-admin')) {
            return redirect()->route('admin.logs.index')
                ->with('error', 'Hanya Super Admin yang dapat menghapus log activity!');
        }

        try {
            $log->delete();
            return redirect()->route('admin.logs.index')
                ->with('success', 'Log activity berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus log activity: ' . $e->getMessage());
        }
    }

    private function getStatistics($request)
    {
        $query = LogActivity::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        } else {
            $query->recent(30);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // by_role pakai query tanpa batasan recent(30) agar
        // angka card konsisten dengan jumlah nyata per role
        $byRoleQuery = LogActivity::query();
        if ($request->filled('date_from')) {
            $byRoleQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $byRoleQuery->whereDate('created_at', '<=', $request->date_to);
        }

        return [
            'total'       => $query->count(),
            'today'       => LogActivity::today()->count(),
            'by_role'     => $byRoleQuery
                                ->select('role', DB::raw('count(*) as total'))
                                ->groupBy('role')
                                ->pluck('total', 'role')
                                ->toArray(),
            'by_activity' => $query->clone()
                                ->select('activity_type', DB::raw('count(*) as total'))
                                ->groupBy('activity_type')
                                ->orderByDesc('total')
                                ->limit(5)
                                ->pluck('total', 'activity_type')
                                ->toArray(),
            'by_module'   => $query->clone()
                                ->select('module', DB::raw('count(*) as total'))
                                ->groupBy('module')
                                ->orderByDesc('total')
                                ->limit(5)
                                ->pluck('total', 'module')
                                ->toArray(),
        ];
    }

    private function getModules()
    {
        // Label yang sudah dirapikan manual, dipakai lebih dulu.
        $label = [
            'auth'          => 'Autentikasi',
            'santri'        => 'Santri',
            'guru'          => 'Guru',
            'kelas'         => 'Kelas',
            'presensi'      => 'Presensi',
            'setoran'       => 'Setoran',
            'rapot'         => 'Rapot',
            'sesi-presensi' => 'Sesi Presensi',
            'monitoring'    => 'Monitoring',
            'log-activity'  => 'Log Activity',

            // Modul keuangan. Kuncinya harus sama persis dengan hasil
            // Str::kebab(class_basename($model)) di AuditObserver.
            'pembayaran'     => 'Pembayaran',
            'tagihan'        => 'Tagihan',
            'invoice-header' => 'Tagihan (Induk)',
            'item-tagihan'   => 'Item Tagihan',
            'pengeluaran'    => 'Pengeluaran',
            'payroll-header' => 'Penggajian',
            'mutasi-kas'     => 'Mutasi Kas',
            'rekening'       => 'Rekening',
            'rkas-header'    => 'RKAS',
            'rkas-detail'    => 'Rincian RKAS',
            'jabatan'        => 'Jabatan',
            'tunjangan'      => 'Tunjangan',
            'potongan'       => 'Potongan',
            'tarif'          => 'Tarif',
        ];

        // Modul yang benar-benar ada di tabel tapi belum punya label ikut
        // ditambahkan sendiri, supaya daftar ini tidak pernah lagi tertinggal
        // dari kenyataan seperti yang terjadi pada modul keuangan.
        foreach (LogActivity::query()->select('module')->distinct()->pluck('module')->filter() as $modul) {
            $label[$modul] ??= ucfirst($modul);
        }

        return $label;
    }

    private function getActivityTypes()
    {
        return [
            'login'    => 'Login',
            'logout'   => 'Logout',
            'create'   => 'Tambah Data',
            'update'   => 'Edit Data',
            'delete'   => 'Hapus Data',
            'download' => 'Download',
            'view'     => 'Lihat Data',
            'import'   => 'Import Data',
            'submit'   => 'Submit Data',
            'approve'  => 'Approve',
            'reject'   => 'Reject',
        ];
    }
}