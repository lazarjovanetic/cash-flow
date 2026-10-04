<?php

use Illuminate\Support\Facades\Schedule;

// cPanel Cron Jobs pokreće "php artisan schedule:run" svakog minuta; ovo šalje podsetnik jednom dnevno.
Schedule::command('dugovanja:podsetnik')
    ->dailyAt(config('dugovanja.podsetnik.vreme', '07:00'))
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
