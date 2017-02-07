<?php
namespace Payum\Stripe\Tests\Action;

use Payum\Core\Model\CreditCard;
use Payum\Core\Model\CreditCardInterface;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\CreateTokenForCreditCard;
use Payum\Core\Request\Generic;
use Payum\Core\Tests\GenericActionTest;
use Payum\Stripe\Action\CreateTokenForCreditCardAction;
use Payum\Stripe\Request\Api\CreateToken;

class CreateTokenForCreditCardActionTest extends GenericActionTest
{
    protected $requestClass = CreateTokenForCreditCard::class;

    protected $actionClass = CreateTokenForCreditCardAction::class;

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(new CreditCard())),
            array(new $this->requestClass($this->getMock(CreditCardInterface::class))),
        );
    }

    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array($this->getMockForAbstractClass(Generic::class, array(array()))),
            array(new $this->requestClass(new \stdClass())),
        );
    }

    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass(CreateTokenForCreditCardAction::class);

        $this->assertTrue($rc->isSubclassOf(GatewayAwareAction::class));
    }

    /**
     * @test
     */
    public function shouldSubExecuteCreateTokenWithCardData()
    {
        $card = new CreditCard();
        $card->setNumber('4111111111111111');
        $card->setExpireAt(new \DateTime('2018-05-12'));
        $card->setSecurityCode(123);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->will($this->returnCallback(function(CreateToken $request) use ($card) {
                $this->assertInstanceOf(ArrayObject::class, $request->getModel());
                $cardData = [
                    'number' => $card->getNumber(),
                    'exp_month' => $card->getExpireAt()->format('m'),
                    'exp_year' => $card->getExpireAt()->format('Y'),
                    'cvc' => $card->getSecurityCode(),
                ];
                $this->assertSame(['card' => $cardData], (array) $request->getModel());
            }))
        ;

        $action = new CreateTokenForCreditCardAction();
        $action->setGateway($gatewayMock);

        $action->execute(new CreateTokenForCreditCard($card));
    }

    /**
     * @test
     */
    public function shouldSubExecuteCreateTokenAndSetTokenInRequest()
    {
        $card = new CreditCard();
        $card->setNumber('4111111111111111');
        $card->setExpireAt(new \DateTime('2018-05-12'));
        $card->setSecurityCode(123);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->will($this->returnCallback(function (CreateToken $request) {
                $model = ArrayObject::ensureArrayObject($request->getModel());
                $model->replace(['id' => 'myToken']);
                $request->setModel($model);
            }))
        ;

        $action = new CreateTokenForCreditCardAction();
        $action->setGateway($gatewayMock);

        $action->execute($createTokenForCreditCard = new CreateTokenForCreditCard($card));

        $token = $createTokenForCreditCard->getToken();
        $this->assertEquals('myToken', $token);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->getMock(GatewayInterface::class);
    }
}
