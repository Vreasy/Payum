<?php
namespace Payum\Stripe\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetErrorInterface;
use Payum\Stripe\Constants;

class ErrorAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (@$model['error']) {
            $request->setOriginalErrorCode(
                @$model['error']['code'] ?: @$model['error']['type'] ?: @$model['error']['message']
            );
            if (@$model['error']['type'] === 'card_error') {
                switch ($model['error']['code']) {
                    case 'invalid_number':
                    case 'invalid_expiry_month':
                    case 'invalid_expiry_year':
                    case 'invalid_cvc':
                    case 'incorrect_cvc':
                    case 'incorrect_number':
                    case 'incorrect_zip':
                        $request->markInvalidCreditCard();
                        break;
                    case 'expired_card':
                        $request->markExpiredCreditCard();
                        break;
                    case 'card_declined':
                        $request->markDeclinedCreditCard();
                        break;
                    case 'missing':
                        $request->markMissingCreditCard();
                        break;
                }
            } elseif (@$model['error']['type'] == 'invalid_request_error') {
                $request->markInvalidRequest();
            } else {
                $request->markUnknown();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetErrorInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
