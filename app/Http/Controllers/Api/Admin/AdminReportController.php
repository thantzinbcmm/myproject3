// app/Http/Controllers/Api/Admin/AdminReportController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');

        $baseQuery = fn() => Reservation::query()
            ->when(!$admin->isSuperAdmin(), fn($q) => $q->where('facility_id', $admin->facility_id));

        $todayCheckins = $baseQuery()->where('check_in_date', $today)->where('status', 'CONFIRMED')->count();
        $todayCheckouts = $baseQuery()->where('check_out_date', $today)->whereIn('status', ['CHECKIN', 'CHECKOUT'])->count();
        $currentGuests = $baseQuery()->where('status', 'CHECKIN')->count();
        $monthlyRevenue = $baseQuery()
            ->whereYear('check_in_date', now()->year)
            ->whereMonth('check_in_date', now()->month)
            ->whereNotIn('status', ['CANCELLED', 'NOSHOW'])
            ->sum('total_amount');
        $monthlyReservations = $baseQuery()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'todayCheckins' => $todayCheckins,
                'todayCheckouts' => $todayCheckouts,
                'currentGuests' => $currentGuests,
                'monthlyRevenue' => $monthlyRevenue,
                'monthlyReservations' => $monthlyReservations,
            ],
        ]);
    }

    public function revenueReport(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $admin = auth('admin')->user();

        $query = Reservation::query()
            ->whereNotIn('status', ['CANCELLED', 'NOSHOW'])
            ->whereYear('check_in_date', $request->year)
            ->when(!$admin->isSuperAdmin(), fn($q) => $q->where('facility_id', $admin->facility_id))
            ->when($request->month, fn($q) => $q->whereMonth('check_in_date', $request->month));

        $revenue = $query->select(
            DB::raw('YEAR(check_in_date) as year'),
            DB::raw('MONTH(check_in_date) as month'),
            DB::raw('COUNT(*) as reservation_count'),
            DB::raw('SUM(total_amount) as total_revenue'),
            'channel'
        )->groupBy('year', 'month', 'channel')->get();

        return response()->json(['success' => true, 'data' => $revenue]);
    }
}