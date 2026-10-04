<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Ledger;
use Carbon\Carbon;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DugovanjaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-04 10:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();
        $this->get('/')->assertRedirect('/prijava');
        $this->get('/prijava')->assertOk()->assertSee('Prijavi se');
    }

    public function test_due_date_is_issue_date_plus_term(): void
    {
        $s = Supplier::create(['name' => 'Agrofina', 'term_days' => 15]);
        $inv = $s->invoices()->create(['number' => '1', 'issued_on' => '2026-09-20', 'term_days' => 15, 'amount' => 100]);
        $this->assertSame('2026-10-05', $inv->due_on->format('Y-m-d'));
    }

    public function test_statuses_and_partial_payments(): void
    {
        $s = Supplier::create(['name' => 'Test', 'term_days' => 15]);
        $late = $s->invoices()->create(['number' => 'A', 'issued_on' => '2026-09-01', 'term_days' => 15, 'amount' => 1000]);
        $late->payments()->create(['paid_on' => '2026-09-10', 'amount' => 400.25]);
        $s->invoices()->create(['number' => 'B', 'issued_on' => '2026-09-26', 'term_days' => 15, 'amount' => 500]); // rok 11.10. = 7 dana
        $s->invoices()->create(['number' => 'C', 'issued_on' => '2026-09-27', 'term_days' => 15, 'amount' => 300]); // rok 12.10. = 8 dana
        $paid = $s->invoices()->create(['number' => 'D', 'issued_on' => '2026-09-01', 'term_days' => 15, 'amount' => 200]);
        $paid->payments()->create(['paid_on' => '2026-09-05', 'amount' => 200]);

        $ledger = new Ledger;
        $by = $ledger->invoices->keyBy(fn ($i) => $i->inv->number);
        $this->assertSame('kasni', $by['A']->status);
        $this->assertSame(59975, $by['A']->rem);
        $this->assertSame(-18, $by['A']->d);
        $this->assertSame('uskoro', $by['B']->status);
        $this->assertSame('otvoreno', $by['C']->status);
        $this->assertSame('placeno', $by['D']->status);

        $t = $ledger->totals();
        $this->assertSame(59975 + 50000 + 30000, $t['total']);
        $this->assertSame(59975, $t['over']);
        $this->assertSame(50000, $t['soon']);
        $this->assertSame('kasni', $ledger->find($s->id)->status);
        $this->assertCount(2, $ledger->alerts());
    }

    public function test_demo_data_matches_design_totals(): void
    {
        (new DemoSeeder)->run();
        $t = (new Ledger)->totals();
        $this->assertSame(27978358, $t['total']);
        $this->assertSame(6, $t['debtSuppliers']);

        $this->get('/')->assertOk()->assertSee('279.783,58')->assertSee('Za plaćanje');
        $agrofina = Supplier::where('name', 'Agrofina')->first();
        $this->get("/dobavljaci/{$agrofina->id}")->assertOk()->assertSee('161.868,59')->assertSee('Marko Petrović');
    }

    public function test_create_supplier_invoice_payment_contact(): void
    {
        $this->post('/dobavljaci', ['_modal' => 'supplier', 'name' => ' Bolero ', 'pib' => '123', 'term_days' => 30])
            ->assertRedirect();
        $s = Supplier::firstWhere('name', 'Bolero');
        $this->assertSame(30, $s->term_days);

        $this->from("/dobavljaci/{$s->id}")->post('/fakture', ['_modal' => 'invoice', 'supplier_id' => $s->id, 'number' => '77/26', 'issued_on' => '2026-10-01', 'amount' => '274.340,38', 'term_days' => 30])
            ->assertRedirect("/dobavljaci/{$s->id}");
        $inv = Invoice::firstWhere('number', '77/26');
        $this->assertSame('274340.38', $inv->amount);
        $this->assertSame('2026-10-31', $inv->due_on->format('Y-m-d'));

        $this->from("/dobavljaci/{$s->id}")->post("/fakture/{$inv->id}/uplate", ['_modal' => 'payment', 'paid_on' => '2026-10-04', 'amount' => '100.000,00'])
            ->assertRedirect();
        $this->assertSame(17434038, (new Ledger)->invoices->first()->rem);

        $this->post("/dobavljaci/{$s->id}/kontakti", ['_modal' => 'contact', 'name' => 'Ana', 'phone' => '064 1'])->assertRedirect();
        $this->assertSame('Ana', $s->contacts()->first()->name);
    }

    public function test_invalid_amount_reopens_modal_with_error(): void
    {
        $s = Supplier::create(['name' => 'X', 'term_days' => 15]);
        $this->from('/')->post('/fakture', ['_modal' => 'invoice', 'supplier_id' => $s->id, 'issued_on' => '2026-10-01', 'amount' => 'abc'])
            ->assertRedirect('/')->assertSessionHasErrors(['amount' => 'Unesite ispravan iznos.'])->assertSessionHasInput('_modal', 'invoice');
        $this->assertSame(0, Invoice::count());
    }

    public function test_deleting_supplier_cascades(): void
    {
        $s = Supplier::create(['name' => 'X', 'term_days' => 15]);
        $inv = $s->invoices()->create(['number' => 'A', 'issued_on' => '2026-09-01', 'term_days' => 15, 'amount' => 10]);
        $inv->payments()->create(['paid_on' => '2026-09-02', 'amount' => 5]);
        $this->delete("/dobavljaci/{$s->id}")->assertRedirect('/');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_demo_reset_is_disabled_by_default(): void
    {
        config(['dugovanja.demo' => false]);
        $this->post('/demo')->assertNotFound();
    }

    public function test_reminder_lists_overdue_and_soon(): void
    {
        $s = Supplier::create(['name' => 'Karizma', 'term_days' => 15]);
        $s->invoices()->create(['number' => 'K1', 'issued_on' => '2026-09-01', 'term_days' => 15, 'amount' => 1000]);
        $s->invoices()->create(['number' => 'K2', 'issued_on' => '2026-09-21', 'term_days' => 15, 'amount' => 2000]); // rok 06.10.
        $s->invoices()->create(['number' => 'K3', 'issued_on' => '2026-10-01', 'term_days' => 15, 'amount' => 3000]); // rok 16.10.

        $this->artisan('dugovanja:podsetnik', ['--dana' => 3, '--suvo' => true])
            ->expectsOutputToContain('K1')
            ->expectsOutputToContain('K2')
            ->doesntExpectOutputToContain('K3')
            ->assertSuccessful();
    }
}
