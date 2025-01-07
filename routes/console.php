<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Code lang ng code!!')->hourly();

// Without pruning, the telescope_entries table can accumulate records very quickly
Schedule::command('telescope:prune --hours=1')->hourly();
