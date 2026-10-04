<?php

namespace App\Http\Controllers;

use App\Services\Ledger;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('home', ['ledger' => new Ledger]);
    }

    public function resetDemo()
    {
        abort_unless(config('dugovanja.demo'), 404);

        DB::transaction(fn () => app(DemoSeeder::class)->run());

        return redirect()->route('home');
    }
}
