<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Stripe\Request\Api\CreateToken;
use Payum\Stripe\StripeHeadersInterface;
use Payum\Stripe\StripeHeadersTrait;
use Payum\Stripe\Keys;
use Stripe\Error;
use Stripe\Stripe;
use Stripe\Token;

class CreateTokenAction extends GatewayAwareAction implements ApiAwareInterface
{
    use ApiAwareTrait;
    use StripeHeadersTrait;

    public function __construct()
    {
        $this->apiClass = Keys::class;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CreateToken */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $token = Token::create(
                $model->toUnsafeArrayWithoutLocal(),
                $this->getStripeHeaders($request)
            );

            $model->replace($token->__toArray(true));
        } catch (Error\Base $e) {
            if ($e->getJsonBody()) {
                $model->replace($e->getJsonBody());
            } else {
                throw($e);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CreateToken &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
