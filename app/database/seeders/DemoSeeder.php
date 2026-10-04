<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Demo podaci sa slika iz dizajna, vezani za današnji datum.
 * PAŽNJA: briše sve postojeće dobavljače, fakture, uplate i kontakte.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Payment::query()->delete();
        Invoice::query()->delete();
        Contact::query()->delete();
        Supplier::query()->delete();

        $today = today();
        // [naziv, PIB, [[pre koliko dana izdata, iznos, uplaćeno ('f' = cela)]], kontakti]
        $spec = [
            ['Agrofina', '104512873', [[56, 26338.52, 'f'], [45, 53455.59, 'f'], [42, 27270.77, 'f'], [31, 69180.55, 'f'], [39, 46983.78, 'f'], [28, 28263.35, 'f'], [22, 45380.06, 8301.55], [10, 69991.22, 0], [3, 54798.86, 0]], [['Marko Petrović', 'Komercijalista', '064 123 4567']]],
            ['Bolero', '', [[40, 18450, 'f'], [25, 12300.4, 'f']], []],
            ['Karizma', '', [[30, 22100, 'f'], [5, 16288.18, 0]], [['Jelena Ilić', 'Komercijalista', '063 555 210']]],
            ['Mrvica', '', [[20, 9870.5, 'f'], [9, 7420, 'f']], []],
            ['Euro2001', '', [[35, 40210, 'f'], [2, 32438.36, 0]], []],
            ['Delt', '', [[18, 14980.2, 'f']], []],
            ['Nenad Jaja', '', [[33, 11200, 'f'], [20, 5443.2, 0]], [['Nenad', 'Vlasnik', '065 300 1122']]],
            ['Trikom', '', [[1, 5206.9, 0]], []],
            ['Foren', '', [[10, 29656.51, 0], [4, 28881.84, 0]], []],
            ['Marbo', '', [[26, 21540, 'f']], []],
        ];

        $no = 1000;
        foreach ($spec as [$name, $pib, $invoices, $contacts]) {
            $supplier = Supplier::create(['name' => $name, 'pib' => $pib ?: null, 'term_days' => 15]);
            foreach ($contacts as [$cName, $role, $phone]) {
                $supplier->contacts()->create(['name' => $cName, 'role' => $role, 'phone' => $phone]);
            }
            foreach ($invoices as [$ago, $amount, $paid]) {
                $no++;
                $issued = $today->copy()->subDays($ago);
                $invoice = $supplier->invoices()->create(['number' => "{$no}/26", 'issued_on' => $issued, 'term_days' => 15, 'amount' => $amount]);
                $paidAmount = $paid === 'f' ? $amount : $paid;
                if ($paidAmount > 0) {
                    $paidOn = $issued->copy()->addDays(12)->min($today);
                    $invoice->payments()->create(['paid_on' => $paidOn, 'amount' => $paidAmount]);
                }
            }
        }
    }
}
