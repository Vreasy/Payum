<?php
namespace Payum\Stripe\Action\Api;

use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Stripe\Request\Api\CreateCustomer;
use Payum\Stripe\StripeHeadersInterface;
use Payum\Stripe\StripeHeadersTrait;
use Payum\Stripe\Keys;
use Stripe\Customer;
use Stripe\Error;
use Stripe\Stripe;

class CreateCustomerAction extends GatewayAwareAction implements ApiAwareInterface
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

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $customer = $model->toUnsafeArrayWithoutLocal();
            if (@$customer['source'] && is_array($customer['source']) && !@$customer['source']['object']) {
                $customer['source']['object'] = 'card';
            }
            $customer = Customer::create($customer, $this->getStripeHeaders($request));

            $model->replace($customer->__toArray(true));
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
            $request instanceof CreateCustomer &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
