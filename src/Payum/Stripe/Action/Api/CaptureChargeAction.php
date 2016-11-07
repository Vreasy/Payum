<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Stripe\Request\Api\CaptureCharge;
use Payum\Stripe\StripeHeadersInterface;
use Payum\Stripe\StripeHeadersTrait;
use Payum\Stripe\Keys;
use Stripe\Charge;
use Stripe\Error;
use Stripe\Stripe;

class CaptureChargeAction implements ActionInterface, ApiAwareInterface
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

        $model->validateNotEmpty(array(
            'id',
        ));

        if (false == $model['paid']) {
            throw new LogicException('The charge must have been authorized.');
        }
        if (true == $model['captured']) {
            throw new LogicException('The charge has already been captured.');
        }
        if (true == $model['refunded']) {
            throw new LogicException('The charge has been refunded.');
        }

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $charge = Charge::retrieve($model['id'], $this->getStripeHeaders($request));
            $charge->capture();

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
            $request instanceof CaptureCharge &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
