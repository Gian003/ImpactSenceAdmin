<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Incident;
use App\Models\InvestigationOfficer;
use App\Models\PatrolUnit;
use App\Models\TocPersonnel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_incidents'  => Incident::count(),
            'pending'          => Incident::where('status', 'pending')->count(),
            'resolved'         => Incident::where('status', 'resolved')->count(),
            'total_riders'     => User::where('role', 'rider')->count(),
            'active_devices'   => Device::where('is_active', true)->count(),
            'patrol_units'     => PatrolUnit::count(),
            'toc_officers'     => TocPersonnel::count(),
            'inv_officers'     => InvestigationOfficer::count(),
        ];

        $recentIncidents = Incident::with('rider', 'patrolUnit')
            ->latest()->limit(8)->get();

        $byMonth = Incident::select(
                DB::raw("DATE_FORMAT(created_at,'%b') as month"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month', DB::raw("DATE_FORMAT(created_at,'%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"))
            ->get();

        return view('admin.dashboard.index', compact('stats', 'recentIncidents', 'byMonth'));
    }

    // Returns a 7-element array (oldest → today) of daily counts for the given query.
    private function trend($query, string $dateCol = 'created_at', int $days = 7): array
    {
        $raw = $query
            ->selectRaw("DATE($dateCol) as day, COUNT(*) as total")
            ->where($dateCol, '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $result[] = (int) ($raw[now()->subDays($i)->format('Y-m-d')] ?? 0);
        }
        return $result;
    }
}
