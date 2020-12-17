<?php

final class NF_ConstantContact_Admin_OutputResponseDataMetabox extends NF_Abstracts_SubmissionMetabox
{


	/**
	 * HTML markup to output Response Data
	 * @var string
	 */
	protected $markup = '';

        /**
         * Key under which response data is stored in NinjaForms process() $data
         * 
         * @var string
         */
        protected $extraValueKey;
                
        /**
         * Handled Response class
         * 
         * Non monorepo versions each have unique version of class to avoid collision
         * @var mixed
         */
        protected $handledResponseObject;
        
        /**
         *
         * @var NF_ConstantContact_Admin_MarkupResponseDataMetabox 
         */
        protected $markupResponseDataMetabox;
        
        /**
	 * Collection of response data
	 * @var array
	 */
	protected $responseData = [];
    
        /**
     * 
     * @param mixed $handledResponseObject
     * @param string $extraValueKey
     * @param string $label
     * @param mixed $markupResponseDataMetabox
     */
	public function __construct($handledResponseObject,$extraValueKey='',$label= '', $markupResponseDataMetabox=null)
	{
		parent::__construct();

                $this->handledResponseObject=$handledResponseObject;
                
                $this->extraValueKey = $extraValueKey;
                
		$this->_title = $label;
                
                if(is_null($markupResponseDataMetabox)){
                    $this->markupResponseDataMetabox = new NF_ConstantContact_Admin_MarkupResponseDataMetabox;
                }else{
                    $this->markupResponseDataMetabox= $markupResponseDataMetabox;
                }
	}
    /**
     * Ninja Forms method that outputs metabox
     * 
     * @param mixed $post
     * @param mixed $metabox
     */
	public function render_metabox($post, $metabox)
	{
		if (!$this->sub->get_extra_value($this->extraValueKey)) {
			$this->addNoResponseDataMarkup();
		} else {
                    $this->markup = '';
			$this->extractResponseData();
                    
                        foreach($this->responseData as $handledResponse){
                            $this->markup.=$this->markupResponseDataMetabox->markupHandledResponse($handledResponse);
                        }
		}
		echo $this->markup;
	}



	/**
	 * Construct collection of HandledResponse entities
	 */
	protected function extractResponseData()
	{
		$submissionDataHandledResponse = $this->sub->get_extra_value($this->extraValueKey);

		if (isset($submissionDataHandledResponse['responseData']) && 
                        is_array($submissionDataHandledResponse['responseData'])
                        && !empty($submissionDataHandledResponse['responseData'])) {
			foreach ($submissionDataHandledResponse['responseData'] as $responseDataArray) {
				$this->responseData[] = $this->handledResponseObject->fromArray($responseDataArray);
			}
		}
	}

	/**
	 * Add markup for no response data available
	 */
	protected function addNoResponseDataMarkup()
	{
		$markup = "<dl>"
				. "<dd><strong>No response data available for this submission</strong></dd>"
				. "</dl>";

		$this->markup .= $markup;
	}
}
