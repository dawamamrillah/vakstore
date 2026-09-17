<?php

namespace App\Console\Commands;

use App\Services\Digiflazz\DigiflazzClient;
use Illuminate\Console\Command;

class TestDigiflazz extends Command
{
    protected $signature = 'digiflazz:test';

    protected $description = 'Test koneksi ke Digiflazz Development API';

    public function handle(DigiflazzClient $client): int
    {
        $this->info('Menguji koneksi ke Digiflazz API...');
        $this->info('Username: '.$client->getUsername());
        $this->info('Base URL: '.$client->getBaseUrl());
        $this->newLine();

        $result = $client->checkBalance();

        $this->info('HTTP Status: '.$result['status_code']);
        $this->newLine();

        $this->info('Response Digiflazz:');
        $this->line(json_encode($result['raw_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
