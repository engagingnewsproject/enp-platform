<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit; //@codeCoverageIgnore

/**
 * Class NF_Action_Email
 */
class NF_Actions_Email extends SotAction implements InterfacesSotAction
{
    use SotGetActionProperties;

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * Absolute path of the CSV file created for the current submission, so it
     * can be removed after sending without recomputing a shared path. See #8200.
     *
     * @var string
     */
    protected $_csv_path = '';

    /**
     * Absolute path of the unique per-submission directory holding the CSV,
     * removed alongside the file after sending. See #8200.
     *
     * @var string
     */
    protected $_csv_dir = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_name  = 'email';
        $this->_timing = 'late';
        $this->_priority = 10;
        $this->_documentation_url = 'https://ninjaforms.com/docs/email/';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);
    }

    public function initHook()
    {
        $this->_nicename = esc_html__('Email', 'ninja-forms');

        $settings = Ninja_Forms::config('ActionEmailSettings');

        $this->_settings = array_merge($this->_settings, $settings);

        $this->_backwards_compatibility();
    }

    /*
    * PUBLIC METHODS
    */
    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {
        $action_settings = $this->sanitize_address_fields($action_settings);

        $errors = $this->check_for_errors($action_settings);

        $headers = $this->_get_headers($action_settings);

        if (has_filter('ninja_forms_get_fields_sorted')) {
            $fields_by_key = array();

            foreach ($data['fields'] as $fieldId => $field) {

                if (is_null($field)) continue;

                if (is_array($field)) {
                    if (! isset($field['key'])) continue;
                    $key = $field['key'];

                    // add field id if it isn't already set
                    if (!isset($field['id'])) {
                        $field['id'] = $fieldId;
                    }
                } else {
                    $key = $field->get_setting('key');
                }
                $fields_by_key[$key] = $field;
            }
            $sorted = apply_filters('ninja_forms_get_fields_sorted', array(), $data['fields'], $fields_by_key, $form_id);
            if (! empty($sorted))
                $data['fields'] = $sorted;
        }

        $attachments = $this->_get_attachments($action_settings, $data, $form_id);

        if ('html' == $action_settings['email_format']) {
            $message = wpautop($action_settings['email_message']);
        } else {
            $message = $this->format_plain_text_message($action_settings['email_message_plain']);
        }

        $message = apply_filters('ninja_forms_action_email_message', $message, $data, $action_settings);

        try {
            /**
             * Hook into the email send to override functionality.
             * @return bool True if already sent. False to fallback to default behavior. Throw a new Exception if there is an error.
             */
            if (! $sent = apply_filters('ninja_forms_action_email_send', false, $action_settings, $message, $headers, $attachments)) {
                $sent = wp_mail($action_settings['to'], strip_tags($action_settings['email_subject']), $message, $headers, $attachments);
            }
        } catch (Exception $e) {
            $sent = false;
            $errors['email_not_sent'] = $e->getMessage();
        }

        if (is_user_logged_in() && current_user_can('manage_options')) {
            $data['actions']['email']['to'] = $action_settings['to'];
            $data['actions']['email']['headers'] = $headers;
            $data['actions']['email']['attachments'] = $attachments;
        }

        $data['actions']['email']['sent'] = $sent;

        // Only show errors to Administrators.
        if ($errors && current_user_can('manage_options')) {
            $data['errors']['form'] = $errors;
        }

        if (! empty($attachments)) {
            $this->_drop_csv();
        }

        return $data;
    }

    /**
     * Sanitizes email address settings
     * @since 3.2.2
     *
     * @param  array $action_settings
     * @return array
     */
    protected function sanitize_address_fields($action_settings)
    {
        // Build a look array to compare our email address settings to.
        $email_address_settings = array('to', 'from_address', 'reply_to', 'cc', 'bcc');

        // Loop over the look up values.
        foreach ($email_address_settings as $setting) {
            // If the loop up values are not set in the action settings continue.
            if (! isset($action_settings[$setting])) continue;

            // If action settings do not match the look up values continue.
            if (! $action_settings[$setting]) continue;

            /*
             * The From address is a single address rather than a list, so it is
             * never split on a separator. Carriage returns and line feeds are
             * still removed, because they have no legitimate use in an address
             * and RFC 5322 section 2.2 forbids them inside a header field.
             */
            if ('from_address' === $setting) {
                $action_settings[$setting] = $this->normalize_from_address($action_settings[$setting]);
                continue;
            }

            /*
             * Merge tags can expand to lists separated by line breaks,
             * semicolons or pipes as well as commas, so all of those are
             * recognised. Entries that are not valid addresses are kept here so
             * that check_for_errors() can still report them to administrators.
             */
            $action_settings[$setting] = implode(',', $this->split_address_list($action_settings[$setting]));
        }
        return $action_settings;
    }

    /**
     * Removes carriage returns and line feeds from a header value
     *
     * RFC 5322 section 2.2 forbids a bare CR or LF inside a header field.
     * Merge tag values can carry either, so every value is stripped before it
     * is written into a header line.
     *
     * @param  string|null $value
     * @return string
     */
    protected function strip_header_breaks($value)
    {
        return str_replace(array("\r\n", "\r", "\n"), '', (string) $value);
    }

    /**
     * Splits an email address setting into its individual entries
     *
     * A merge tag placed in an address setting can expand to a list separated
     * by line breaks, semicolons or pipes as well as by commas, so all of those
     * are treated as separators. Empty and whitespace-only entries are dropped;
     * entries that are not valid addresses are preserved so that
     * check_for_errors() can still surface them to administrators.
     *
     * @param  string|array $value
     * @return array
     */
    protected function split_address_list($value)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $value = preg_replace('/[\r\n;|,]+/', ',', (string) $value);

        $entries = array();

        foreach (explode(',', $value) as $entry) {
            $entry = trim($entry);

            if ('' === $entry) continue;

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Normalises the From address
     *
     * The From address is a single address rather than a list, so it is never
     * split on a separator. Carriage returns and line feeds are removed,
     * because they have no legitimate use in an address and RFC 5322 section
     * 2.2 forbids them inside a header field.
     *
     * @param  string|array $value
     * @return string
     */
    protected function normalize_from_address($value)
    {
        if (is_array($value)) {
            $value = implode(',', array_map('trim', $value));
        }

        return trim($this->strip_header_breaks($value));
    }

    /**
     * Returns the individual entries of an address setting
     *
     * Every setting except the From address is a list; the From address is a
     * single address. Both paths match what _format_recipients() and
     * _format_from() will do with the same value, so validation and header
     * construction never disagree.
     *
     * @param  string       $setting
     * @param  string|array $value
     * @return array
     */
    protected function address_entries($setting, $value)
    {
        if ('from_address' === $setting) {
            return array($this->normalize_from_address($value));
        }

        return $this->split_address_list($value);
    }

    /**
     * Determines whether an entry can be used as an email recipient
     *
     * @param  string $email
     * @return bool
     */
    protected function is_valid_recipient($email)
    {
        return (bool) is_email(trim($this->strip_header_breaks($email)));
    }

    protected function check_for_errors($action_settings)
    {
        $errors = array();

        $email_address_settings = array('to', 'from_address', 'reply_to', 'cc', 'bcc');

        foreach ($email_address_settings as $setting) {
            if (! isset($action_settings[$setting])) continue;
            if (! $action_settings[$setting]) continue;


            $email_addresses = $this->address_entries($setting, $action_settings[$setting]);

            /*
             * Entries that are not usable addresses are skipped when the header
             * is built, so one of them is not on its own a reason to stop the
             * submission. The warning is raised only when a setting produced no
             * usable recipient at all.
             */
            $produced_a_recipient = false;

            foreach ($email_addresses as $email) {
                $email = trim($email);
                if (false !== strpos($email, '<') && false !== strpos($email, '>')) {
                    preg_match('/(?:<)([^>]*)(?:>)/', $email, $email);
                    $email = $email[1];
                }
                if (is_email($email)) {
                    $produced_a_recipient = true;
                    break;
                }
            }

            if (! $produced_a_recipient) {
                /* translators: 1: name of the email action. 2: name of the setting. */
                $errors['invalid_email'] = sprintf(esc_html__('Your email action "%1$s" has an invalid value for the "%2$s" setting. Please check this setting and try again.', 'ninja-forms'), $action_settings['label'], $setting);
            }
        }

        return $errors;
    }

    private function _get_headers($settings)
    {
        $headers = array();

        $headers[] = 'Content-Type: text/' . $settings['email_format'];
        $headers[] = 'charset=UTF-8';
        $headers[] = 'X-Ninja-Forms:ninja-forms'; // Flag for transactional email.

        $headers[] = $this->_format_from($settings);

        $headers = array_merge($headers, $this->_format_recipients($settings));

        return $headers;
    }

    private function _get_attachments($settings, $data, $form_id)
    {
        $attachments = array();

        if (isset($settings['attach_csv']) && 1 == $settings['attach_csv']) {
            $attachments[] = $this->_create_csv($data['fields'], $form_id);
        }

        if (! isset($settings['id'])) $settings['id'] = '';

        // Allow admins to attach files from media library
        if (isset($settings['file_attachment']) && 0 < strlen($settings['file_attachment'])) {
            $file_path = '';
            $media_id = attachment_url_to_postid($settings['file_attachment']);

            if ($media_id !== 0) {
                $file_path = get_attached_file($media_id);
                if (0 < strlen($file_path)) {
                    $attachments[] = $file_path;
                }
            }
        }

        $attachments = apply_filters('ninja_forms_action_email_attachments', $attachments, $data, $settings);

        return $attachments;
    }

    private function _format_from($settings)
    {
        $from_name = get_bloginfo('name', 'raw');
        $from_name = apply_filters('ninja_forms_action_email_from_name', $from_name);
        $from_name = ($settings['from_name']) ? $settings['from_name'] : $from_name;

        $from_address = get_bloginfo('admin_email');
        $from_address = apply_filters('ninja_forms_action_email_from_address', $from_address);
        $from_address = ($settings['from_address']) ? $settings['from_address'] : $from_address;

        return $this->_format_recipient('from', $from_address, $from_name);
    }

    private function _format_recipients($settings)
    {
        $headers = array();

        $recipient_settings = array(
            'Cc' => $settings['cc'],
            'Bcc' => $settings['bcc'],
            'Reply-to' => $settings['reply_to'],
        );

        foreach ($recipient_settings as $type => $emails) {

            foreach ($this->split_address_list($emails) as $email) {

                $email = $this->strip_header_breaks($email);

                $matches = array();
                if (preg_match('/^"?(?<name>[^<"]+)"? <(?<email>[^>]+)>$/', $email, $matches)) {
                    // Skip anything that is not a usable address so that an
                    // unusable entry cannot corrupt the header for the valid
                    // addresses listed alongside it.
                    if (! $this->is_valid_recipient($matches['email'])) continue;

                    $headers[] = $this->_format_recipient($type, $matches['email'], $matches['name']);
                } else {
                    if (! $this->is_valid_recipient($email)) continue;

                    $headers[] = $this->_format_recipient($type, $email);
                }
            }
        }

        return $headers;
    }

    private function _format_recipient($type, $email, $name = '')
    {
        $type = ucfirst($type);

        // Final guard: no header line this action produces may contain a bare
        // carriage return or line feed.
        $email = trim($this->strip_header_breaks($email));
        $name = trim($this->strip_header_breaks($name));

        if (! $name) $name = $email;

        $recipient = "$type: $name <$email>";

        return $recipient;
    }

    protected function _create_csv($fields, $form_id = 0)
    {
        $csv_array = array();

        // Get our current date.
        $date_format = Ninja_Forms()->get_setting('date_format');
        $today = date($date_format, current_time('timestamp'));
        $csv_array[0][] = 'Date Submitted';
        $csv_array[1][] = $today;

        /*
         * Field types the rest of the plugin hides from submission data. The
         * Save action drops these from the stored submission through this same
         * filter, which is why reCAPTCHA never reaches the Submissions page
         * export but did reach this attachment. See issue #7164.
         */
        $hidden_field_types = apply_filters('nf_sub_hidden_field_types', array());

        $ignore = array_values(array_unique(array_merge(
            array(
                'hr',
                'submit',
                'html',
                'creditcardcvc',
                'creditcardexpiration',
                'creditcardfullname',
                'creditcardnumber',
                'creditcardzip',
            ),
            $hidden_field_types
        )));

        /*
         * Field types that opt out of this attachment alone, such as the
         * password fields, add themselves here.
         */
        $ignore = apply_filters('ninja_forms_csv_ignore_fields', $ignore);

        foreach ($fields as $field) {

            if (! isset($field['label'])) continue;
            if (in_array($field['type'], $ignore)) continue;

            $label = (isset($field['admin_label']) && '' != $field['admin_label']) ? $field['admin_label'] : $field['label'];
            // Escape labels.
            $label = WPN_Helper::maybe_escape_csv_column($label);

            if ($field["type"] === "repeater" && isset($field['fields'])) {
                $value = "";
                foreach ($field['fields'] as $field_model) {
                    foreach ($field['value'] as $in_field_value) {
                        $matching_value = substr($in_field_value['id'], 0, strlen($field_model['id'])) === $field_model['id'];
                        $index_found = substr($in_field_value['id'], strpos($in_field_value['id'], "_") + 1);
                        if ($matching_value) {
                            //Catch specific file uploeds data
                            if (isset($in_field_value["files"])) {
                                $field_files_names = [];
                                foreach ($in_field_value["files"] as $file_data) {
                                    $field_files_names[] = $file_data["data"]["file_url"];
                                }
                                $in_field_value['value'] = implode(" , ", $field_files_names);
                            }

                            // Handle signature fields within repeaters
                            $field_value = $in_field_value['value'];
                            if ( isset($field_model['type']) && 'signature' === $field_model['type'] ) {
                                // Process signature field using the filter
                                $field_data = array(
                                    'type' => 'signature',
                                    'form_id' => $form_id
                                );
                                $field_value = apply_filters('ninja_forms_subs_export_field_value_signature', $field_value, $field_data);
                            } else {
                                $field_value = WPN_Helper::stripslashes($field_value);
                            }

                            // Handle array values (e.g., checkbox selections within repeaters)
                            if (is_array($field_value)) {
                                $field_value = implode(',', array_map(function ($v) {
                                    return is_array($v) ? implode(',', $v) : $v;
                                }, $field_value));
                            }

                            $value .= $field_model['label'] . "#" . $index_found . " : " . $field_value . " \n";
                        };
                    }
                }
            } else {
                // For signature fields, preserve the JSON structure
                if ( 'signature' === $field['type'] ) {
                    $value = $field['value'];
                } else {
                    $value = WPN_Helper::stripslashes($field['value']);
                }
                
                if (empty($value) && ! isset($value)) {
                    $value = '';
                }
                // Date fields store compound values (date/hour/minute/ampm parts) as an
                // array and combine them into a formatted string via their own export
                // filter below - flattening here first would destroy that structure.
                if (is_array($value) && 'date' !== $field['type']) {
                    // Handle nested arrays (e.g., repeater fields without 'fields' metadata)
                    $value = implode(',', array_map(function ($v) {
                        if (is_array($v)) {
                            return implode(',', array_map(function ($inner) {
                                return is_array($inner) ? implode(',', $inner) : $inner;
                            }, $v));
                        }
                        return $v;
                    }, $value));
                }
            }

            // Add form_id to field data for use in filters
            $field['form_id'] = $form_id;

            // add filter to add single quote if first character in value is '='
            $value = apply_filters('ninja_forms_subs_export_field_value_' . $field['type'], $value, $field);

            $csv_array[0][] = $label;
            $csv_array[1][] = $value;
        }

        $csv_content = WPN_Helper::str_putcsv(
            $csv_array,
            apply_filters('ninja_forms_sub_csv_delimiter', ','),
            apply_filters('ninja_forms_sub_csv_enclosure', '"'),
            apply_filters('ninja_forms_sub_csv_terminator', "\n")
        );

        $upload_dir = wp_upload_dir();
        $path = trailingslashit($upload_dir['path']);

        // create temporary file
        $path = tempnam($path, 'Sub');
        $temp_file = fopen($path, 'r+');

        // write to temp file
        fwrite($temp_file, $csv_content);
        fclose($temp_file);

        // find the directory we will be using for the final file
        $path_parts = pathinfo($path);
        $dir = $path_parts['dirname'];
        $basename = $path_parts['basename'];

        // create name for file
        $new_name = apply_filters('ninja_forms_submission_csv_name', 'ninja-forms-submission');

        /*
         * Give this submission its own unique directory so the friendly, shared
         * filename does not collide with a concurrent submission's CSV. The
         * previous behaviour renamed every submission's CSV to one fixed path,
         * so two overlapping submissions could attach each other's data (#8200).
         * The unique directory is derived from the already-unique tempnam basename.
         */
        $unique_dir = $dir . '/' . $basename . '.d';
        if (! is_dir($unique_dir)) {
            mkdir($unique_dir, 0755, true);
        }

        $final_path = $unique_dir . '/' . $new_name . '.csv';

        // move the temp file into the unique directory under the friendly name
        rename($path, $final_path);

        // remember the exact paths so cleanup removes this submission's file only
        $this->_csv_path = $final_path;
        $this->_csv_dir = $unique_dir;

        return $final_path;
    }

    /**
     * Function to delete csv file from temp directory after Email Action has completed.
     */
    private function _drop_csv()
    {
        // Remove only this submission's own CSV, tracked when it was created,
        // so a concurrent submission's file is never deleted out from under it (#8200).
        if ('' !== $this->_csv_path && file_exists($this->_csv_path)) {
            unlink($this->_csv_path);
        }

        if ('' !== $this->_csv_dir && is_dir($this->_csv_dir)) {
            @rmdir($this->_csv_dir);
        }

        $this->_csv_path = '';
        $this->_csv_dir = '';
    }

    /*
     * Backwards Compatibility
     */

    private function _backwards_compatibility()
    {
        add_filter('ninja_forms_sub_csv_delimiter',        array($this, 'ninja_forms_sub_csv_delimiter'), 10, 1);
        add_filter('ninja_sub_csv_enclosure',              array($this, 'ninja_sub_csv_enclosure'), 10, 1);
        add_filter('ninja_sub_csv_terminator',             array($this, 'ninja_sub_csv_terminator'), 10, 1);
        add_filter('ninja_forms_action_email_attachments', array($this, 'ninja_forms_action_email_attachments'), 10, 3);
    }

    public function ninja_forms_sub_csv_delimiter($delimiter)
    {
        return apply_filters('nf_sub_csv_delimiter', $delimiter);
    }

    public function ninja_sub_csv_enclosure($enclosure)
    {
        return apply_filters('nf_sub_csv_enclosure', $enclosure);
    }

    public function ninja_sub_csv_terminator($terminator)
    {
        return apply_filters('nf_sub_csv_terminator', $terminator);
    }

    public function ninja_forms_action_email_attachments($attachments, $form_data, $action_settings)
    {
        return apply_filters('nf_email_notification_attachments', $attachments, $action_settings['id']);
    }

    private function format_plain_text_message($message)
    {
        $message =  str_replace(array('<table>', '</table>', '<tr><td>', ''), '', $message);
        $message =  str_replace('</td><td>', ' ', $message);
        $message =  str_replace('</td></tr>', "\r\n", $message);
        /*
         * Submitted values arrive here escaped, so that a `<` survives the
         * merge tag instead of truncating the submission (#7279). Decode after
         * the markup is removed, so a plain text recipient reads the
         * characters that were typed rather than entity codes such as `&lt;`.
         */
        return html_entity_decode(strip_tags($message), ENT_QUOTES, 'UTF-8');
    }
}
