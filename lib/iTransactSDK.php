<?php
/**
 * Copyright (c) Payroc LLC 2017.
 */

namespace iTransact\iTransactSDK {

    require_once (__DIR__.'/Models/CardPayload.php');
    require_once (__DIR__.'/Models/AddressPayload.php');
    require_once (__DIR__.'/Models/TransactionPayload.php');

    use iTransact\iTransactSDK\CardPayload;
    use iTransact\iTransactSDK\AddressPayload;
    use iTransact\iTransactSDK\TransactionPayload;

    /**
     * Class iTCore - does the majority of the work behind the scenes.
     *
     *
     * @package iTransact\iTransactSDK
     * @author Preston Farr
     * @copyright Payroc LLC
     * @example
     * $trans = new iTTransaction();
     *
     * $trans::postCardTransaction($username, $somekey, $payload);
     */
    class iTCore
    {
        /**
         *
         */
        const API_BASE_URL = "https://api.itransact.com";

        /**
         * @param string $apiUsername
         * @param string $apiKey
         * @param TransactionPayload $payload
         *
         * @return array
         */
        public static function generateHeaderArray($apiUsername, $apiKey, $payload)
        {
            $payloadSignature = self::signPayload($apiKey, $payload);
            $encodedUsername = base64_encode($apiUsername);
            return array(
                'Content-Type: application/json',
                'Authorization: ' . $encodedUsername . ':' . $payloadSignature
            );
        }

        /**
         * Workaround for the const not being able to have an expression assigned at runtime.
         *
         * @return string
         */
        public static function API_POST_TOKENS_URL(){
            return self::API_BASE_URL . "/tokens";
        }

        /**
         * Workaround for the const not being able to have an expression assigned at runtime.
         *
         * @return string
         */
        public static function API_GET_TOKENS_URL(){
            return self::API_POST_TOKENS_URL() . "/"; // Just add token id at the end
        }

        /**
         * Workaround for the const not being able to have an expression assigned at runtime.
         *
         * @return string
         */
        public static function API_POST_TRANSACTIONS_URL(){
            return self::API_BASE_URL . "/transactions";
        }

        /**
         * Workaround for the const not being able to have an expression assigned at runtime.
         *
         * @return string
         */
        public static function API_GET_TRANSACTIONS_URL(){
            return self::API_POST_TRANSACTIONS_URL() . "/"; // Just add transaction id at the end
        }

        /**
         * @param string $apikey
         * @param string $payload
         *
         * @return string
         */
        public static function signPayload($apikey, $payload)
        {
            $digest = hash_hmac('sha256', json_encode($payload), $apikey, true);
            return base64_encode($digest);
        }
    }

    /**
     * Publicly availiable
     *
     * Class iTTransaction
     * @package iTransact\iTransactSDK
     * @see
     */
    class iTTransaction
    {
        /**
         * Submits signed payload to iTransact's JSON API Gateway
         *
         * Use this function to generate headers (signing the payload in the process) and submit the transaction.
         *
         * @param string $apiUsername
         * @param string $apiKey
         * @param TransactionPayload $payload
         *
         * @return mixed
         */
        public function postCardTransaction($apiUsername, $apiKey, $payload)
        {
            $headers = iTCore::generateHeaderArray($apiUsername, $apiKey, $payload);

            $ch = curl_init(iTCore::API_POST_TRANSACTIONS_URL());
            curl_setopt_array($ch, array(
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload)
            ));

            $response = curl_exec($ch);
			
            if ($response === FALSE) {
                die(curl_error($ch));
            }

            return json_decode($response, TRUE);
        }



        /**
         * Signs payload using the api key and returns only the signature.
         *
         * If you change your payload size AT ALL this signature (HMAC string) will also change
         *
         * @param string $apikey
         * @param string $payload
         *
         * @return string
         */
        public function signPayload($apikey, $payload)
        {
            return iTCore::signPayload($apikey, $payload);
        }

        // TODO - add other SDK methods for ACH, etc.

    }
}