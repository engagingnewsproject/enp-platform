<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Action_CollectPayment
 */
final class NF_Actions_CollectPayment extends SotAction implements InterfacesSotAction
{
    use SotGetActionProperties;

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var array
     */
    protected $payment_gateways = array();

    protected $tempCpNiceName;
    protected $tempCpName;
    /**
     * Constructor
     *
     * @param string $cp_nice_name
     * @param string $cp_name
     */
    public function __construct(
        $cp_nice_name = 'Collect Payment',
        $cp_name = 'collectpayment'
    ) {
        $this->_name  = 'collectpayment';
        $this->_timing = 'late';
        $this->_priority = 0;

        add_action('init', [$this, 'initHook']);

        $this->tempCpNiceName = $cp_nice_name;
        $this->tempCpName = $cp_name;

        add_action('ninja_forms_loaded', array($this, 'register_payment_gateways'), -1);

        add_filter('ninja_forms_action_type_settings', array($this, 'maybe_remove_action'));
    }
    
    public function initHook(): void
    {
        $this->initializeSettings();

        // Set the nice name to what we passed in. 'Collect Payment' is default
        if ('Collect Payment' == $this->tempCpNiceName) {
            $this->tempCpNiceName = esc_html__('Collect Payment', 'ninja-forms');
        }

        $this->_nicename = $this->tempCpNiceName;
        // Set name to what we passed in. 'collectpayment' is default
        $this->_name = strtolower($this->tempCpName);

        $settings = Ninja_Forms::config('ActionCollectPaymentSettings');

        /**
         * if we pass in something other than 'collectpayment', set the value
         * of the gateway drop-down
         **/
        if ('collectpayment' != $this->_name) {
            $settings['payment_gateways']['value'] = $this->_name;
        }

        $this->_settings = array_merge($this->_settings, $settings);
    }

    function initializeSettings(): void
    {
        $this->_settings_all = apply_filters( 'ninja_forms_actions_settings_all', $this->_settings_all );

        if( ! empty( $this->_settings_only ) ){

            $this->_settings = array_merge( $this->_settings, $this->_settings_only );
        } else {

            $this->_settings = array_merge( $this->_settings_all, $this->_settings );
            $this->_settings = array_diff( $this->_settings, $this->_settings_exclude );
        }

        $this->_settings = $this->load_settings( $this->_settings_all );   
     }
 
    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {

        $payment_gateway = $action_settings['payment_gateways'];

        $payment_gateway_class = $this->payment_gateways[$payment_gateway];

        $handler = NF_Handlers_LocaleNumberFormatting::create();
        $converted = $handler->locale_decode_number($action_settings['payment_total']);
        $action_settings['payment_total'] = $converted;

        return $payment_gateway_class->process($action_settings, $form_id, $data);
    }

    public function register_payment_gateways()
    {
        $this->payment_gateways = apply_filters('ninja_forms_register_payment_gateways', array());

        add_action('init',[$this,'buildPaymentGatewayOptions'],15);

    }

    /**
     * Build gateway options for CollectPayment dropdown
     *
     * Done at `init-15` to ensure that object can populate translations
     * 
     * @return void
     */
    public function buildPaymentGatewayOptions(): void
    {
        foreach ($this->payment_gateways as $gateway) {

            if (! is_subclass_of($gateway, 'NF_Abstracts_PaymentGateway')) {
                continue;
            }

            $this->_settings['payment_gateways']['options'][] = array(
                'label' => $gateway->get_name(),
                'value' => $gateway->get_slug(),
            );

            $this->_settings = array_merge($this->_settings, $gateway->get_settings());
        }
    }

    public function maybe_remove_action($action_type_settings)
    {
        if (empty($this->payment_gateways)) {
            unset($action_type_settings[$this->_name]);
        }

        return $action_type_settings;
    }
}
