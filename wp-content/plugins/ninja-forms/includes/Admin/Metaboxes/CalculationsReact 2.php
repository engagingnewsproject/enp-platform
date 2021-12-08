<?php 
namespace NinjaForms\Includes\Admin\Metaboxes;

if (!defined('ABSPATH')) exit;
/**
 * Construct Calculations metabox for the React Submissions page
 *
 * Class must have a public function handle that can receive the $extraValue
 * and a NF submission.
 * 
 * Output of handle method is a \NinjaForms\Includes\Entities\MetaboxOutputEntity with two properties:
 * 
 * 'title' (string output of metabox title/header)
 * 'labelValueCollection' – indexed array of label values
 * 
 * Each label value array has three keys:
 *   'label' – label of the output
 *   'value' –  value of that being output
 *   'styling' – currently accepts 'alert' to add an 'alert' class for CSS styling
 */
class CalculationsReact
{

    /**
     * Given submission '$extra' data and the complete submission, return array
     * construct for metabox If nothing to output, then return null
     *
     * If the '$extra' data contains all required information, then simply
     * construct that as label/value/styling arrays.
     *
     * If your output requires other information from the submission, use the
     * $nfSub to extract the required information.
     *
     * Note that in this example, we want additional information from the
     * submission for output so we disregard the $extraValue and work directly
     * with the $nfSub to extract the information.
     * 
     * @param mixed $extraValue
     * @param NF_Database_Models_Submission $nfSub
     * @return \NinjaForms\Includes\Entities\MetaboxOutputEntity|null
     */
    public function handle($extraValue, $nfSub): ?\NinjaForms\Includes\Entities\MetaboxOutputEntity
    {
        $return = null;

        $debug = $this->isDebugSet();

        // extract/construct the label/value/styling arrays
        $labelValueCollection = self::extractResponses($extraValue,$debug);

        if (!empty($labelValueCollection)) {

            $array = [
                // Set a translatable title for your metabox
                'title' => __('Calculations', 'ninja-forms'),

                // set the label/value/styling
                'labelValueCollection' => $labelValueCollection

            ];

            $return = \NinjaForms\Includes\Entities\MetaboxOutputEntity::fromArray($array);
        }

        return $return;
    }

    /**
     * Construct calculations output
     */
    protected static function extractResponses($calculations, ?bool $debug=false ): array
    {
        // Initialize collection of label/value/styling arrays
        $return = [];

        if (is_array($calculations)) {
            foreach ($calculations as $name => $contents) {
                $result = [
                    'label' => \esc_html($name),
                    'value' => $contents['value']
                ];

                $return[] = $result;

                if(!$debug){
                    continue;
                }

                $raw = [
                    'label' =>  \esc_html($name). __(' - Raw', 'ninja-forms'),
                    'value' => $contents['raw']
                ];

                $return[] = $raw;
                
                $parsed = [
                    'label' =>  \esc_html($name) . __(' - Parsed', 'ninja-forms'),
                    'value' => $contents['parsed']
                ];

                $return[] = $parsed;
            }
        }
        return $return;
    }

    /**
     * Determine/return if calc debug is set
     * 
     * Checks for string `&calcs_debug` in URI
     *
     * @return boolean
     */
    protected  function isDebugSet( ): bool
    {
        $referer= filter_input(INPUT_SERVER,'HTTP_REFERER');

        $return = strpos($referer,'calcs_debug')>0?TRUE:FALSE;  
        
        return $return;
    }
}
