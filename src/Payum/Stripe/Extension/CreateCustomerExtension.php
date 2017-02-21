<?php
namespace Payum\Stripe\Extension;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Security\SensitiveValue;
use Payum\Stripe\Constants;
use Payum\Stripe\Request\Api\CreateCustomer;
use Payum\Stripe\Request\Api\ObtainToken;
use Payum\Stripe\Request\Api\RetrieveCustomer;
use Payum\Stripe\Request\Api\UpdateCustomer;
use Payum\Stripe\Request\Api\CreateCustomerSource;
use Payum\Stripe\Request\Api\CreateCharge;
use Payum\Stripe\Request\Api\CreateToken;
use Payum\Stripe\Request\Api\RetrieveToken;

class CreateCustomerExtension implements ExtensionInterface
{
    /**
     * @var Context $context
     */
    public function onPreExecute(Context $context)
    {
        /** @var Capture $request */
        $request = $context->getRequest();
        if (false == $request instanceof Capture) {
            return;
        }

        $model = $request->getModel();
        if (false == $model instanceof \ArrayAccess) {
            return;
        }

        $model = ArrayObject::ensureArrayObject($model);

        $this->retrieveCustomer($context->getGateway(), $model);
        $this->createCustomer($context->getGateway(), $model);
    }

    /**
     * @var Context $context
     */
    public function onExecute(Context $context)
    {
    }

    /**
     * @var Context $context
     */
    public function onPostExecute(Context $context)
    {
        /** @var Capture $request */
        $request = $context->getRequest();
        if (false == $request instanceof ObtainToken
            && false == $request instanceof CreateCharge) {
            return;
        }

        $model = $request->getModel();
        if (false == $model instanceof \ArrayAccess) {
            return;
        }

        if ($request instanceof ObtainToken) {
            $this->createCustomer($context->getGateway(), ArrayObject::ensureArrayObject($model));
        }

        if ($request instanceof CreateCharge) {
            $this->updateCustomer($context->getGateway(), ArrayObject::ensureArrayObject($model));
        }
    }

    /**
     * @param GatewayInterface $gateway
     * @param ArrayObject $model
     */
    protected function createCustomer(GatewayInterface $gateway, ArrayObject $model)
    {
        if (@$model['customer']) {
            return;
        }

        if (!@$model['card']
            || (!is_string($model['card']) && !$model['card'] instanceof SensitiveValue)
        ) {
            return;
        }

        $local = $model->getArray('local');
        if (false == $local['save_card']) {
            return;
        }

        $customer = $local->getArray('customer');

        $customer['source'] = $model['card'] instanceof SensitiveValue
            ? $model['card']->peek()
            : $model['card'];
        if (@$customer['id']) {
            if ($model['card'] instanceof SensitiveValue || substr($model['card'], 0, 3) == 'tok') {
                // Create a new card for the customer based on the card details or token
                $customerSource = ArrayObject::ensureArrayObject(['customer' => $customer['id'], 'source' => $customer['source']]);
                $gateway->execute(new CreateCustomerSource($customerSource));
                if (!@$customerSource['id']) {
                    $model['status'] = Constants::STATUS_FAILED;
                    $model['error'] = @$customerSource['error'];
                    return;
                }
                $local['card_details'] = $customerSource->toUnsafeArray();
            } else {
                if ($card = current(array_filter(
                    @$customer['sources']['data'] ?: [],
                    function ($card) use ($model) {
                        return $card['id'] == $model['card'];
                    }
                ))) {
                    $local['card_details'] = $card;
                } else {
                    $local['card_details'] = [
                        'id' => $model['card'],
                    ];
                }
            }
        } else {
            $gateway->execute(new CreateCustomer($customer));
            if ($card = current(array_filter(
                @$customer['sources']['data'] ?: [],
                function ($card) use ($customer) {
                    return $card['id'] == $customer['default_source'];
                }
            ))) {
                $local['card_details'] = $card;
            } elseif (@$customer['default_source']) {
                $local['card_details'] = [
                    'id' => @$customer['default_source'],
                ];
            }
        }

        $customer = $customer->toUnsafeArray();
        if ($model['card'] instanceof SensitiveValue) {
            unset($customer['source']);
        }
        $local['customer'] = $customer;
        $model['local'] = $local->toUnsafeArray();
        unset($model['card']);

        if (@$customer['id'] && !@$customer['error']) {
            if (@$local['stripe_headers']['stripe_account']) {
                // For direct payments into a connected account, we must create a customer token
                $token = ArrayObject::ensureArrayObject([]);
                $token['customer'] = $customer['id'];
                $token['card'] = $local['card_details']['id'];
                $token['local'] = [
                    'stripe_headers' => @$local['stripe_headers'] ?: []
                ];
                $gateway->execute($token = new CreateToken($token));
                $token = $token->getModel()->toUnsafeArray();
                if (@$token['id']) {
                    $model['source'] = $token['id'];
                } else {
                    $model['status'] = Constants::STATUS_FAILED;
                    $model['error'] = $token['error'];
                }
            } else {
                $model['customer'] = $customer['id'];
                $model['source'] = $local['card_details']['id'];
            }
        } else {
            $model['status'] = Constants::STATUS_FAILED;
            $model['error'] = $customer['error'];
        }
    }

    protected function retrieveCustomer($gateway, $model)
    {
        if (@$model['customer']) {
            return;
        }

        $local = $model->getArray('local');
        if (false == $local['save_card']) {
            return;
        }

        $customer = $local->getArray('customer');
        if (!@$customer['id']) {
            return;
        }

        $customer = ArrayObject::ensureArrayObject($customer);
        $gateway->execute(new RetrieveCustomer($customer));
        $local['customer'] = $customer->toUnsafeArray();
        $model['local'] = $local->toUnsafeArray();
    }

    protected function updateCustomer($gateway, $model)
    {
        $local = $model->getArray('local');
        if (!@$local['card_details'] || !@$local['customer'] || !$local['save_card']) {
            return;
        }

        $customer = $local->getArray('customer');
        if (!@$customer['id'] || @$model['error'] || @$customer['default_source'] == @$local['card_details']['id']) {
            return;
        }

        $customer = ArrayObject::ensureArrayObject($customer);
        $customer['default_source'] = $local['card_details']['id'];
        $gateway->execute(new UpdateCustomer($customer));

        if (!@$customer['id']) {
            return;
        }

        $local['customer'] = $customer->toUnsafeArray();
        $model['local'] = $local->toUnsafeArray();
    }
}
