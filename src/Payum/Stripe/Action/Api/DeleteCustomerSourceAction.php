<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Stripe\Request\Api\DeleteCustomerSource;
use Payum\Stripe\StripeHeadersInterface;
use Payum\Stripe\StripeHeadersTrait;
use Payum\Stripe\Keys;
use Stripe\Customer;
use Stripe\Error;
use Stripe\Stripe;

class DeleteCustomerSourceAction extends GatewayAwareAction implements ApiAwareInterface
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
        /** @var $request CreateCustomer */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $model->validateNotEmpty(array(
            'customer',
            'id'
        ));

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $customer = Customer::retrieve($model['customer'], $this->getStripeHeaders($request));
            $source = $customer->sources->retrieve($model['id']);
            $deletedCard = $source->delete();

            $model->replace($deletedCard->__toArray(true));

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
            $request instanceof DeleteCustomerSource &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
