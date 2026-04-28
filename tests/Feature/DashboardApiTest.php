<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_view_contains_backend_bootstrap_payload(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('window.bsaBootstrapData', false);
        $response->assertSee('window.bsaApiConfig', false);
    }

    public function test_product_crud_api_flow_works(): void
    {
        $create = $this->postJson('/api/products', [
            'code' => 'BRG-999',
            'name' => 'Produk Uji',
            'price' => 25000,
            'stock' => 8,
        ]);

        $create->assertCreated();
        $create->assertJsonPath('data.code', 'BRG-999');

        $id = $create->json('data.id');

        $this->putJson('/api/products/'.$id, [
            'code' => 'BRG-999',
            'name' => 'Produk Uji Update',
            'price' => 30000,
            'stock' => 11,
        ])->assertOk()->assertJsonPath('data.stock', 11);

        $this->deleteJson('/api/products/'.$id)->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $id]);
    }

    public function test_user_crud_api_flow_works(): void
    {
        $ownerRole = Role::query()->create(['name' => 'owner']);
        $karyawanRole = Role::query()->create(['name' => 'karyawan']);

        User::query()->create([
            'name' => 'Owner Utama',
            'phone' => '081300000010',
            'role_id' => $ownerRole->id,
            'password' => 'password123',
        ]);

        $create = $this->postJson('/api/users', [
            'name' => 'User Baru',
            'role' => 'karyawan',
            'phone' => '081300000011',
            'password' => 'Secret123',
        ]);

        $create->assertCreated();
        $create->assertJsonPath('data.role', 'karyawan');

        $id = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $id);

        $this->putJson('/api/users/'.$id, [
            'name' => 'User Baru Update',
            'role' => 'karyawan',
            'phone' => '081300000011',
        ])->assertOk()->assertJsonPath('data.role', 'karyawan');

        $this->assertDatabaseHas('users', [
            'id' => $id,
            'name' => 'User Baru Update',
            'role_id' => $karyawanRole->id,
        ]);

        $this->deleteJson('/api/users/'.$id)->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $id]);

        // Last owner guard should reject deletion when only one owner remains.
        $lastOwner = User::query()->where('role_id', $ownerRole->id)->firstOrFail();
        $this->deleteJson('/api/users/'.$lastOwner->id)->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $lastOwner->id]);
        $this->assertSame(1, Role::query()->where('name', 'karyawan')->count());
    }

    public function test_account_login_endpoint_verifies_password_per_user(): void
    {
        $ownerRole = Role::query()->create(['name' => 'owner']);

        $user = User::query()->create([
            'name' => 'Dapmon',
            'phone' => '081300000001',
            'role_id' => $ownerRole->id,
            'password' => 'Secret123',
        ]);

        $ok = $this->postJson('/api/session/login', [
            'account_id' => $user->id,
            'password' => 'Secret123',
        ]);

        $ok->assertOk();
        $ok->assertJsonPath('data.id', $user->id);
        $ok->assertJsonPath('data.name', 'Dapmon');
        $ok->assertJsonPath('data.role', 'Owner');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Login',
            'module' => 'Dashboard',
            'status' => 'sukses',
            'user_name' => 'Dapmon',
        ]);

        $wrong = $this->postJson('/api/session/login', [
            'account_id' => $user->id,
            'password' => 'Salah123',
        ]);

        $wrong->assertStatus(422);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Login',
            'module' => 'Dashboard',
            'status' => 'gagal',
            'user_name' => 'Dapmon',
        ]);
    }

    public function test_account_logout_endpoint_records_activity_log(): void
    {
        $response = $this->postJson('/api/session/logout', [
            'account_id' => 1,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Logout',
            'module' => 'Dashboard',
            'status' => 'sukses',
        ]);
    }

    public function test_stock_export_endpoint_returns_download_response(): void
    {
        $response = $this->get('/dashboard/export/stock');

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Export Laporan',
            'module' => 'Stok',
            'status' => 'sukses',
        ]);
    }

    public function test_stock_pdf_export_endpoint_returns_download_response(): void
    {
        $response = $this->get('/dashboard/export/stock?format=pdf');

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_export_falls_back_when_paper_size_config_is_invalid(): void
    {
        config()->set('export.paper.default', 'invalid-size');
        config()->set('export.paper.tables.stock', 'unknown-size');

        $pdf = $this->get('/dashboard/export/stock?format=pdf');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $xlsx = $this->get('/dashboard/export/stock?format=xlsx');
        $xlsx->assertOk();
        $xlsx->assertHeader('content-disposition');
    }

    public function test_import_template_endpoint_returns_download_response(): void
    {
        $response = $this->get('/dashboard/import-template/orders');

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Download Template',
            'module' => 'Pesanan',
            'status' => 'sukses',
        ]);
    }

    public function test_order_flow_can_create_new_customer_and_sync_metrics(): void
    {
        $create = $this->postJson('/api/orders', [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Baru',
            'product' => 'LPG',
            'nominal' => 125000,
            'status' => 'terkirim',
        ]);

        $create->assertCreated();
        $orderId = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $orderId);

        $customer = Customer::query()->where('name', 'Pelanggan Baru')->first();
        $this->assertNotNull($customer);
        $this->assertSame(1, (int) $customer->order_history_count);
        $this->assertSame(125000.0, (float) $customer->total_spending);

        $this->putJson('/api/orders/'.$orderId, [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Baru',
            'product' => 'Aqua Galon',
            'nominal' => 175000,
            'status' => 'terkirim',
        ])->assertOk();

        $customer->refresh();
        $this->assertSame(1, (int) $customer->order_history_count);
        $this->assertSame(175000.0, (float) $customer->total_spending);

        $this->deleteJson('/api/orders/'.$orderId)->assertOk();

        $customer->refresh();
        $this->assertSame(0, (int) $customer->order_history_count);
        $this->assertSame(0.0, (float) $customer->total_spending);
        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
    }

    public function test_order_store_records_actor_from_headers_when_auth_user_is_missing(): void
    {
        $ownerRole = Role::query()->create(['name' => 'owner']);

        $actor = User::query()->create([
            'name' => 'Dapmon',
            'phone' => '081300000001',
            'role_id' => $ownerRole->id,
            'password' => 'password123',
        ]);

        $create = $this->postJson('/api/orders', [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Header',
            'product' => 'Produk Header',
            'nominal' => 210000,
            'status' => 'terkirim',
        ], [
            'X-BSA-Actor-Phone' => '081300000001',
            'X-BSA-Actor-Name' => 'Dapmon',
            'X-BSA-Actor-Role' => 'Owner',
        ]);

        $create->assertCreated();
        $create->assertJsonPath('data.recorder', 'Dapmon');

        $orderId = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $orderId);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $actor->id,
        ]);
    }

    public function test_order_can_store_multiple_items_and_auto_calculate_total(): void
    {
        $create = $this->postJson('/api/orders', [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Multi Item',
            'product' => 'Ringkasan Akan Dihitung Ulang',
            'nominal' => 1,
            'status' => 'terkirim',
            'items' => [
                ['product' => 'LPG', 'quantity' => 2, 'unit_price' => 1000000],
                ['product' => 'Aqua Galon', 'quantity' => 3, 'unit_price' => 30000],
            ],
        ]);

        $create->assertCreated();

        $orderId = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $orderId);
        $this->assertSame(2090000.0, (float) $create->json('data.nominal'));

        $order = Order::query()->with('items')->find($orderId);
        $this->assertNotNull($order);
        $this->assertSame(2, $order->items->count());
        $this->assertSame(2090000.0, (float) $order->nominal);
        $this->assertStringContainsString('LPG x2', (string) $order->product_name);
        $this->assertStringContainsString('Aqua Galon x3', (string) $order->product_name);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_name' => 'LPG',
            'quantity' => 2,
        ]);

        $customer = Customer::query()->where('name', 'Pelanggan Multi Item')->first();
        $this->assertNotNull($customer);
        $this->assertSame(1, (int) $customer->order_history_count);
        $this->assertSame(2090000.0, (float) $customer->total_spending);
    }

    public function test_terkirim_order_decreases_stock_and_delete_restores_it(): void
    {
        $product = Product::query()->create([
            'code' => 'BRG-777',
            'name' => 'Produk Stok Tes',
            'price' => 12000,
            'stock' => 5,
        ]);

        $create = $this->postJson('/api/orders', [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Stok',
            'product' => 'Produk Stok Tes',
            'nominal' => 1,
            'status' => 'terkirim',
            'items' => [
                ['product' => 'Produk Stok Tes', 'quantity' => 3, 'unit_price' => 12000],
            ],
        ]);

        $create->assertCreated();
        $orderId = (int) $create->json('data.id');

        $product->refresh();
        $this->assertSame(2, (int) $product->stock);

        $this->deleteJson('/api/orders/'.$orderId)->assertOk();

        $product->refresh();
        $this->assertSame(5, (int) $product->stock);
    }

    public function test_order_rejects_when_stock_is_insufficient(): void
    {
        Product::query()->create([
            'code' => 'BRG-778',
            'name' => 'Produk Stok Tipis',
            'price' => 15000,
            'stock' => 1,
        ]);

        $create = $this->postJson('/api/orders', [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Minus',
            'product' => 'Produk Stok Tipis',
            'nominal' => 1,
            'status' => 'terkirim',
            'items' => [
                ['product' => 'Produk Stok Tipis', 'quantity' => 2, 'unit_price' => 15000],
            ],
        ]);

        $create->assertStatus(422);
        $create->assertJsonValidationErrors('items');
    }

    public function test_order_update_syncs_stock_on_status_and_quantity_changes(): void
    {
        $product = Product::query()->create([
            'code' => 'BRG-779',
            'name' => 'Produk Status Dinamis',
            'price' => 20000,
            'stock' => 10,
        ]);

        $create = $this->postJson('/api/orders', [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Dinamis',
            'product' => 'Produk Status Dinamis',
            'nominal' => 1,
            'status' => 'tertunda',
            'items' => [
                ['product' => 'Produk Status Dinamis', 'quantity' => 2, 'unit_price' => 20000],
            ],
        ]);

        $create->assertCreated();
        $orderId = (int) $create->json('data.id');

        $product->refresh();
        $this->assertSame(10, (int) $product->stock);

        $this->putJson('/api/orders/'.$orderId, [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Dinamis',
            'product' => 'Produk Status Dinamis',
            'nominal' => 1,
            'status' => 'terkirim',
            'items' => [
                ['product' => 'Produk Status Dinamis', 'quantity' => 2, 'unit_price' => 20000],
            ],
        ])->assertOk();

        $product->refresh();
        $this->assertSame(8, (int) $product->stock);

        $this->putJson('/api/orders/'.$orderId, [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Dinamis',
            'product' => 'Produk Status Dinamis',
            'nominal' => 1,
            'status' => 'terkirim',
            'items' => [
                ['product' => 'Produk Status Dinamis', 'quantity' => 5, 'unit_price' => 20000],
            ],
        ])->assertOk();

        $product->refresh();
        $this->assertSame(5, (int) $product->stock);

        $this->putJson('/api/orders/'.$orderId, [
            'date' => '2026-04-14',
            'author' => 'Pelanggan Dinamis',
            'product' => 'Produk Status Dinamis',
            'nominal' => 1,
            'status' => 'tertolak',
            'items' => [
                ['product' => 'Produk Status Dinamis', 'quantity' => 5, 'unit_price' => 20000],
            ],
        ])->assertOk();

        $product->refresh();
        $this->assertSame(10, (int) $product->stock);
    }

    public function test_stock_import_endpoint_can_create_and_update_data(): void
    {
        $existing = Product::query()->create([
            'code' => 'BRG-120',
            'name' => 'Produk Lama',
            'price' => 10000,
            'stock' => 2,
        ]);

        $csv = implode(PHP_EOL, [
            'ID,Kode Barang,Nama Barang,Harga,Stok',
            sprintf('%d,BRG-120,Produk Lama Diupdate,12500,9', $existing->id),
            ',BRG-121,Produk Baru,23000,15',
        ]);

        $file = UploadedFile::fake()->createWithContent('stok-import.csv', $csv);

        $response = $this->post('/api/import/stock', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonPath('imported', 2);
        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'code' => 'BRG-120',
            'name' => 'Produk Lama Diupdate',
            'stock' => 9,
        ]);
        $this->assertDatabaseHas('products', [
            'code' => 'BRG-121',
            'name' => 'Produk Baru',
            'stock' => 15,
        ]);
    }

    public function test_orders_import_endpoint_creates_order_and_updates_stock(): void
    {
        $product = Product::query()->create([
            'code' => 'BRG-130',
            'name' => 'Produk Import Excel',
            'price' => 20000,
            'stock' => 5,
        ]);

        $csv = implode(PHP_EOL, [
            'ID,Tanggal,Pelanggan,Produk,Nominal,Status,Quantity',
            ',2026-04-14,Pelanggan Import Excel,Produk Import Excel,60000,terkirim,3',
        ]);

        $file = UploadedFile::fake()->createWithContent('orders-import.csv', $csv);

        $response = $this->post('/api/import/orders', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonPath('imported', 1);

        $product->refresh();
        $this->assertSame(2, (int) $product->stock);

        $order = Order::query()->where('author_name', 'Pelanggan Import Excel')->first();
        $this->assertNotNull($order);
        $this->assertSame('terkirim', (string) $order->status);
        $this->assertSame(60000.0, (float) $order->nominal);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Produk Import Excel',
            'quantity' => 3,
        ]);

        $customer = Customer::query()->where('name', 'Pelanggan Import Excel')->first();
        $this->assertNotNull($customer);
        $this->assertSame(1, (int) $customer->order_history_count);
        $this->assertSame(60000.0, (float) $customer->total_spending);
    }
}
