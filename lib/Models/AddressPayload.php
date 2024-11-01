<?php
/**
 * Copyright (c) Payroc LLC 2017.
 */

/**
 * Created by PhpStorm.
 * User: prestonf
 * Date: 8/2/17
 * Time: 3:49 PM
 */

namespace iTransact\iTransactSDK;


/**
 * Class Address
 * @package iTransact\iTransactSDK
 */
class AddressPayload
{

    public $line1;
    public $line2;
    public $city;
    public $state;
    public $postal_code;

    /**
     * @return mixed
     */
    public function getLine1()
    {
        return $this->line1;
    }

    /**
     * @param mixed $line1
     */
    public function setLine1($line1)
    {
        $this->line1 = $line1;
    }

    /**
     * @return mixed
     */
    public function getLine2()
    {
        return $this->line2;
    }

    /**
     * @param mixed $line2
     */
    public function setLine2($line2)
    {
        $this->line2 = $line2;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @param mixed $postal_code
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;
    }


    /**
     * AddressPayload constructor.
     * @param $line1
     * @param $line2
     * @param $city
     * @param $state
     * @param $postal_code
     */
    public function __construct($line1, $line2, $city, $state, $postal_code)
    {
        $this->line1 = $line1;
        $this->line2 = $line2;
        $this->city = $city;
        $this->state = $state;
        $this->postal_code = $postal_code;
    }

}