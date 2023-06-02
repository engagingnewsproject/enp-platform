<?php

namespace NinjaForms\ConstantContact\Handlers;

use NinjaForms\ConstantContact\Handlers\Api2CheckExistingEmail;
use NinjaForms\ConstantContact\Handlers\Api2UpdateExistingSubscriber;
use NinjaForms\ConstantContact\Handlers\Api2CreateNewSubscriber;

use \NF_ConstantContact_Admin_HandledResponse as HandledResponse;

/**
 * Subsribe new email/add list to existing subscriber
 */
class Api2Subscribe
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
     * Subsribe new email/add list to existing subscriber
     *
     * @param array $memberData
     * @return bool
     */
    public function handle(array $memberData)
    {
        $handledResponseEmailCheck = $this->checkExistingEmail($memberData['email']);

        $recordCount = $handledResponseEmailCheck->getRecordCount();

        if (0 < $recordCount) {

            $records = $handledResponseEmailCheck->getRecords();

            $matchedSubscriber = $records[0];

            $this->handledResponse =  $this->updateExistingSubscriber($memberData, $matchedSubscriber);
        } else {

            $this->handledResponse = $this->createNewSubscriber($memberData);
        }


        // Contact added successfully
        return true;
    }

    /**
     * Check if email already exists in acccount
     *
     * @param string $email
     * @return HandledResponse Response object after checking for matching emails
     */
    protected function checkExistingEmail(string $email): HandledResponse
    {
        $object = new Api2CheckExistingEmail();

        $object->setAccessToken($this->accessToken)
            ->setApiDevKey($this->apiDevKey)
            ->handle($email);

        $handledResponse = $object->getHandledResponse();

        return $handledResponse;
    }

    /**
     * Update an existing subscriber to add a new mailing list
     *
     * @param array $memberData
     * @param array $matchedSubscriber
     * @return HandledResponse
     */
    protected function updateExistingSubscriber(array $memberData, array $matchedSubscriber): HandledResponse
    {
        $object = new Api2UpdateExistingSubscriber();

        $object->setAccessToken($this->accessToken)
            ->setApiDevKey($this->apiDevKey)
            ->handle($memberData, $matchedSubscriber);

        $handledResponse = $object->getHandledResponse();

        return $handledResponse;
    }

    /**
     * Create a new subscriber using the member data
     *
     * @param array $member_data
     * @return HandledResponse
     */
    protected function createNewSubscriber(array $member_data): HandledResponse
    {
        $object = new Api2CreateNewSubscriber();

        $object->setAccessToken($this->accessToken)
            ->setApiDevKey($this->apiDevKey)
            ->handle($member_data);

        $handledResponse = $object->getHandledResponse();

        return $handledResponse;
    }

    /**
     * Set the value of apiDevKey
     *
     * @return  Api2Subscribe
     */
    public function setApiDevKey($apiDevKey): Api2Subscribe
    {
        $this->apiDevKey = $apiDevKey;

        return $this;
    }

    /**
     * Set constant Contact access token
     *
     * @param  string  $accessToken  Constant Contact access token
     *
     * @return  Api2Subscribe
     */
    public function setAccessToken(string $accessToken): Api2Subscribe
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
