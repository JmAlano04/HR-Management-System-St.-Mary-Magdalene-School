<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ZktecoAutoSync extends Command
{
    protected $signature = 'zkteco:auto-sync';

    protected $description = 'Sync ZKTeco attendance every 10 seconds';

    public function handle()
    {
        while (true) {  
            $this->call('zkteco:sync');

            sleep(10);
        }
    }
}
