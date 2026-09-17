<?php

namespace App\Console\Commands;

use App\Services\Digiflazz\DigiflazzProductService;
use Illuminate\Console\Command;

class DigiflazzPrice extends Command
{
    protected $signature = 'digiflazz:price {--sync : Langsung sinkronisasikan dan update harga modal ke database} {--type=prepaid : Tipe pricelist (prepaid/pasca)}';

    protected $description = 'Mengambil daftar harga produk dari Digiflazz dan opsional sinkronisasi ke database';

    public function handle(DigiflazzProductService $productService): int
    {
        $type = (string) $this->option('type');
        $this->info("Mengambil daftar harga ({$type}) dari Digiflazz...");
        $this->newLine();

        $fetchResult = $productService->fetchPricelist($type);
        $products = $fetchResult['data'];

        $this->info($fetchResult['message']);
        $this->newLine();

        $rows = [];

        foreach (array_slice($products, 0, 50) as $product) {
            $rows[] = [
                $product['buyer_sku_code'] ?? ($product['sku'] ?? '-'),
                $product['product_name'] ?? ($product['name'] ?? '-'),
                $product['category'] ?? '-',
                $product['brand'] ?? '-',
                'Rp '.number_format($product['price'] ?? ($product['cost_price'] ?? 0), 0, ',', '.'),
                ($product['buyer_product_status'] ?? true) ? 'AKTIF' : 'NONAKTIF',
            ];
        }

        $this->table(
            [
                'SKU',
                'Produk',
                'Kategori',
                'Brand',
                'Harga Modal',
                'Status',
            ],
            $rows
        );

        if (count($products) > 50) {
            $this->comment('Menampilkan 50 dari total '.count($products).' produk. Gunakan --sync untuk mengupdate semua produk ke database.');
        }

        if ($this->option('sync')) {
            $this->newLine();
            $this->info('Melakukan sinkronisasi data ke database...');
            $syncRes = $productService->sync($type);
            $this->info($syncRes['message']);
        }

        return self::SUCCESS;
    }
}
