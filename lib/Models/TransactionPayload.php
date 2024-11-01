<?php
/**
 * Created by PhpStorm.
 * User: prestonf
 * Date: 1/26/18
 * Time: 4:04 PM
 */

namespace iTransact\iTransactSDK;


class TransactionPayload
{
    public $amount;
    public $card;
    public $address;

    /**
     * TransactionPayload constructor.
     * @param integer $amount Example: $15.00 should be 1500
     * @param CardPayload $card
     * @param AddressPayload $address
     */
    public function __construct($amount, $card, $address)
    {
        $this->amount = $amount;
        $this->card = $card;
        // TODO - make address optional
        $this->address = $address;
    }


    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param mixed $card
     */
    public function setCard($card)
    {
        $this->card = $card;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }


}