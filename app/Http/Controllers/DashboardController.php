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
        $bootstrapData = [
            'stock' => Product::query()->orderBy('id')->get()->map(fn (Product $row) => DashboardDataMapper::stock($row))->values(),
            'orders' => Order::query()->with(['items', 'user'])->orderByDesc('id')->get()->map(fn (Order $row) => DashboardDataMapper::order($row))->values(),
            'customers' => Customer::query()->orderBy('name')->get()->map(fn (Customer $row) => DashboardDataMapper::customer($row))->values(),
            'activity' => ActivityLog::query()->orderByDesc('logged_at')->get()->map(fn (ActivityLog $row) => DashboardDataMapper::activity($row))->values(),
            'users' => User::query()->with('role')->orderBy('name')->get()->map(fn (User $row) => DashboardDataMapper::employee($row))->values(),
            'calendarEvents' => CalendarEvent::query()->orderBy('event_date')->orderBy('event_time')->get()->map(fn (CalendarEvent $row) => DashboardDataMapper::calendarEvent($row))->values(),
            'finance' => FinanceTransaction::query()->orderByDesc('transaction_date')->orderByDesc('id')->get()->map(fn (FinanceTransaction $row) => DashboardDataMapper::finance($row))->values(),
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
