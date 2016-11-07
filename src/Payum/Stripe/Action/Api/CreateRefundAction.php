<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Refund;
use Payum\Stripe\StripeHeadersInterface;
use Payum\Stripe\StripeHeadersTrait;
use Payum\Stripe\Keys;
use Stripe\Refund as StripeRefund;
use Stripe\Error;
use Stripe\Stripe;
use Payum\Stripe\Constants;

class CreateRefundAction implements ActionInterface, ApiAwareInterface
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
        /** @var $request Refund */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!@$model['charge']) {
            throw new LogicException('The charge id has to be set.');
        }

        if (isset($model['amount'])
            && (!is_numeric($model['amount']) || $model['amount'] <= 0)
        ) {
            throw new LogicException('The amount is invalid.');
        }

        try {
            Stripe::setApiKey($this->api->getSecretKey());
            $refund = StripeRefund::create(
                $model->toUnsafeArrayWithoutLocal(),
                $this->getStripeHeaders($request)
            );
            $model->replace($refund->__toArray(true));
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
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
