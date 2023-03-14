<?php

namespace NinjaForms\Includes\Entities;

use NinjaForms\Includes\Entities\SimpleEntity;

/**
 * Entity defining a submission field, including value if present
 * 
 * Submission field is the submission value for a single field within a form's field collection
 */
class SubmissionField extends SimpleEntity
{

    /**
     * Record id for the stored data
     *
     * @var string
     */
    protected $id = '';

    /**
     * Field slug
     *
     * @var string
     */
    protected $slug = '';

    /**
     * Field label
     *
     * @var string
     */
    protected $label = '';

    /**
     * Admin label
     *
     * @var string
     */
    protected $adminLabel = '';

    /**
     * Field type
     *
     * @var string
     */
    protected $type = '';

    /**
     * Indexed collection of option label/value/calc
     *
     * @var array
     */
    protected $options =[];
    
    /**
     * Indexed collection of fieldset repeater fields as arrays within parent field
     *
     * @var array
     */
    protected $fieldsetRepeaterFields = [];

    /**
     * Array of complete field settings
     * 
     * Original source is from NF DB tables
     *
     * @var array
     */
    protected $original=[];

    /**
     * Submission value, null by default
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Construct entity from associative array
     *
     * @param array $items
     * @return SubmissionField
     */
    public static function fromArray(array $items): SubmissionField
    {
        $obj = new static();

        foreach ($items as $property => $value) {

            $obj = $obj->__set($property, $value);
        }
        
        //filter repeater field data
        if($obj->type === "repeater" && !empty($obj->fieldsetRepeaterFields) && !empty($items["value"])){
            $fieldIDs = [];
			foreach($obj->fieldsetRepeaterFields as $fieldsetRepeaterField){
                array_push( $fieldIDs, $fieldsetRepeaterField['id']);
            }
			foreach($obj->value as $id => $valueArr){
				$repeaterFieldID = substr($valueArr['id'], 0, strpos($valueArr['id'], "_"));
                if(!in_array($repeaterFieldID, $fieldIDs)){
                    unset($obj->value[$id]);
                }
            }
        }

        return $obj;
    }


    /**
     * Get field Id
     *
     * @return  string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set field database record id
     *
     * @param  string  $id  Field Id
     *
     * @return  SubmissionField
     */
    public function setId(string $id): SubmissionField
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get field slug
     *
     * @return  string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set field slug
     *
     * @param  string  $slug  Field slug
     *
     * @return  SubmissionField
     */
    public function setSlug(string $slug): SubmissionField
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get field label
     *
     * @return  string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set field label
     *
     * @param  string  $label  Field label
     *
     * @return  SubmissionField
     */
    public function setLabel(string $label): SubmissionField
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get amdin label
     *
     * @return  string
     */
    public function getAdminLabel(): string
    {
        return $this->adminLabel;
    }

    /**
     * Set amdin label
     *
     * @param  string  $adminLabel  Admin label
     *
     * @return  SubmissionField
     */
    public function setAdminLabel(string $adminLabel): SubmissionField
    {
        $this->adminLabel = $adminLabel;

        return $this;
    }

    /**
     * Get field type
     *
     * @return  string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set field type
     *
     * @param  string  $type  Field type
     *
     * @return  SubmissionField
     */
    public function setType(string $type): SubmissionField
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get submission Value
     *
     * @return  mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set submission Value
     *
     * @param  mixed  $value  Submission Value
     *
     * @return  self
     */
    public function setValue($value): SubmissionField
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get indexed collection of option label/value/calc
     *
     * @return  array
     */ 
    public function getOptions():array
    {
        return $this->options;
    }

    /**
     * Set indexed collection of option label/value/calc
     *
     * @param  array  $options  Indexed collection of option label/value/calc
     *
     * @return  SubmissionField
     */ 
    public function setOptions(array $options):SubmissionField
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get Indexed collection of fieldset repeater fields within parent field
     *
     * @return  array
     */ 
    public function getFieldsetRepeaterFields():array
    {
        return $this->fieldsetRepeaterFields;
    }

    /**
     * Set Indexed collection of fieldset repeater fields within parent field
     *
     * @param  SubmissionField[]  $fieldsetRepeater  Indexed collection of fieldset repeater fields within parent field
     *
     * @return  SubmissionField
     */ 
    public function setFieldsetRepeaterFields(array $fieldsetRepeaterCollection):SubmissionField
    {
        $this->fieldsetRepeaterFields = $fieldsetRepeaterCollection;

        return $this;
    }

    /**
     * Get original field settings as stored in NF DB tables
     *
     * @return  array
     */ 
    public function getOriginal():array
    {
        return $this->original;
    }

    /**
     * Set original source field settings (from NF DB tables)
     *
     * @param  array  $original  Original source is from NF DB tables
     *
     * @return  SubmissionField
     */ 
    public function setOriginal(array $original):SubmissionField
    {
        $this->original = $original;

        return $this;
    }
}
