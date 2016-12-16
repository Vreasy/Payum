<?php
namespace Payum\Stripe\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetRefundInfoInterface;

class RefundInfoAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetRefundInfoInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $request->setRefundId(@$model['id']);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetRefundInfoInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
