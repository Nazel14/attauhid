<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $logs          = $query->paginate(20)->withQueryString()->onEachSide(0);
        $users         = User::orderBy('name')->get(['id', 'name', 'role']);
        $roles         = ['admin', 'guru', 'santri'];
        $modules       = $this->getModules();
        $activityTypes = $this->getActivityTypes();
        $stats         = $this->getStatistics($request);

        return view('admin.logs.index', compact(
            'logs', 'users', 'roles', 'modules', 'activityTypes', 'stats'
        ));
    }

    public function show(LogActivity $log)
    {
        $log->load('user');
        return view('admin.logs.show', compact('log'));
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
                ->with('info', 'Tidak ada log yang perlu dihapus untuk periode yang dipilih.');
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
        return [
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
        ];
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