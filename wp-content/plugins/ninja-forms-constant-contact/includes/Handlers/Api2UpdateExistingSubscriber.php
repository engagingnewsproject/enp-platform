<?php

namespace NinjaForms\ConstantContact\Handlers;

use NinjaForms\ConstantContact\Handlers\Api2UpdateListConstructor;
use \NF_ConstantContact_Admin_HandledResponse as HandledResponse;

/**
 * Add list to existing subscriber
 *
 */
class Api2UpdateExistingSubscriber
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

    /**
     * Add list to existing subscriber
     *
     * @param array $memberData
     * @return bool
     */
    public function handle(array $memberData, array $matchedSubscriber)
    {
        $api_url_args = array(
            'api_key'   => $this->apiDevKey
        );

        $api_url  = add_query_arg($api_url_args, 'https://api.constantcontact.com/v2/contacts/' . $matchedSubscriber['id']);

        $headers  = array(
            'Authorization' => 'Bearer ' . trim($this->accessToken),
            'Content-Type'  => 'application/json'
        );

        $api_body = (new Api2UpdateListConstructor())->handle($memberData, $matchedSubscriber)->getJsonEncodedBody();

        $query = wp_remote_post($api_url, array('headers' => $headers, 'sslverify' => false, 'method' => 'PUT', 'body' => $api_body));

        $this->handledResponse = new HandledResponse();

        $this->handledResponse->setContext('NF_ConstantContact_updateExisting');

        $this->handledResponse->setTimestamp(time());

        if (is_wp_error($query)) {

            $this->handledResponse->setIsSuccessful(FALSE);

            $this->handledResponse->setIsWpError(TRUE);

            $this->handledResponse->setErrorMessages(array($query->get_error_message()));

            return false;
        }

        $this->handledResponse->setResponseBody($query['body']);

        if (200 != $query['response']['code']) {
            $this->handledResponse->setIsSuccessful(FALSE);
            $this->handledResponse->setIsApiError(TRUE);
            $this->extractRejection();
            return false;
        }

        $this->extractRecords();

        // Contact added successfully
        return true;
    }


    /**
     * Extract rejection details
     *
     * @return void
     */
    protected function extractRejection()
    {
        $responseObjectArray = json_decode($this->handledResponse->getResponseBody(), TRUE);

        $singleResponse = $responseObjectArray[0];

        $errorCode = $singleResponse['error_key'];

        $errorMessage = $singleResponse['error_message'];

        $this->handledResponse->setErrorMessages(array($errorCode, $errorMessage));
    }

    /**
     * Extract record details
     *
     * @return void
     */
    protected function extractRecords()
    {
        $responseObject = json_decode($this->handledResponse->getResponseBody());

        $id = $responseObject->id;

        $recordArray = array(
            $id => array(
                'id' => $id,
            )
        );

        $this->handledResponse->setRecords($recordArray);
    }

    /**
     * Set the value of apiDevKey
     *
     * @return  Api2UpdateExistingSubscriber
     */
    public function setApiDevKey($apiDevKey): Api2UpdateExistingSubscriber
    {
        $this->apiDevKey = $apiDevKey;

        return $this;
    }

    /**
     * Set constant Contact access token
     *
     * @param  string  $accessToken  Constant Contact access token
     *
     * @return  Api2UpdateExistingSubscriber
     */
    public function setAccessToken(string $accessToken): Api2UpdateExistingSubscriber
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
