<?php

namespace App\Console\Commands;

use App\Services\Digiflazz\DigiflazzProductService;
use Exception;
use Illuminate\Console\Command;

class DigiflazzSyncCommand extends Command
{
    protected $signature = 'digiflazz:sync {--type=prepaid : Tipe produk (prepaid/pasca)}';

    protected $description = 'Sinkronisasi katalog, kategori, dan harga modal produk dari Digiflazz ke database';

    public function handle(DigiflazzProductService $productService): int
    {
        $this->info('Memulai sinkronisasi katalog & harga modal dari Digiflazz...');

        try {
            $type = (string) $this->option('type');
            $result = $productService->sync($type);

            $this->info($result['message']);
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Sumber Data', ($result['source'] ?? 'live_api') === 'live_api' ? 'API Real-Time' : 'Katalog Resmi Terverifikasi'],
                    ['Total Produk Terproses', $result['total_items'] ?? 0],
                    ['Produk Baru Dibuat', $result['created'] ?? 0],
                    ['Produk Diperbarui (Harga Realtime)', $result['updated'] ?? 0],
                    ['Produk Nonaktif', $result['deactivated'] ?? 0],
                    ['Error', $result['errors'] ?? 0],
                    ['Brand / Layanan Aktif', implode(', ', array_values($result['brands'] ?? []))],
                ]
            );

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Sinkronisasi gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
