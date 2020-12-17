<?php

use NF_ConstantContact_Admin_HandledResponse as HandledResponse;

/**
 * Given HandledResponse entity, adds HTML markup for Diagnostic Metabox
 * 
 * This class constructs HTML that outputs a given HandledResponse for display
 * inside a Ninja Forms submission metabox.  It only provides the markup for
 * a specific handled response, NOT the entire metabox.  It is thus constructed
 * such that multiple responses can be displayed inside a single metabox (which
 * would be required in more complex submissions that make multiple requests
 * for a given form submission) 
 *
 */
class NF_ConstantContact_Admin_MarkupResponseDataMetabox {

    /**
     * Marked up HTML string
     * 
     * @var string
     */
    protected $markup = '';

    /**
     * Handled response to be marked up
     * 
     * @var HandledResponse
     */
    protected $handledResponse;

    /**
     * Handled Response context
     * 
     * @var string
     */
    protected $context;

    /**
     * Handled Response string
     * @var string
     */
    protected $result;

    /**
     * Handled Response record
     * 
     * @var string
     */
    protected $record;

    /**
     * Handled Response error messages array
     * 
     * @var array
     */
    protected $errorMessages;

    /**
     * Returns metabox markup for given HandledResponse
     * 
     * @param HandledResponse $handledResponse
     * @return string
     */
    public function markupHandledResponse($handledResponse) {

        $this->handledResponse = $handledResponse;
        $this->initialize();
        $this->constructContext();
        $this->constructResult();

        if ($this->handledResponse->isSuccessful()) {
            $this->constructRecord();
        } else {
            $this->constructErrorMessages();
        }
        
        $this->constructMarkup();
        
        return $this->markup;
    }
    /**
     * Constructs markup from Handled Response
     * 
     * Wraps context as term, with result, record, and error messages as 
     * definitions.
     */
    protected function constructMarkup() {
        $this->markup .= '<dt>' . $this->context . '</dt>';
        $this->markup .= '<dd>' . $this->result . '</dd>';

        if ('' !== $this->record) {
            $this->markup .= '<dd>' . $this->record . '</dd>';
        }
        if (!empty($this->errorMessages)) {
            foreach ($this->errorMessages as $errorMessage) {
                $this->markup .= '<dd>' . $errorMessage. '</dd>';
            }
        }
    }
    /**
     * Construct human-readable context - remove underscores/add capitalization
     */
    protected function constructContext() {

        $contextString = $this->handledResponse->getContext();

        $contextArray = explode('_', $contextString);

        $this->context = implode(
                ' ',
                array_map(function($str) {
                    return ucfirst($str);
                }, $contextArray));
    }
    
    /**
     * Construct human readable result as determined by HandledResponse flags
     */
    protected function constructResult() {

        $result = 'Unknown Error';

        if ($this->handledResponse->isSuccessful()) {
            $result = 'Success';
        } elseif ($this->handledResponse->isApiError()) {
            $result = 'Rejected by API';
        } elseif ($this->handledResponse->isWpError()) {
            $result = 'Wordpress Error';
        }
        $this->result = 'Result: ' . $result;
    }

        /**
     * Construct string output for a record id if record id is present
     */
    protected function constructRecord() {
        $records = $this->handledResponse->getRecords();

        $id = '';
        if (!empty($records)) {
            $arrayKeys = array_keys($records);

            $id= $arrayKeys[0];
        }

        $this->record = 'New Record Id: ' . $id;
    }

    /**
     * Construct error messages from HandledResponse
     */
    protected function constructErrorMessages() {
        $this->errorMessages = $this->handledResponse->getErrorMessages();
    }
    
   /**
     * Initialize values for markup, ensuring known default values
     */
    protected function initialize() {
        $this->markup = '';
        $this->context = '';
        $this->result = '';
        $this->record = '';
        $this->errorMessages = array();
    }

}
