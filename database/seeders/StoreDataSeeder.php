<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class StoreDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Produk (Stok Barang)
        $products = [
            [
                'code' => 'PRD-ULTRAMILK',
                'name' => 'Ultra Milk 250ml',
                'price_buy' => 4800,
                'price_sell' => 6500,
                'price' => 6500,
                'stock' => 100,
            ],
            [
                'code' => 'PRD-INDOMIE-GORENG',
                'name' => 'Indomie Goreng 1 Dus',
                'price_buy' => 96000,
                'price_sell' => 115000,
                'price' => 115000,
                'stock' => 25,
            ],
            [
                'code' => 'PRD-AQUA-600',
                'name' => 'Aqua 600ml',
                'price_buy' => 3200,
                'price_sell' => 4500,
                'price' => 4500,
                'stock' => 48,
            ],
            [
                'code' => 'PRD-TELUR-AYAM',
                'name' => 'Telur Ayam 1kg',
                'price_buy' => 26000,
                'price_sell' => 32000,
                'price' => 32000,
                'stock' => 15,
            ],
            [
                'code' => 'PRD-BERAS-5KG',
                'name' => 'Beras Premium 5kg',
                'price_buy' => 62000,
                'price_sell' => 78000,
                'price' => 78000,
                'stock' => 20,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['code' => $product['code']], $product);
        }

        // 2. Seed Pelanggan
        $customers = [
            [
                'name' => 'Ahmad Permata',
                'phone' => '081234567801',
                'address' => 'Jl. Cempaka No. 12, Sidoarjo',
                'order_history_count' => 4,
                'total_spending' => 450000,
            ],
            [
                'name' => 'Ayu Nugroho',
                'phone' => '081234567802',
                'address' => 'Jl. Melati No. 45, Sidoarjo',
                'order_history_count' => 2,
                'total_spending' => 125000,
            ],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567803',
                'address' => 'Jl. Kenanga No. 8, Sidoarjo',
                'order_history_count' => 8,
                'total_spending' => 980000,
            ],
            [
                'name' => 'Siti Aminah',
                'phone' => '081234567804',
                'address' => 'Jl. Mawar No. 23, Sidoarjo',
                'order_history_count' => 0,
                'total_spending' => 0,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['phone' => $customer['phone']], $customer);
        }
    }
}
