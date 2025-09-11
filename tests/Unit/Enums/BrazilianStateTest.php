<?php

namespace Tests\Unit\Enums;

use App\Enums\BrazilianState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BrazilianStateTest extends TestCase
{
    #[Test]
    public function it_should_have_27_states()
    {
        $this->assertCount(27, BrazilianState::cases());
    }

    #[Test]
    public function it_should_return_correct_state_name()
    {
        $this->assertEquals('São Paulo', BrazilianState::SAO_PAULO->getName());
        $this->assertEquals('Rio de Janeiro', BrazilianState::RIO_DE_JANEIRO->getName());
        $this->assertEquals('Minas Gerais', BrazilianState::MINAS_GERAIS->getName());
        $this->assertEquals('Bahia', BrazilianState::BAHIA->getName());
        $this->assertEquals('Distrito Federal', BrazilianState::DISTRITO_FEDERAL->getName());
    }

    #[Test]
    public function it_should_convert_to_array_correctly()
    {
        $states = BrazilianState::toArray();

        $this->assertIsArray($states);
        $this->assertCount(27, $states);

        $spState = array_filter($states, fn($state) => $state['uf'] === 'SP');
        $spState = reset($spState);

        $this->assertEquals('SP', $spState['uf']);
        $this->assertEquals('São Paulo', $spState['name']);
    }

    #[Test]
    public function it_should_convert_to_associative_array_correctly()
    {
        $states = BrazilianState::toAssociativeArray();

        $this->assertIsArray($states);
        $this->assertCount(27, $states);
        $this->assertArrayHasKey('SP', $states);
        $this->assertEquals('São Paulo', $states['SP']);
        $this->assertEquals('Rio de Janeiro', $states['RJ']);
    }

    #[Test]
    public function it_should_get_state_from_uf()
    {
        $sp = BrazilianState::tryFromUF('SP');
        $this->assertInstanceOf(BrazilianState::class, $sp);
        $this->assertEquals(BrazilianState::SAO_PAULO, $sp);

        $rj = BrazilianState::tryFromUF('rj');
        $this->assertInstanceOf(BrazilianState::class, $rj);
        $this->assertEquals(BrazilianState::RIO_DE_JANEIRO, $rj);

        $invalid = BrazilianState::tryFromUF('XX');
        $this->assertNull($invalid);
    }

    #[Test]
    public function it_should_handle_case_insensitive_uf()
    {
        $sp = BrazilianState::tryFromUF('sp');
        $this->assertInstanceOf(BrazilianState::class, $sp);
        $this->assertEquals(BrazilianState::SAO_PAULO, $sp);

        $df = BrazilianState::tryFromUF('df');
        $this->assertInstanceOf(BrazilianState::class, $df);
        $this->assertEquals(BrazilianState::DISTRITO_FEDERAL, $df);
    }
}
