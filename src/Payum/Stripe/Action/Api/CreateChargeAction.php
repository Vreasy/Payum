<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Stripe\Request\Api\CreateCharge;
use Payum\Stripe\StripeHeadersInterface;
use Payum\Stripe\StripeHeadersTrait;
use Payum\Stripe\Keys;
use Stripe\Charge;
use Stripe\Error;
use Stripe\Stripe;

class CreateChargeAction implements ActionInterface, ApiAwareInterface, StripeHeadersInterface
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
        /** @var $request CreateCharge */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (false == ($model['card'] || $model['customer'] || $model['source'])) {
            throw new LogicException('The either card token or customer id or source has to be set.');
        }

        if (is_array($model['card']) || is_array($model['source'])) {
            throw new LogicException('The token has already been used.');
        }

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $charge = Charge::create(
                $model->toUnsafeArrayWithoutLocal(),
                $this->getStripeHeaders($request)
            );

            $model->replace($charge->__toArray(true));
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
            $request instanceof CreateCharge &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
