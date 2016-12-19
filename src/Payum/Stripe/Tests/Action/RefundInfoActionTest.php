<?php

namespace Payum\Stripe\Tests\Action\Api;

use Payum\Core\Tests\GenericActionTest;
use Payum\Core\Request\GetRefundInfo;
use Payum\Stripe\Action\RefundInfoAction;

class RefundInfoActionTest extends GenericActionTest
{
    protected $requestClass = GetRefundInfo::class;

    protected $actionClass = RefundInfoAction::class;

    /**
     * @test
     */
    public function shouldSupportInfoRequestWithArrayAccessModel()
    {
        $action = new RefundInfoAction();

        $this->assertTrue($action->supports(new GetRefundInfo([])));
    }

    /**
     * @test
     */
    public function shouldNotSupportInfoRequestWithNonArrayAccessModel()
    {
        $action = new RefundInfoAction();

        $this->assertFalse($action->supports(new GetRefundInfo(new \stdClass())));
    }

    /**
     * @test
     */
    public function shouldObtainRefundIdCorrectly()
    {
        $action = new RefundInfoAction();

        $model = [
            'id' => 're_1234567890',
        ];

        $action->execute($refundInfo = new GetRefundInfo($model));

        $this->assertEquals("re_1234567890", $refundInfo->getRefundId());
    }

    /**
     * @test
     */
    public function shouldReturnNullRefundIdFromError()
    {
        $action = new RefundInfoAction();

        $model = [
            'error' => [
            ]
        ];

        $action->execute($refundInfo = new GetRefundInfo($model));

        $this->assertNull($refundInfo->getRefundId());
    }
}
