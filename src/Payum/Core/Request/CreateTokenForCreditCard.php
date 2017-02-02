<?php
namespace Payum\Core\Request;

use Payum\Core\Model\CreditCardInterface;

class CreateTokenForCreditCard extends Generic
{
    /**
     * @var array|\ArrayAccess
     */
    protected $token = [];

    /**
     * @var CreditCardInterface
     */
    private $card;

    /**
     * @param CreditCardInterface $card
     */
    public function __construct($card)
    {
        $this->card = $card;
    }

    /**
     * @return array|\ArrayAccess
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param array|\ArrayAccess $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return CreditCardInterface
     */
    public function getCard()
    {
        return $this->card;
    }
}
