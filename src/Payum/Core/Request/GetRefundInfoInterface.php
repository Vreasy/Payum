<?php
namespace Payum\Core\Request;

use Payum\Core\Model\ModelAggregateInterface;
use Payum\Core\Model\ModelAwareInterface;

interface GetRefundInfoInterface extends ModelAwareInterface, ModelAggregateInterface
{
    /**
     * @return string
     */
    public function getRefundId();

    /**
     * @param string $refundId
     */
    public function setRefundId($refundId);
}
