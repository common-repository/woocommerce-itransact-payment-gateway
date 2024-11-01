<?php
/**
 * Copyright (c) Payroc LLC 2017.
 */

/**
 * Created by PhpStorm.
 * User: prestonf
 * Date: 8/2/17
 * Time: 3:40 PM
 */

namespace iTransact\iTransactSDK;

/**
 * Class CardPayload
 * @package iTransact\iTransactSDK
 */
class CardPayload
{
    public $name;
    public $number;
    public $cvv;
    public $exp_month;
    public $exp_year;

    /**
     * CardPayload constructor.
     *
     * @param $name
     * @param $number
     * @param $cvv
     * @param $exp_month
     * @param $exp_year
     */
    public function __construct($name, $number, $cvv, $exp_month, $exp_year)
    {
        $this->name = $name;
        $this->number = $number;
        $this->cvv = $cvv;
        $this->exp_month = $exp_month;
        $this->exp_year = $exp_year;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getCvv()
    {
        return $this->cvv;
    }

    /**
     * @param mixed $cvv
     */
    public function setCvv($cvv)
    {
        $this->cvv = $cvv;
    }

    /**
     * @return mixed
     */
    public function getExpMonth()
    {
        return $this->exp_month;
    }

    /**
     * @param mixed $exp_month
     */
    public function setExpMonth($exp_month)
    {
        $this->exp_month = $exp_month;
    }

    /**
     * @return mixed
     */
    public function getExpYear()
    {
        return $this->exp_year;
    }

    /**
     * @param mixed $exp_year
     */
    public function setExpYear($exp_year)
    {
        $this->exp_year = $exp_year;
    }


}