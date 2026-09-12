<?php

namespace SierraTecnologia\Cashier\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SierraTecnologia\Card as SierraTecnologiaCard;
use SierraTecnologia\Cashier\Card;
use SierraTecnologia\Cashier\Tests\Fixtures\User;

class CardTest extends TestCase
{
    public function test_card_attributes_and_accessors()
    {
        $user = new User;
        $mockCard = SierraTecnologiaCard::constructFrom([
            'id' => 'card_12345',
            'brand' => 'Visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
        ]);

        $card = new Card($user, $mockCard);

        $this->assertEquals('Visa', $card->brand);
        $this->assertEquals('4242', $card->last4);
        $this->assertEquals(12, $card->exp_month);
        $this->assertEquals(2030, $card->exp_year);
        $this->assertSame($mockCard, $card->asSierraTecnologiaCard());
    }
}
