<?php

use NinjaForms\Includes\Entities\NfSiteEnvironment;
use NinjaForms\Includes\Entities\Usage;

use NinjaForms\Includes\Factories\ConstructNfSiteEntity;
use NinjaForms\Includes\Factories\ConstructNfSiteEnvironmentEntity;
use NinjaForms\Includes\Factories\ConstructUsageEntity;

/**
 * Handles sending information to our api.ninjaforms.com endpoint.
 *
 * @since  3.2
 */
class NF_Dispatcher
{
    private $api_url = 'https://api.ninjaforms.com/';
    
    /**
     * Returns bool true if we are opted-in or have a premium add-on.
     * If a premium add-on is installed, then users have opted into tracked via our terms and conditions.
     * If no premium add-ons are installed, check to see if the user has opted in or out of anonymous usage tracking.
     *
     * @since  3.2.0
     * @return bool
     */
    public function should_we_send() {
        /**
         * TODO:
         * Prevent certain URLS or IPs from submitting. i.e. staging, 127.0.0.1, localhost, etc.
         */

        if ( ! has_filter( 'ninja_forms_settings_licenses_addons' ) && ( ! Ninja_Forms()->tracking->is_opted_in() || Ninja_Forms()->tracking->is_opted_out() ) ) {
            return false;
        }
        return true;
    }

    /**
     * Send consolidated telemetry data
     * 
     * @since  3.2
     * @return void
     * 
     * @updated 3.3.17
     */
    public function sendTelemetryData() {

        $environment = $this->constructNfSiteEnvironment();

        $usage = $this->constructUsage( $environment->nf_db_version);

        $data = array_merge($environment->toArray(),$usage->toArray());
        
        $this->send( '3.8.18', $data );
    }
    
    /**
     * Construct environment variable
     *
     * @return NfSiteEnvironment
     */
    protected function constructNfSiteEnvironment(): NfSiteEnvironment
    {
        $factory = new ConstructNfSiteEnvironmentEntity();

        $return = $factory->handle();

        return $return;
    }

    /**
     * Construct usage array
     *
     * @return Usage
     */
    protected function constructUsage(): Usage
    {
        $factory = new ConstructUsageEntity();

        $return = $factory->handle();

        return $return;
    }

    /**
     * Sends a campaign slug and data to our API endpoint.
     * Checks to ensure that the user has 1) opted into tracking or 2) they have a premium add-on installed.
     * 
     * @since  3.2
     * @param  string       $slug   Campaign slug
     * @param  array        $data   Array of data being sent. Should NOT already be a JSON string.
     * @return void
     */
    public function send( $slug, $data = array() ) {

        if ( ! $this->should_we_send() ) {
            return false;
        }

        $siteData = $this->constructSiteData();
        // Only the 3.8.18 report stores and acknowledges the legacy backlog.
        $be_data = $slug === '3.8.18' ? NF_Telemetry_BehavioralQueue::legacyBacklog() : [];

        /*
         * Send our data using wp_remote_post.
         */
        $response = wp_remote_post(
            $this->api_url,
            array(
                'body' => array(
                    'slug'          => $slug,
                    'data'          => wp_json_encode( $data ),
                    'site_data'     => wp_json_encode($siteData),
                    'be_data'       => wp_json_encode( $be_data )
                ),
            )
        );

        if ($slug === '3.8.18') {
            $ack = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 201
                ? json_decode(wp_remote_retrieve_body($response), true) : null;
            NF_Telemetry_BehavioralQueue::settleLegacy($be_data, $ack);
        }
    }

    protected function constructSiteData(): array
    {
        $factory = new ConstructNfSiteEntity();
        return $factory->handle()->jsonSerialize();
    }

    /** Deliver independently of monthly environment collection; retry without data loss. */
    public function sendBehavioralData()
    {
        if (!$this->should_we_send()) {
            return false;
        }
        $events = NF_Telemetry_BehavioralQueue::pending();
        if (!$events) {
            return true;
        }
        $site = $this->constructSiteData();
        $batch = wp_generate_uuid4();
        $response = wp_remote_post($this->api_url, [
            'timeout' => 20,
            'body' => [
                'slug' => 'behavioral_v2',
                'data' => wp_json_encode(['site_url' => $site['url'], 'batch_id' => $batch, 'events' => $events]),
            ],
        ]);
        $ack = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 201
            ? json_decode(wp_remote_retrieve_body($response), true) : null;
        $accepted = is_array($ack) && isset($ack['batch_id'], $ack['schema_version'])
            && $ack['batch_id'] === $batch && $ack['schema_version'] === 2;
        if ($accepted) {
            NF_Telemetry_BehavioralQueue::acknowledge($events, $ack);
        }
        $attempt = $accepted ? 0 : min(6, (int) get_option('nf_be_delivery_attempt', 0) + 1);
        update_option('nf_be_delivery_attempt', $attempt, false);
        if (NF_Telemetry_BehavioralQueue::pending(1)) {
            NF_Telemetry_BehavioralQueue::schedule(min(21600, 900 * (2 ** $attempt)));
        }
        return $accepted;
    }
}
