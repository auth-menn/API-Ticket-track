<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStatistics(Request $request)
    {
        $currentMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfMonth = Carbon::now()->subMonth()->endOfMonth();


        $totalTickets = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth]) ->count();

         $activeTickets = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->where('status', '!=', 'resolved')
            ->count();
            
        $resolvedTickets = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->where('status', 'resolved')
            ->count();

        $avgResolutionTime = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->where('status', 'resolved')
            ->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time'))
            ->value('avg_time') ?? 0;

        $statusDistribution = [
            'open' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'open')->count(),
            'onprogress' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'onprogress')->count(),
            'resolved' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'resolved')->count(),
            'rejected' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'rejected')->count(),
        ];

       

        $dashboardData = [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'resolved_tickets' => $resolvedTickets,
            'avg_resolution_time' => round($avgResolutionTime, 1),
            'status_distribution' => $statusDistribution,
        ];


        return response()->json([
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => DashboardResource::make((object) $dashboardData)
        ], 200);


        

           

    }
}
