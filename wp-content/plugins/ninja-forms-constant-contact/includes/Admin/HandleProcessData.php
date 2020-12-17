<?php

/**
 * Add error and response data to Ninja Forms ->process() $data
 *
 * Heavily based on NF_Stripe_Checkout_FormData
 *
 */
class NF_ConstantContact_Admin_HandleProcessData
{

	/**
	 * NF Process Data passed to action
	 * @var array
	 */
	protected $data;

	/**
	 *
	 * @var string
	 */
	protected $actionKey='';
	
	/**
	 * Incoming NF Process data
	 * @param array $data
	 * @param string $actionKey
	 */
	public function __construct( $data, $actionKey)
	{
		$this->data = $data;
		$this->actionKey = $actionKey;
	}

	/**
	 * Add a form error
	 * @param string $message
	 * @return \SaturdayDrive\EmailCRM\NfBridge\Actions\HandleProcessData
	 */
	public function addFormError( $message): NF_ConstantContact_Admin_HandleProcessData
	{
		$this->data['errors']['form'][$this->actionKey] = $message;
		
		return $this;
	}


	/**
	 * Append ResponseData array
	 * @param array $responseData
	 */
	public function appendResponseData($responseData): NF_ConstantContact_Admin_HandleProcessData
	{
		$this->data['extra'][$this->actionKey]['responseData'][]=$responseData;
		
		return $this;
	}


	/**
	 * Return process $data array
	 * @return array
	 */
	public function toArray()
	{
		return $this->data;
	}
}
