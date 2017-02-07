<?php
namespace Payum\Core\Request;

class GetError extends Generic implements GetErrorInterface
{
    const CREDIT_CARD_INVALID = 'credit_card_invalid';
    const CREDIT_CARD_EXPIRED = 'credit_card_expired';
    const CREDIT_CARD_DECLINED = 'credit_card_declined';
    const CREDIT_CARD_MISSING = 'credit_card_missing';
    const INVALID_REQUEST = 'invalid_request';
    const UNKNOWN = 'unknown';

    protected $error_code;

    protected $original_error_code;

    /**
     * {@inheritDoc}
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->markUnknown();
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->error_code;
    }

    /**
     * @return mixed
     */
    public function getOriginalErrorCode()
    {
        return $this->original_error_code;
    }

    /**
     * @return mixed
     */
    public function setOriginalErrorCode($error_code)
    {
        $this->original_error_code = $error_code;
    }

    /**
     * @return void
     */
    public function markInvalidCreditCard()
    {
        $this->error_code = self::CREDIT_CARD_INVALID;
    }

    /**
     * @return void
     */
    public function markExpiredCreditCard()
    {
        $this->error_code = self::CREDIT_CARD_EXPIRED;
    }

    /**
     * @return void
     */
    public function markDeclinedCreditCard()
    {
        $this->error_code = self::CREDIT_CARD_DECLINED;
    }

    /**
     * @return boolean
     */
    public function markMissingCreditCard()
    {
        $this->error_code = self::CREDIT_CARD_MISSING;
    }

    /**
     * @return boolean
     */
    public function markInvalidRequest()
    {
        $this->error_code = self::INVALID_REQUEST;
    }

    /**
     * @return boolean
     */
    public function markUnknown()
    {
        $this->error_code = self::UNKNOWN;
    }

    /**
     * @return boolean
     */
    public function isInvalidCreditCard()
    {
        return $this->error_code === self::CREDIT_CARD_INVALID;
    }

    /**
     * @return boolean
     */
    public function isExpiredCreditCard()
    {
        return $this->error_code === self::CREDIT_CARD_EXPIRED;
    }

    /**
     * @return boolean
     */
    public function isDeclinedCreditCard()
    {
        return $this->error_code === self::CREDIT_CARD_DECLINED;
    }

    /**
     * @return boolean
     */
    public function isMissingCreditCard()
    {
        return $this->error_code === self::CREDIT_CARD_MISSING;
    }

    /**
     * @return boolean
     */
    public function isInvalidRequest()
    {
        return $this->error_code === self::INVALID_REQUEST;
    }

    /**
     * @return boolean
     */
    public function isUnknown()
    {
        return $this->error_code === self::UNKNOWN;
    }

}
