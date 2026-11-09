<?php

namespace NinjaForms\Includes\Entities;

use NinjaForms\Includes\Entities\SimpleEntity;

/**
 * Entity holding title and label/value/styling output for metaboxes
 */
class MetaboxOutputEntity extends SimpleEntity
{

    /**
     * Metabox title
     *
     * @var string
     */
    protected $title = '';

    /**
     * Collection of label/value/styling/url elements for output
     *
     * Each element: 'label' and 'value' (required), 'styling' (optional class
     * hint), and 'url' (optional link for the value, escaped with esc_url_raw;
     * '' when absent).
     *
     * @var array
     */
    protected $labelValueCollection = [];

    /**
     * Construct entity from associative array
     *
     * @param array $items
     * @return SingleSubmission
     */
    public static function fromArray(array $items): MetaboxOutputEntity
    {
        $obj = new static();

        foreach ($items as $property => $value) {

            // Pass field value through entity to validate, then add, keyed on slug
            if ('labelValueCollection' === $property) {
                foreach ($value as $labelValueElement) {

                    if (!isset($labelValueElement['label']) || !isset($labelValueElement['value'])) {
                        continue;
                    }

                    $styling = isset($labelValueElement['styling'])?$labelValueElement['styling']:'';
                    $url = self::normaliseUrl($labelValueElement);

                    $obj->labelValueCollection[] = [
                        'label' => $labelValueElement['label'],
                        'value' => $labelValueElement['value'],
                        'styling'=>$styling,
                        'url'=>$url
                    ];
                }
            } else {

                $obj = $obj->__set($property, $value);
            }
        }
        return $obj;
    }

    /**
     * Constructs an array representation
     */
    public function toArray(): array
    {
        $vars = get_object_vars($this);

        $array = ['labelValueCollection' => []];

        foreach ($vars as $property => $value) {
            if('labelValueCollection'===$property){
                $value = $this->validateLabelValueCollection($value);
            }

            $array[$property] = $value;
        }

        return $array;
    }

    protected function validateLabelValueCollection( $incomingLabelValueCollection): array
    {
        $return = [];
        
        if(!\is_array($incomingLabelValueCollection)){
            return $return;
        }

        foreach($incomingLabelValueCollection as $incomingLabelValue){
            if (!isset($incomingLabelValue['label']) || !isset($incomingLabelValue['value'])) {
                continue;
            }
            
            $styling = isset($incomingLabelValue['styling'])?$incomingLabelValue['styling']:'';
            $url = self::normaliseUrl($incomingLabelValue);

            $return[]=[
                'label' => $incomingLabelValue['label'],
                'value' => $incomingLabelValue['value'],
                'styling'=>$styling,
                'url'=>$url
            ];
        }

        return $return;
    }

    /**
     * Escaped url of a label/value element, or '' when it has none
     *
     * Empty urls are skipped so an element without a link needs no WordPress functions.
     *
     * Untyped on purpose: add-on metaboxes pass label/value elements too, and
     * some may be array-like objects rather than arrays.
     *
     * @param array|\ArrayAccess $labelValueElement
     * @return string
     */
    private static function normaliseUrl($labelValueElement): string
    {
        return isset($labelValueElement['url'])&&''!==$labelValueElement['url']?\esc_url_raw($labelValueElement['url']):'';
    }

    /**
     * Get metabox title
     *
     * @return  string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set metabox title
     *
     * @param  string  $title  Metabox title
     *
     * @return  MetaboxOutputEntity
     */
    public function setTitle(string $title): MetaboxOutputEntity
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of labelValueCollection
     */
    public function getLabelValueCollection(): array
    {
        return $this->labelValueCollection;
    }

    /**
     * Set the value of labelValueCollection
     *
     * @return  MetaboxOutputEntity
     */
    public function setLabelValueCollection($labelValueCollection): MetaboxOutputEntity
    {
        $this->labelValueCollection = $labelValueCollection;

        return $this;
    }
}
