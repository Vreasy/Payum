<?php
namespace Payum\Core\Tests\Request;

use Payum\Core\Request\CreateTokenForCreditCard;
use Payum\Core\Request\Generic;
use Payum\Core\Model\CreditCard;

class CreateTokenForCreditCardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfGeneric()
    {
        $rc = new \ReflectionClass(CreateTokenForCreditCard::class);

        $this->assertTrue($rc->isSubclassOf(Generic::class));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithCardModel()
    {
        $card = new CreditCard();

        $request = new CreateTokenForCreditCard($card);

        $this->assertSame($card, $request->getCard());
    }

    /**
     * @test
     */
    public function shouldSetExpectedDefaultValuesInConstructor()
    {
        $card = new CreditCard();

        $request = new CreateTokenForCreditCard($card);

        $this->assertSame(null, $request->getToken());
    }

    /**
     * @test
     */
    public function shouldAllowSetAndLaterGetToken()
    {
        $card = new CreditCard();

        $request = new CreateTokenForCreditCard($card);
        $request->setToken('aToken');

        $this->assertEquals('aToken', $request->getToken());
    }
}
