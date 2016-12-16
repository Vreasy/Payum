<?php

namespace Payum\Core\Request;

class GetRefundInfo extends Generic implements GetRefundInfoInterface
{
    protected $refund_id;

    public function setRefundId($refundId)
    {
        $this->refund_id = $refundId;
    }

    public function getRefundId()
    {
        return $this->refund_id;
    }
}
