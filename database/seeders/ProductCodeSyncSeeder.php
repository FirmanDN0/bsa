<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductCodeSyncSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()
            ->orderBy('id')
            ->get(['id', 'name', 'code']);

        $sequenceByBase = [];
        $usedCodes = [];

        foreach ($products as $product) {
            $base = $this->buildProductCodeBase((string) $product->name);
            $nextSequence = ($sequenceByBase[$base] ?? 0) + 1;
            $candidate = $nextSequence === 1
                ? $base
                : sprintf('%s-%d', $base, $nextSequence);

            while (isset($usedCodes[$candidate])) {
                $nextSequence++;
                $candidate = $nextSequence === 1
                    ? $base
                    : sprintf('%s-%d', $base, $nextSequence);
            }

            $sequenceByBase[$base] = $nextSequence;
            $usedCodes[$candidate] = true;

            if (strtoupper((string) $product->code) === $candidate) {
                continue;
            }

            Product::query()->whereKey($product->id)->update([
                'code' => $candidate,
            ]);
        }
    }

    private function buildProductCodeBase(string $productName): string
    {
        $normalized = strtoupper(trim($productName));
        $namePart = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?: 'BARANG';

        return substr('PRD-'.$namePart, 0, 45);
    }
}
