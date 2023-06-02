<?php

namespace NinjaForms\ConstantContact\Handlers;

use NinjaForms\ConstantContact\Handlers\Api2SubscribeBodyConstructor;
use \NF_ConstantContact_Admin_HandledResponse as HandledResponse;

/**
 * Subsribe new email to list
 */
class Api2CreateNewSubscriber
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
     * Subsribe new email to list
     *
     * @param array $memberData
     * @return bool
     */
    public function handle(array $memberData)
    {
        $api_url_args = array(
            'api_key'   => $this->apiDevKey,
            'action_by' => 'ACTION_BY_VISITOR'
        );

        $api_url  = add_query_arg($api_url_args, 'https://api.constantcontact.com/v2/contacts');

        $headers  = array(
            'Authorization' => 'Bearer ' . trim($this->accessToken),
            'Content-Type'  => 'application/json'
        );

        $api_body = (new Api2SubscribeBodyConstructor())->handle($memberData)->getJsonEncodedBody();

        $query = wp_remote_post($api_url, array('headers' => $headers, 'sslverify' => false, 'body' => $api_body));

        $this->handledResponse = new HandledResponse();

        $this->handledResponse->setContext('NF_ConstantContact_subscribe');

        $this->handledResponse->setTimestamp(time());

        if (is_wp_error($query)) {

            $this->handledResponse->setIsSuccessful(FALSE);

            $this->handledResponse->setIsWpError(TRUE);

            $this->handledResponse->setErrorMessages(array($query->get_error_message()));

            return false;
        }

        $this->handledResponse->setResponseBody($query['body']);

        $responseArray = json_decode($this->handledResponse->getResponseBody(), true);

        if (201 != $query['response']['code']) {
            $this->handledResponse->setIsSuccessful(FALSE);
            $this->handledResponse->setIsApiError(TRUE);

            $errorCollection = $this->extractRejection($responseArray);

            $this->handledResponse->setErrorMessages($errorCollection);
            return false;
        }

        $recordArray = $this->extractRecord($responseArray);

        $this->handledResponse->setRecords($recordArray);
        // Contact added successfully
        return true;
    }

    /**
     * Extract rejection details
     *
     * @param array $responseArray
     * @return array
     */
    protected function extractRejection(array $responseArray): array
    {
        $singleResponse = $responseArray[0];

        $errorCode = $singleResponse['error_key'];

        $errorMessage = $singleResponse['error_message'];

        return [$errorCode, $errorMessage];
    }

    /**
     * Extract record details
     *
     * @param array $responseArray
     * @return array
     */
    protected function extractRecord(array $responseArray): array
    {
        $id = $responseArray['id'];

        $recordArray = array(
            $id => array(
                'id' => $id,
            )
        );

        return $recordArray;
    }

    /**
     * Set the value of apiDevKey
     *
     * @return  Api2CreateNewSubscriber
     */
    public function setApiDevKey($apiDevKey): Api2CreateNewSubscriber
    {
        $this->apiDevKey = $apiDevKey;

        return $this;
    }

    /**
     * Set constant Contact access token
     *
     * @param  string  $accessToken  Constant Contact access token
     *
     * @return  Api2CreateNewSubscriber
     */
    public function setAccessToken(string $accessToken): Api2CreateNewSubscriber
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
