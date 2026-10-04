<?php

namespace App\Console\Commands;

use App\Services\Ledger;
use App\Support\Fmt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendReminder extends Command
{
    protected $signature = 'dugovanja:podsetnik {--dana= : Koliko dana unapred (podrazumevano PODSETNIK_DANA_PRE)} {--suvo : Samo ispiši poruku, ne šalji}';

    protected $description = 'Šalje podsetnik (email i/ili Telegram) za fakture koje kasne ili uskoro dospevaju';

    public function handle(): int
    {
        $days = (int) ($this->option('dana') ?? config('dugovanja.podsetnik.dana_pre'));
        $ledger = new Ledger;
        $alerts = $ledger->alerts($days);

        if ($alerts->isEmpty()) {
            $this->info('Nema faktura koje kasne ili dospevaju — podsetnik nije poslat.');

            return self::SUCCESS;
        }

        $over = $alerts->where('status', 'kasni');
        $soon = $alerts->where('status', '!=', 'kasni');
        $lines = ['Dugovanja · '.Fmt::date($ledger->today), ''];
        if ($over->isNotEmpty()) {
            $lines[] = 'KASNI ('.Fmt::rsd($over->sum('rem')).'):';
            foreach ($over as $i) {
                $lines[] = "• {$i->supplier->name} — br. {$i->inv->number}, rok ".Fmt::date($i->due).' ('.Ledger::daysText($i).'): '.Fmt::rsd($i->rem);
            }
            $lines[] = '';
        }
        if ($soon->isNotEmpty()) {
            $lines[] = "DOSPEVA U NAREDNIH {$days} ".mb_strtoupper(Fmt::dan($days)).' ('.Fmt::rsd($soon->sum('rem')).'):';
            foreach ($soon as $i) {
                $lines[] = "• {$i->supplier->name} — br. {$i->inv->number}, rok ".Fmt::date($i->due).' ('.Ledger::daysText($i).'): '.Fmt::rsd($i->rem);
            }
            $lines[] = '';
        }
        $lines[] = config('app.url');
        $text = implode("\n", $lines);

        if ($this->option('suvo')) {
            foreach ($lines as $line) {
                $this->line($line);
            }

            return self::SUCCESS;
        }

        $cfg = config('dugovanja.podsetnik');
        $sent = 0;
        if ($cfg['email']) {
            $subject = 'Podsetnik: '.($over->isNotEmpty() ? Fmt::fakt($over->count()).' kasni, ' : '').Fmt::fakt($soon->count()).' dospeva';
            foreach (array_map('trim', explode(',', $cfg['email'])) as $to) {
                Mail::raw($text, fn ($m) => $m->to($to)->subject($subject));
            }
            $this->info("Email poslat: {$cfg['email']}");
            $sent++;
        }
        if ($cfg['telegram_token'] && $cfg['telegram_chat_id']) {
            Http::asForm()->post("https://api.telegram.org/bot{$cfg['telegram_token']}/sendMessage", [
                'chat_id' => $cfg['telegram_chat_id'],
                'text' => $text,
                'disable_web_page_preview' => 'true',
            ])->throw();
            $this->info('Telegram poruka poslata.');
            $sent++;
        }
        if (! $sent) {
            $this->warn('Nije podešen ni PODSETNIK_EMAIL ni Telegram — ništa nije poslato.');
        }

        return self::SUCCESS;
    }
}
