<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\CalendarEvent;
use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

class DashboardDataSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        $owner = User::query()->firstWhere('phone', '081300000001');
        $operator = User::query()->firstWhere('phone', '081300000002');
        $finance = User::query()->firstWhere('phone', '081300000003');

        if (!$owner || !$operator || !$finance) {
            throw new RuntimeException('Data user inti belum tersedia. Jalankan RoleSeeder dan UserSeeder terlebih dahulu.');
        }

        $this->resetBusinessTables();

        $products = $this->seedProducts();
        $customers = $this->seedCustomers();

        $orders = $this->seedOrders($today, $products, $customers, $operator, $finance);
        $this->refreshCustomerStats();

        $transactions = $this->seedFinanceTransactions($today, $orders);
        $this->seedActivityLogs($today, $owner, $operator, $finance, $orders, $transactions);
        $this->seedCalendarEvents($today, $orders, $owner, $operator, $finance);
        $this->seedMonthlyReport($today, $owner);
    }

    private function resetBusinessTables(): void
    {
        OrderItem::query()->delete();
        Order::query()->delete();
        Report::query()->delete();
        ActivityLog::query()->delete();
        FinanceTransaction::query()->delete();
        CalendarEvent::query()->delete();
        Customer::query()->delete();
        Product::query()->delete();
    }

    private function seedProducts(): array
    {
        $rows = [
            ['code' => 'PRD-LPG3', 'name' => 'LPG 3 Kg', 'price' => 22000, 'stock' => 180],
            ['code' => 'PRD-AQUA19', 'name' => 'Aqua Galon 19L', 'price' => 23000, 'stock' => 140],
            ['code' => 'PRD-CLEO19', 'name' => 'Cleo Galon 19L', 'price' => 21000, 'stock' => 100],
            ['code' => 'PRD-SEGEL', 'name' => 'Segel Galon', 'price' => 2500, 'stock' => 600],
            ['code' => 'PRD-REG', 'name' => 'Regulator Gas', 'price' => 125000, 'stock' => 28],
            ['code' => 'PRD-SELANG', 'name' => 'Selang Gas', 'price' => 45000, 'stock' => 55],
        ];

        $products = [];
        foreach ($rows as $row) {
            $product = Product::query()->create($row);
            $products[$row['code']] = $product;
        }

        return $products;
    }

    private function seedCustomers(): array
    {
        $rows = [
            'warung_berkah' => ['name' => 'Warung Berkah', 'phone' => '081287001101', 'address' => 'Jl. Mawar No. 10, Cileungsi'],
            'toko_sumber' => ['name' => 'Toko Sumber Rejeki', 'phone' => '081287001102', 'address' => 'Jl. Kenanga Raya Blok B2, Cileungsi'],
            'laundry_bening' => ['name' => 'Laundry Bening', 'phone' => '081287001103', 'address' => 'Jl. Anggrek 3 No. 7, Cibubur'],
            'rm_melati' => ['name' => 'Rumah Makan Melati', 'phone' => '081287001104', 'address' => 'Jl. Raya Alternatif No. 88, Gunung Putri'],
            'cv_tirta' => ['name' => 'CV Tirta Sejahtera', 'phone' => '081287001105', 'address' => 'Kawasan Industri Cileungsi, Blok D5'],
        ];

        $customers = [];
        foreach ($rows as $key => $row) {
            $customers[$key] = Customer::query()->create([
                ...$row,
                'order_history_count' => 0,
                'total_spending' => 0,
            ]);
        }

        return $customers;
    }

    private function seedOrders(
        CarbonImmutable $today,
        array $products,
        array $customers,
        User $operator,
        User $finance,
    ): array {
        $handlers = [
            'operator' => $operator,
            'finance' => $finance,
        ];

        $plans = [
            [
                'month_offset' => -5,
                'day' => 4,
                'handler' => 'operator',
                'customer' => 'warung_berkah',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 30],
                    ['code' => 'PRD-SEGEL', 'qty' => 30],
                ],
            ],
            [
                'month_offset' => -5,
                'day' => 18,
                'handler' => 'finance',
                'customer' => 'laundry_bening',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-AQUA19', 'qty' => 24],
                ],
            ],
            [
                'month_offset' => -4,
                'day' => 6,
                'handler' => 'operator',
                'customer' => 'toko_sumber',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 40],
                    ['code' => 'PRD-REG', 'qty' => 2],
                ],
            ],
            [
                'month_offset' => -4,
                'day' => 21,
                'handler' => 'finance',
                'customer' => 'rm_melati',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-CLEO19', 'qty' => 18],
                    ['code' => 'PRD-AQUA19', 'qty' => 12],
                ],
            ],
            [
                'month_offset' => -3,
                'day' => 8,
                'handler' => 'operator',
                'customer' => 'cv_tirta',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 55],
                    ['code' => 'PRD-SEGEL', 'qty' => 55],
                ],
            ],
            [
                'month_offset' => -3,
                'day' => 24,
                'handler' => 'finance',
                'customer' => 'warung_berkah',
                'status' => 'tertolak',
                'items' => [
                    ['code' => 'PRD-AQUA19', 'qty' => 20],
                ],
            ],
            [
                'month_offset' => -2,
                'day' => 5,
                'handler' => 'operator',
                'customer' => 'laundry_bening',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-AQUA19', 'qty' => 30],
                    ['code' => 'PRD-CLEO19', 'qty' => 10],
                ],
            ],
            [
                'month_offset' => -2,
                'day' => 17,
                'handler' => 'finance',
                'customer' => 'toko_sumber',
                'status' => 'tertunda',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 25],
                    ['code' => 'PRD-SELANG', 'qty' => 4],
                ],
            ],
            [
                'month_offset' => -1,
                'day' => 4,
                'handler' => 'operator',
                'customer' => 'rm_melati',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 28],
                    ['code' => 'PRD-SEGEL', 'qty' => 28],
                ],
            ],
            [
                'month_offset' => -1,
                'day' => 19,
                'handler' => 'finance',
                'customer' => 'cv_tirta',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 42],
                    ['code' => 'PRD-REG', 'qty' => 2],
                ],
            ],
            [
                'month_offset' => 0,
                'day' => 3,
                'handler' => 'operator',
                'customer' => 'warung_berkah',
                'status' => 'terkirim',
                'items' => [
                    ['code' => 'PRD-AQUA19', 'qty' => 26],
                    ['code' => 'PRD-CLEO19', 'qty' => 12],
                ],
            ],
            [
                'month_offset' => 0,
                'day' => 10,
                'handler' => 'finance',
                'customer' => 'toko_sumber',
                'status' => 'tertunda',
                'items' => [
                    ['code' => 'PRD-LPG3', 'qty' => 32],
                    ['code' => 'PRD-SEGEL', 'qty' => 32],
                ],
            ],
        ];

        $createdOrders = [];

        foreach ($plans as $plan) {
            $customer = $customers[$plan['customer']];
            $handler = $handlers[$plan['handler']] ?? $operator;

            $nominal = 0;
            $items = [];

            foreach ($plan['items'] as $item) {
                $product = $products[$item['code']];
                $quantity = (int) $item['qty'];
                $unitPrice = (float) $product->price;
                $lineTotal = $quantity * $unitPrice;

                $nominal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $primaryProduct = $items[0]['product'];
            $productSummary = $primaryProduct->name;
            if (count($items) > 1) {
                $productSummary .= ' +'.(count($items) - 1).' item';
            }

            $order = Order::query()->create([
                'order_date' => $this->resolveDate($today, $plan['month_offset'], $plan['day']),
                'author_name' => $customer->name,
                'product_name' => $productSummary,
                'nominal' => $nominal,
                'status' => $plan['status'],
                'user_id' => $handler->id,
                'customer_id' => $customer->id,
                'product_id' => $primaryProduct->id,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $createdOrders[] = [
                'order' => $order,
                'customer' => $customer,
                'handler' => $handler,
            ];
        }

        return $createdOrders;
    }

    private function refreshCustomerStats(): void
    {
        Customer::query()->each(function (Customer $customer): void {
            $historyOrders = $customer->orders()->whereIn('status', ['terkirim', 'tertunda']);
            $realizedOrders = $customer->orders()->where('status', 'terkirim');

            $customer->update([
                'order_history_count' => $historyOrders->count(),
                'total_spending' => (float) $realizedOrders->sum('nominal'),
            ]);
        });
    }

    private function seedFinanceTransactions(CarbonImmutable $today, array $orders): array
    {
        $rows = [];

        foreach ($orders as $entry) {
            /** @var Order $order */
            $order = $entry['order'];
            /** @var Customer $customer */
            $customer = $entry['customer'];

            if ($order->status !== 'terkirim') {
                continue;
            }

            $rows[] = [
                'order_id' => $order->id,
                'transaction_date' => $order->order_date,
                'description' => 'Pembayaran order #ORD-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT).' - '.$customer->name,
                'category' => 'pemasukan',
                'amount' => (float) $order->nominal,
            ];
        }

        $expensePlans = [
            ['month_offset' => -5, 'day' => 2, 'description' => 'Logistik - Pembelian stok LPG dari agen utama', 'amount' => 2950000],
            ['month_offset' => -4, 'day' => 3, 'description' => 'Logistik - Pembelian stok galon dan segel', 'amount' => 3200000],
            ['month_offset' => -3, 'day' => 9, 'description' => 'Operasional - Perbaikan kendaraan distribusi', 'amount' => 680000],
            ['month_offset' => -3, 'day' => 26, 'description' => 'Gaji - Pembayaran gaji tim bulanan', 'amount' => 4500000],
            ['month_offset' => -2, 'day' => 6, 'description' => 'Operasional - Pembayaran sewa gudang', 'amount' => 1750000],
            ['month_offset' => -1, 'day' => 7, 'description' => 'Logistik - Pembelian tabung dan perlengkapan', 'amount' => 1100000],
            ['month_offset' => 0, 'day' => 5, 'description' => 'Operasional - Tagihan listrik dan air gudang', 'amount' => 780000],
            ['month_offset' => 0, 'day' => 12, 'description' => 'Logistik - Pembelian segel galon tambahan', 'amount' => 920000],
            ['month_offset' => 0, 'day' => 14, 'description' => 'Gaji - Insentif kurir pengantaran', 'amount' => 1250000],
        ];

        foreach ($expensePlans as $expense) {
            $rows[] = [
                'transaction_date' => $this->resolveDate($today, $expense['month_offset'], $expense['day']),
                'description' => $expense['description'],
                'category' => 'pengeluaran',
                'amount' => $expense['amount'],
            ];
        }

        $transactions = [];
        foreach ($rows as $row) {
            $transactions[] = FinanceTransaction::query()->create($row);
        }

        return $transactions;
    }

    private function seedActivityLogs(
        CarbonImmutable $today,
        User $owner,
        User $operator,
        User $finance,
        array $orders,
        array $transactions,
    ): void {
        $latestOrder = $orders ? $orders[count($orders) - 1]['order'] : null;
        $pendingOrder = $this->findFirstOrderByStatus(array_reverse($orders), 'tertunda');
        $latestTransaction = $transactions ? $transactions[count($transactions) - 1] : null;

        $rows = [
            [
                'logged_at' => $today->setTime(7, 45)->format('Y-m-d H:i:s'),
                'user_name' => $owner->name,
                'action' => 'Login',
                'module' => 'Dashboard',
                'status' => 'sukses',
                'metadata' => ['channel' => 'web'],
                'user_id' => $owner->id,
            ],
            [
                'logged_at' => $today->setTime(8, 20)->format('Y-m-d H:i:s'),
                'user_name' => $operator->name,
                'action' => 'Tambah Data',
                'module' => 'Pesanan',
                'status' => 'sukses',
                'metadata' => [
                    'order_id' => $latestOrder?->id,
                    'customer' => $latestOrder?->author_name,
                ],
                'user_id' => $operator->id,
            ],
            [
                'logged_at' => $today->setTime(9, 10)->format('Y-m-d H:i:s'),
                'user_name' => $finance->name,
                'action' => 'Tambah Data',
                'module' => 'Keuangan',
                'status' => 'sukses',
                'metadata' => [
                    'transaction_id' => $latestTransaction?->id,
                    'category' => $latestTransaction?->category,
                ],
                'user_id' => $finance->id,
            ],
            [
                'logged_at' => $today->setTime(10, 5)->format('Y-m-d H:i:s'),
                'user_name' => $operator->name,
                'action' => 'Edit Data',
                'module' => 'Pesanan',
                'status' => 'warning',
                'metadata' => [
                    'order_id' => $pendingOrder?->id,
                    'note' => 'Menunggu konfirmasi pembayaran pelanggan.',
                ],
                'user_id' => $operator->id,
            ],
            [
                'logged_at' => $today->setTime(11, 40)->format('Y-m-d H:i:s'),
                'user_name' => $finance->name,
                'action' => 'Export Laporan',
                'module' => 'Keuangan',
                'status' => 'sukses',
                'metadata' => ['format' => 'xlsx'],
                'user_id' => $finance->id,
            ],
            [
                'logged_at' => $today->setTime(13, 15)->format('Y-m-d H:i:s'),
                'user_name' => $owner->name,
                'action' => 'Edit Data',
                'module' => 'Karyawan',
                'status' => 'sukses',
                'metadata' => ['message' => 'Pembaruan jadwal shift mingguan.'],
                'user_id' => $owner->id,
            ],
        ];

        foreach ($rows as $row) {
            ActivityLog::query()->create($row);
        }
    }

    private function seedCalendarEvents(
        CarbonImmutable $today,
        array $orders,
        User $owner,
        User $operator,
        User $finance,
    ): void {
        $latestDeliveredOrder = $this->findFirstOrderByStatus(array_reverse($orders), 'terkirim');
        $pendingOrder = $this->findFirstOrderByStatus(array_reverse($orders), 'tertunda');

        $deliveryTitle = $latestDeliveredOrder
            ? 'Pengiriman order #'.$latestDeliveredOrder->id.' ke '.$latestDeliveredOrder->author_name
            : 'Pengiriman rutin pelanggan area kota';

        $followUpTitle = $pendingOrder
            ? 'Follow up pembayaran order #'.$pendingOrder->id
            : 'Follow up piutang pelanggan prioritas';

        $rows = [
            [
                'event_date' => $today->toDateString(),
                'event_time' => '08:30:00',
                'title' => 'Briefing operasional harian oleh '.$operator->name,
                'type' => 'Meeting',
                'location' => 'Gudang Utama',
                'status' => 'terjadwal',
            ],
            [
                'event_date' => $today->toDateString(),
                'event_time' => '13:00:00',
                'title' => $deliveryTitle,
                'type' => 'Pengiriman',
                'location' => 'Area pelanggan kota',
                'status' => 'berlangsung',
            ],
            [
                'event_date' => $today->addDay()->toDateString(),
                'event_time' => '09:30:00',
                'title' => $followUpTitle,
                'type' => 'Keuangan',
                'location' => 'Ruang Finance',
                'status' => 'terjadwal',
            ],
            [
                'event_date' => $today->addDays(2)->toDateString(),
                'event_time' => '15:00:00',
                'title' => 'Audit stok tabung LPG dan galon',
                'type' => 'Operasional',
                'location' => 'Zona Gudang B',
                'status' => 'terjadwal',
            ],
            [
                'event_date' => $today->addDays(4)->toDateString(),
                'event_time' => '10:00:00',
                'title' => 'Maintenance kendaraan distribusi',
                'type' => 'Maintenance',
                'location' => 'Workshop Mitra',
                'status' => 'terjadwal',
            ],
            [
                'event_date' => $today->addDays(6)->toDateString(),
                'event_time' => '11:00:00',
                'title' => 'Review laporan bulanan bersama '.$owner->name.' dan '.$finance->name,
                'type' => 'Meeting',
                'location' => 'Ruang Meeting',
                'status' => 'terjadwal',
            ],
        ];

        foreach ($rows as $row) {
            CalendarEvent::query()->create($row);
        }
    }

    private function seedMonthlyReport(CarbonImmutable $today, User $owner): void
    {
        $monthStart = $today->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $monthNumber = (int) $monthStart->format('n');
        $title = 'Laporan Bulanan '.($monthNames[$monthNumber] ?? $monthStart->format('F')).' '.$monthStart->format('Y');

        $totalRevenue = (float) FinanceTransaction::query()
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->where('category', 'pemasukan')
            ->sum('amount');

        $totalOrders = (int) Order::query()
            ->whereBetween('order_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('status', ['terkirim', 'tertunda'])
            ->count();

        $activeCustomers = (int) Customer::query()->where('order_history_count', '>', 0)->count();

        Report::query()->create([
            'report_month' => $monthStart->toDateString(),
            'title' => $title,
            'generated_by_user_id' => $owner->id,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'total_customers' => $activeCustomers,
            'notes' => 'Ringkasan otomatis: data berasal dari transaksi keuangan, pesanan, dan pelanggan aktif.',
        ]);
    }

    private function resolveDate(CarbonImmutable $today, int $monthOffset, int $day): string
    {
        $baseMonth = $today->startOfMonth()->addMonths($monthOffset);
        $safeDay = min(max($day, 1), $baseMonth->daysInMonth);

        return $baseMonth->addDays($safeDay - 1)->toDateString();
    }

    private function findFirstOrderByStatus(array $orders, string $status): ?Order
    {
        foreach ($orders as $entry) {
            if (($entry['order']->status ?? null) === $status) {
                return $entry['order'];
            }
        }

        return null;
    }
}
