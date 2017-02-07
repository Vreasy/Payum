<?php
namespace Payum\Stripe\Tests\Action\Api;

use Payum\Core\Request\GetError;
use Payum\Core\Tests\GenericActionTest;
use Payum\Stripe\Action\ErrorAction;
use Payum\Stripe\Constants;

class ErrorActionTest extends GenericActionTest
{
    protected $requestClass = GetError::class;

    protected $actionClass = ErrorAction::class;

    /**
     * @test
     */
    public function shouldMarkInvalidCardIfNumberIsInvalid()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'invalid_number',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkInvalidCardIfExpiryMonthIsInvalid()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'invalid_expiry_month',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkInvalidCardIfExpiryYearIsInvalid()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'invalid_expiry_year',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkInvalidCardIfCVCIsInvalid()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'invalid_cvc',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkInvalidCardIfCVCCIsIncorrect()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'incorrect_cvc',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkInvalidCardIfNumnerIsIncorrect()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'incorrect_number',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkFailedIfZipIsIncorrect()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'incorrect_zip',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isInvalidCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkExpiredCardIfCardIsExpired()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'expired_card',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isExpiredCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkDeclinedCardIfCardisExpired()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'card_declined',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isDeclinedCreditCard());
    }

    /**
     * @test
     */
    public function shouldMarkUnknownIfErrorTypeIsUnknown()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'some_unknown_error',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isUnknown());
    }

    /**
     * @test
     */
    public function shouldMarkIMissingCardIfCardIsMIssing()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'missing',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isMissingCreditCard());
    }

    /**
     * @test
     */
    public function shouldSetTheOriginalErrorCodeCorrectlyFromCode()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'card_error',
                'code' => 'unknown_error',
                'message' => 'This is an error message',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertEquals('unknown_error', $error->getOriginalErrorCode());
    }

    /**
     * @test
     */
    public function shouldSetTheOriginalErrorCodeCorrectlyFromType()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'This is an error message',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertEquals('invalid_request_error', $error->getOriginalErrorCode());
    }

    /**
     * @test
     */
    public function shouldSetTheOriginalErrorCodeCorrectlyFromMessage()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'message' => 'This is an error message',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertEquals(
            'This is an error message',
            $error->getOriginalErrorCode()
        );
    }

    /**
     * @test
     */
    public function shouldSetUnknownErrorWhenErrorIsUnknown()
    {
        $action = new ErrorAction();

        $model = [
            'error' => [
                'foo' => 'bar',
            ],
        ];

        $action->execute($error = new GetError($model));

        $this->assertTrue($error->isUnknown());
    }

}
