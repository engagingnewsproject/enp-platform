<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_AJAX_Controllers_Onboarding extends NF_Abstracts_Controller
{
    private $session;

    public function __construct()
    {
        // Register AJAX hooks.
		add_action('wp_ajax_nf_onboarding_dismiss', array($this, 'handle'));
		add_action('wp_ajax_nf_onboarding_start', array($this, 'handle'));
		add_action('wp_ajax_nf_onboarding_next', array($this, 'handle'));
        add_action('wp_ajax_nf_onboarding_complete', array($this, 'handle'));
        // Register Heartbeat hook.
        add_filter('heartbeat_received', array($this, 'pulse'), 10, 2);
        // Register step verification.
        add_filter('nf_onboarding_step_now', array($this, 'step_now'));
        add_filter('nf_onboarding_page_now', array($this, 'page_now'));
        // Register setting hooks.
        add_filter('ninja_forms_check_setting_show_welcome', array($this, 'show_welcome'));
        add_action('ninja_forms_save_setting_show_welcome', array($this, 'toggle_show_welcome'));
        add_filter('ninja_forms_current_user_is_onboarding', array($this, 'current_user_is_onboarding'));
    }

    /**
     * Heartbeat API callback
     * @param Array $response The response data to be filtered.
     * @param Array $data The extra data registered to the heartbeat.
     * @return Array
     */
    public function pulse($response, $data)
    {
        $this->init_session();
        // If we're not onboarding, leave.
        if(! $this->in_progress()) return $response;

        // If we don't have a current page, leave.
        if(empty($data['ninja_forms_onboarding_page_now'])) return $response;

        // If it's the right page for our current step, update our current timestamp.
        if( false !== strpos( $data['ninja_forms_onboarding_page_now'], $this->page_now() ) ) {
            $this->session['last_active'] = current_time( 'timestamp' );
            $this->update();
        } else{
            // If it's not the right page (and we've exceeded 30 minutes), set the session to abandoned.
            $now = current_time('timestamp');
            $window = ($this->page_now() === 'page=ninja-forms&form_id=') ? 55 : 1800;
            if($now - $this->session['last_active'] >= $window) {
                $this->session['status'] = 'abandoned';
                $this->radio('abandon');
                $this->update();
            }
        }
        return $response;
    }

    /**
     * Determine if the current user has the welcome page enabled in settings.
     */
    public function show_welcome($response)
    {
        $this->init_session();
        $response['value'] = intval(!$this->is_dismissed());
        return $response;
    }

    /**
     * Toggle dismissal of onboarding.
     */
    public function toggle_show_welcome($reset)
    {
        if($reset) {
            delete_user_meta(get_current_user_id(), 'nf_onboarding');
        } else {
            $this->init_session();
            $this->dismiss();
        }
    }

    /**
     * Verify current user's onboarding status.
     */
    public function current_user_is_onboarding($status)
    {
        $this->init_session();
        $status = $this->in_progress();
        return $status;
    }

    /**
     * Verifies onboarding is in progress.
     * @return Boolean
     */
    public function in_progress()
    {
        return $this->session['status'] === 'enabled';
    }

    /**
     * Initiates the session.
     */
    public function init_session()
    {
        $this->session = [
            'status' => 'waiting',
            'step' => 0,
            'last_active' => current_time( 'timestamp' ),
        ];
        $session = get_user_meta( get_current_user_id(), 'nf_onboarding', true );
        if(is_array($session)) $this->session = array_merge($this->session, $session);
    }

    /**
     * Begins the onboarding process.
     * @return Boolean
     */
    public function start()
    {
        if($this->in_progress()) {
            $this->_errors[] = 'Could not begin onboarding already in progress.';
            return false;
        }

        $this->session = [
            'status' => 'enabled',
            'step' => 1,
            'last_active' => current_time( 'timestamp' ),
        ];

        return $this->update();
    }

    /**
     * Verifies our current target page.
     * @return String
     */
    public function page_now()
    {
        switch($this->session['step']) {
            case 0:
                return 'page=nf-welcome';
            case 1:
                return 'page=ninja-forms#forms';
            case 2:
                return 'page=ninja-forms#new-form';
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 10:
                return 'page=ninja-forms&form_id=';
            case 11:
                // @TODO: set this up later.
                return '';
            default:
                $this->_errors[] = 'Failed to get page for step that does not exist.';
                return '';
        }
    }

    /**
     * Verifies our current step.
     * @return Integer
     */
    public function step_now($request)
    {
        $this->init_session();
        if(!$this->in_progress()) return 0;
        return $this->session['step'];
    }

    /**
     * Moves the onboarding session to the next step.
     * @return Boolean
     */
    public function next()
    {
        if(!$this->in_progress()) {
            $this->_errors[] = 'Could not advance onboarding not already in progress.';
            return false;
        }

        $this->session['step'] += 1;
        $this->session['last_active'] = current_time('timestamp');

        return $this->update();
    }

    /**
     * Complete the onboarding process.
     * @return Boolean
     */
    public function complete()
    {
        if(!$this->in_progress()) {
            $this->_errors[] = 'Could not complete onboarding not already in progress.';
            return false;
        }

        $this->session['status'] = 'completed';

        return $this->update();
    }

    /**
     * Dismiss the onboarding process.
     * @return Boolean
     */
    public function dismiss()
    {
        $this->session['status'] = 'dismissed';
        return $this->update();
    }

    /**
     * Determine if onboarding has been dismissed.
     * @return Boolean
     */
    public function is_dismissed()
    {
        if($this->session['status'] === 'dismissed') return true;
        if($this->session['status'] === 'completed') return true;
        return false;
    }

    /**
     * Updates user meta with our session data.
     * @return Boolean
     */
    public function update()
    {
        if(!update_user_meta(get_current_user_id(), 'nf_onboarding', $this->session)) {
            $this->_errors[] = 'Failed to update user meta value.';
            return false;
        }
        return true;
    }

    /**
     * AJAX request handler method.
     */
    public function handle()
    {
        // @TODO: Check security?
        $this->init_session();
        // Handle radio call
        $this->_data['success'] = false;
        $action = str_replace('nf_onboarding_', '', $_POST['action']);
        if(method_exists($this, $action)) {
            $this->_data['success'] = $this->{$action}();
        } else {
            $this->_errors[] = 'Invalid method requested.';
        }
        $this->radio($action);
        // Call telemetry (if enabled)
        // Respond
        $this->_respond();
    }

    /**
     * Telemetry radio caller.
     */
    public function radio($action)
    {
        // Remove anything that isn't an alphabetic character.
        preg_replace("/[^a-z ]/", '', $action);
        $message = '';
        $user = get_current_user_id();
        $timestamp = date("F j, Y, g:i a");
        $status = $action;
        switch ($action) {
            case 'start':
                $message .= 'User has begun onboarding.';
                break;
            case 'next':
                // Don't actually send anything when they're progressing through the steps.
                break;
            case 'dismiss':
                $message .= 'User has dismissed onboarding.';
                break;
            case 'complete':
                $message .= 'User has completed onboarding.';
                break;
            case 'abandon':
                $message .= 'User has abandoned onboarding.';
                break;
            default:
                $status = 'error';
                $message .= 'A disallowed method [' . $status . '] was called.';
                break;
        }
        // @TODO: send to telemetry.
    }
}