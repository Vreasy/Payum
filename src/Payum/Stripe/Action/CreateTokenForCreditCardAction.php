<?php
namespace Payum\Stripe\Action;

use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\CreditCardInterface;
use Payum\Core\Security\SensitiveValue;
use Payum\Stripe\Request\Api\CreateToken;
use Payum\Core\Request\CreateTokenForCreditCard;
use Stripe\Error;
use Stripe\Stripe;
use Stripe\Token;

class CreateTokenForCreditCardAction extends GatewayAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param CreateTokenForCreditCard $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var CreditCardInterface $card */
        $card = $request->getCard();

        $token = ArrayObject::ensureArrayObject($request->getToken());

        $cardData = [
            'number' => $card->getNumber(),
            'exp_month' => $card->getExpireAt()->format('m'),
            'exp_year' => $card->getExpireAt()->format('Y'),
        ];

        if ($card->getSecurityCode()) {
            $cardData['cvc'] = $card->getSecurityCode();
        }

        $token['card'] = $cardData;

        $this->gateway->execute(new CreateToken($token));

        $request->setToken($token->toUnsafeArray());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        //error_log("***** ".get_class($request)." instanceof: ".($request instanceof CreateTokenForCreditCard));
        //error_log("***** ".get_class($request->getCard())." instanceof: ".($request->getCard() instanceof CreditCardInterface));
        return
            $request instanceof CreateTokenForCreditCard &&
            $request->getCard() instanceof CreditCardInterface
        ;
    }
}
