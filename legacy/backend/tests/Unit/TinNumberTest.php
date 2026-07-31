<?php

namespace Tests\Unit;

use App\Rules\TinNumber;
use PHPUnit\Framework\TestCase;

class TinNumberTest extends TestCase
{
    private function fails(mixed $value): bool
    {
        $failed = false;
        (new TinNumber)->validate('tin_number', $value, function () use (&$failed) {
            $failed = true;

            // Mimic the translator's ->translate() fluency without booting Laravel.
            return new class
            {
                public function translate() {}
            };
        });

        return $failed;
    }

    public function test_accepts_exactly_nine_digits(): void
    {
        $this->assertFalse($this->fails('123456789'));
    }

    public function test_rejects_too_few_or_too_many_digits(): void
    {
        $this->assertTrue($this->fails('12345678'));
        $this->assertTrue($this->fails('1234567890'));
    }

    public function test_rejects_non_digit_characters(): void
    {
        $this->assertTrue($this->fails('12345678a'));
        $this->assertTrue($this->fails('123 45678'));
    }
}
