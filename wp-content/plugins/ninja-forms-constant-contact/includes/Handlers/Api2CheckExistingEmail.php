<?php

namespace NinjaForms\ConstantContact\Handlers;

use \NF_ConstantContact_Admin_HandledResponse as HandledResponse;

class Api2CheckExistingEmail
{

    /**
     * API developer key
     *
     * @var string
     */
    protected $apiDevKey = '';

    /**
     * Constant Contact access token
     *
     * @var string
     */
    protected $accessToken = '';

    /** @var HandledResponse */
    protected $handledResponse;

    public function handle(string $email)
    {
        $api_url_args = array(
            'api_key'   => $this->apiDevKey,
            'email' => $email
        );

        $api_url  = add_query_arg($api_url_args, 'https://api.constantcontact.com/v2/contacts');

        $headers  = array(
            'Authorization' => 'Bearer ' . trim($this->accessToken),
            'Content-Type'  => 'application/json'
        );

        $query = wp_remote_post($api_url, array('headers' => $headers, 'sslverify' => false, 'method' => 'GET'));

        $this->handledResponse = new HandledResponse();

        $this->handledResponse->setContext('NF_ConstantContact_checkExistingEmail');

        $this->handledResponse->setTimestamp(time());

        if (is_wp_error($query)) {

            $this->handledResponse->setIsSuccessful(FALSE);

            $this->handledResponse->setIsWpError(TRUE);

            $this->handledResponse->setErrorMessages(array($query->get_error_message()));

            return false;
        }

        $this->handledResponse->setResponseBody($query['body']);

        $responseArray = json_decode($this->handledResponse->getResponseBody(), TRUE);

        if (200 != $query['response']['code']) {
            $this->handledResponse->setIsSuccessful(FALSE);
            $this->handledResponse->setIsApiError(TRUE);

            $errorCollection = $this->extractRejection($responseArray);

            $this->handledResponse->setErrorMessages($errorCollection);

            return false;
        }

        $recordArray = $this->extractRecords($responseArray);
        $this->handledResponse->setRecords($recordArray);
        $this->handledResponse->setRecordCount(count($recordArray));
        // Contact added successfully
        return true;
    }


    /**
     * Extract rejection details
     *
     * @return array
     */
    protected function extractRejection(array $responseObjectArray): array
    {
        $singleResponse = $responseObjectArray[0];

        $errorCode = $singleResponse['error_key'];

        $errorMessage = $singleResponse['error_message'];

        return [$errorCode, $errorMessage];
    }

    /**
     * Extract record details
     *@param array $responseArray
     * @return array
     */
    protected function extractRecords(array $responseArray): array
    {
        if (isset($responseArray['results'][0])) {
            $return = [
                $responseArray['results'][0]
            ];
        } else {
            $return  = [];
        }

        return $return;
    }

    /**
     * Set the value of apiDevKey
     *
     * @return  Api2CheckExistingEmail
     */
    public function setApiDevKey($apiDevKey): Api2CheckExistingEmail
    {
        $this->apiDevKey = $apiDevKey;

        return $this;
    }

    /**
     * Set constant Contact access token
     *
     * @param  string  $accessToken  Constant Contact access token
     *
     * @return  Api2CheckExistingEmail
     */
    public function setAccessToken(string $accessToken): Api2CheckExistingEmail
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Get the value of handledResponse
     * 
     * @return HandledResponse
     */
    public function getHandledResponse(): HandledResponse
    {
        return $this->handledResponse;
    }
}
