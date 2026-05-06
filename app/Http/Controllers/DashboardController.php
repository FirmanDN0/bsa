<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CalendarEvent;
use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardDataMapper;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // We only provide a small subset of data for the initial dashboard view.
        // Other data will be fetched via API when the user navigates to those sections.
        $bootstrapData = [
            'stock' => Product::query()->orderBy('id')->limit(10)->get()->map(fn (Product $row) => DashboardDataMapper::stock($row))->values(),
            'orders' => Order::query()->orderByDesc('id')->limit(10)->get()->map(fn (Order $row) => DashboardDataMapper::order($row))->values(),
            'users' => User::query()->with('role')->get()->map(fn (User $row) => DashboardDataMapper::employee($row))->values(),
            // Empty placeholders for other large tables to keep initial load light
            'customers' => [],
            'activity' => [],
            'calendarEvents' => [],
            'finance' => [],
        ];

        $summaryData = [
            'totalUsers' => User::query()->count(),
            'totalTransactions' => FinanceTransaction::query()->count(),
            'monthlyReports' => Report::query()
                ->whereYear('report_month', now()->year)
                ->whereMonth('report_month', now()->month)
                ->count(),
        ];

        $apiConfig = [
            'endpoints' => [
                'stock' => url('/api/products'),
                'orders' => url('/api/orders'),
                'customers' => url('/api/customers'),
                'activity' => url('/api/activity-logs'),
                'users' => url('/api/users'),
                'accountLogin' => url('/api/session/login'),
                'accountLogout' => url('/api/session/logout'),
                'finance' => url('/api/finance-transactions'),
                'calendarEvents' => url('/api/calendar-events'),
            ],
            'exportEndpoint' => url('/dashboard/export'),
            'importEndpoint' => url('/api/import'),
            'importTemplateEndpoint' => url('/dashboard/import-template'),
        ];

        return view('pages.dashboard', [
            'bootstrapData' => $bootstrapData,
            'summaryData' => $summaryData,
            'apiConfig' => $apiConfig,
        ]);
    }
}
