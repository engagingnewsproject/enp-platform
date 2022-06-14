<?php
use NinjaForms\Includes\Entities\MetaboxOutputEntity;
use NF_ConstantContact_Admin_HandledResponse as HandledResponse;

/**
 * Outputs metabox on React submissions page
 *
 */
class NF_ConstantContact_Admin_MetaboxOutput {

    public function handle($extraValue, $nfSub): ?MetaboxOutputEntity
    {
        $return = null;

        // If extra value is not set, there is nothing to display
        // If it is set, then dislay all values to contain within one metabox
        if (!isset($extraValue['responseData'])) {
            return $return;
        }

        $labelValueCollection = self::extractResponses($extraValue['responseData']);

        if (!empty($labelValueCollection)) {

            $array = [
                'title' => __( 'Constant Contact Response', 'ninja-forms-constant-contact' ),
                'labelValueCollection' => $labelValueCollection

            ];

            $return = MetaboxOutputEntity::fromArray($array);
        }

        return $return;
    }

    /**
     * Extract all extra data to be displayed
     *
     * @param array $handledReponseCollection
     * @return array
     */
    protected static function extractResponses(array $handledReponseCollection): array
    {

        $return = [];

        foreach ($handledReponseCollection as $handledResponseAsArray) {
            if (!is_array($handledResponseAsArray)) {
                continue;
            }
            $handledResponse = HandledResponse::fromArray($handledResponseAsArray);

            $return[] = self::addContext($handledResponse, $return);

            $return[] = self::addResult($handledResponse);

            $record = self::addRecord($handledResponse);
            if (!empty($record)) {
                $return[] = $record;
            }

            foreach ($handledResponse->getErrorMessages() as $errorMessage) {
                $return[] = [
                    'label' => '',
                    'value' => $errorMessage,
                    'styling' => 'alert'
                ];
            }
        }

        return $return;
    }


    /**
     * Construct/add the context
     *
     * @param HandledResponse $handledResponse
     * @return array
     */
    protected static function addContext(HandledResponse $handledResponse): array
    {
        $contextString = $handledResponse->getContext();

        $contextArray = explode('_', $contextString);

        $context = implode(
            ' ',
            array_map(function ($str) {
                return ucfirst($str);
            }, $contextArray)
        );

        $return = [
            'label' => __('Context', 'ninja-forms-constant-contact'),
            'value' => $context,
            'styling' => ''
        ];

        return $return;
    }

    /**
     * Construct/add the result
     *
     * @param HandledResponse $handledResponse
     * @return array
     */
    protected static function addResult(HandledResponse $handledResponse): array
    {
        $result = 'Unknown Error';

        if ($handledResponse->isSuccessful()) {
            $result = __('Success', 'ninja-forms-constant-contact');
        } elseif ($handledResponse->isApiError()) {
            $result = __('Rejected by API', 'ninja-forms-constant-contact');
        } elseif ($handledResponse->isWpError()) {
            $result = __('Wordpress Error', 'ninja-forms-constant-contact');
        }

        $return = [
            'label' => __('Result', 'ninja-forms-constant-contact'),
            'value' => $result,
            'styling' => 'Success' === $result ? '' : 'alert'
        ];


        return $return;
    }

    /**
     * Construct/add the record
     *
     * @param HandledResponse $handledResponse
     * @return array
     */
    protected static function addRecord(HandledResponse $handledResponse): array
    {
        $return = [];

        $records = $handledResponse->getRecords();

        if (0 < count($records)) {
            $arrayKeys = array_keys($records);

            $id = $arrayKeys[0];

            $record =  $id;

            $return = [
                'label' => __('New Record Id', 'ninja-forms-constant-contact'),
                'value' => $record,
                'styling' => ''
            ];
        }


        return $return;
    }

}
