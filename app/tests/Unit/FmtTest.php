<?php

namespace Tests\Unit;

use App\Support\Fmt;
use PHPUnit\Framework\TestCase;

class FmtTest extends TestCase
{
    public function test_formats_serbian_amounts(): void
    {
        $this->assertSame('274.340,38', Fmt::num(27434038));
        $this->assertSame('0,05', Fmt::num(5));
        $this->assertSame('1.000.000,00 RSD', Fmt::rsd(100000000));
        $this->assertSame('−12,50', Fmt::num(-1250));
    }

    public function test_parses_amounts_in_both_formats(): void
    {
        $this->assertSame(27434038, Fmt::parseAmount('274.340,38'));
        $this->assertSame(27434038, Fmt::parseAmount('274340.38'));
        $this->assertSame(27434038, Fmt::parseAmount('274 340,38 RSD'));
        $this->assertSame(150000, Fmt::parseAmount('1500'));
        $this->assertNull(Fmt::parseAmount('abc'));
        $this->assertNull(Fmt::parseAmount(''));
    }

    public function test_short_amounts_and_plurals(): void
    {
        $this->assertSame('42,5k', Fmt::short(4252171));
        $this->assertSame('162k', Fmt::short(16186859));
        $this->assertSame('', Fmt::short(0));
        $this->assertSame('1 faktura', Fmt::fakt(1));
        $this->assertSame('3 fakture', Fmt::fakt(3));
        $this->assertSame('12 faktura', Fmt::fakt(12));
        $this->assertSame('22 fakture', Fmt::fakt(22));
        $this->assertSame('dan', Fmt::dan(21));
        $this->assertSame('dana', Fmt::dan(11));
        $this->assertSame('NJ', Fmt::initials('Nenad Jaja'));
    }
}
