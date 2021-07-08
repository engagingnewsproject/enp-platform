<?php

if (!defined('ABSPATH'))
    exit;

use NF_Exports_BulkSubmissionEmailParameters as BulkSubmissionEmailParameters;

/**
 * Email exported submissions as an attachment
 */
class NF_Exports_BulkSubmissionEmail {

    /**
     * Bulk Submission Email Parameters
     * @var BulkSubmissionEmailParameters 
     */
    protected $bulkSubmissionEmailParameters;

    /**
     * Comma delineated `To` addresses
     * @var string
     */
    protected $toAddresses;

    /**
     * `From` address
     * @var string
     */
    protected $fromAddress;

    /**
     * `Reply To` address
     * @var string
     */
    protected $replyTo;

    /**
     * `Subject`
     * @var string
     */
    protected $subject;

    /**
     * CSV string content, stored in array for multiple attachments
     * @var array
     */
    protected $content;

    /**
     *
     * @var resource
     */
    protected $tempFile;

    /**
     * Directory of final file location
     * @var string
     */
    protected $dir;

    /**
     * Temp file name at time of upload, before renaming
     * @var string
     */
    protected $basename;

    /**
     * Full file name with path as attached to email
     * @var string
     */
    protected $attachmentFilename;

    /**
     * Instantiated with BulkSubmissionEmailParameters and string CSV content
     * 
     * @param BulkSubmissionEmailParameters $bulkSubmissionEmailParameters
     * @param array $attachmentFilenames Array of string filenames ready for attachment
     */
    public function __construct($bulkSubmissionEmailParameters, array $attachmentFilenames) {
        $this->bulkSubmissionEmailParameters = $bulkSubmissionEmailParameters;
        $this->attachmentFilename = $attachmentFilenames;
        $this->setDefaults();
    }

    /**
     * Set default properties
     */
    protected function setDefaults() {
        // set upload director to /uploads
        $dir = wp_upload_dir();
        $this->uploadDir = $dir['path'];
    }

    /**
     * Generate email, attach content, submit email
     */
    public function handle() {
        $this->sanitizeAddressFields();

        $headers = $this->getHeaders();

        $attachments = $this->getAttachments();

        $message = apply_filters('ninja_forms_action_email_message', $this->bulkSubmissionEmailParameters->getEmailSubject());

        try {

            $sent = wp_mail($this->toAddresses, strip_tags($this->bulkSubmissionEmailParameters->getEmailSubject()), $message, $headers, $attachments);
        } catch (Exception $e) {

            $sent = false;
        }
    }

    /**
     * Put every email address through a sanitizing method
     */
    protected function sanitizeAddressFields() {
        $incomingToAddresses = $this->bulkSubmissionEmailParameters->getEmailTo();

        $emailAddresses = explode(',', $incomingToAddresses);

        // Loop over our email addresses.
        foreach ($emailAddresses as $email) {


            $sanitized = $this->sanitizeEmail($email);

            // Build our array of the email addresses.
            $sanitizedArray[] = $sanitized;
        }
        $this->toAddresses = implode(',', $sanitizedArray);

        // Sanitized our array of settings.

        $this->fromAddress = $this->sanitizeEmail($this->bulkSubmissionEmailParameters->getEmailFrom());

        $this->replyTo = $this->bulkSubmissionEmailParameters->getEmailReplyTo();
    }

    /**
     * Sanitize a given email address
     * 
     * @param string $incoming
     * @return string
     */
    protected function sanitizeEmail($incoming) {

        // Trim values in case there is a value with spaces/tabs/etc to remove whitespace
        $trimmed = trim($incoming);

        if (empty($trimmed)) {
            return '';
        }

        $matches = [];
        if (false !== strpos($trimmed, '<') && false !== strpos($trimmed, '>')) {
            preg_match('/(?:<)([^>]*)(?:>)/', $trimmed, $matches);

            $return = $matches[1];
        } else {

            $return = $trimmed;
        }

        // skip if email is invalid
        if (!is_email($return)) {
            return '';
        }

        return $return;
    }

    /**
     * Construct and return header array
     * 
     * Note that variable headers are run through sanitize_header method
     * @return array
     */
    private function getHeaders() {
        $contentHeaders = [];

        $contentHeaders[] = 'Content-Type: text/html';
        $contentHeaders[] = 'charset=UTF-8';
        $contentHeaders[] = 'X-Ninja-Forms:ninja-forms'; // Flag for transactional email.

        $contentHeaders[] = $this->formatAddress('from', $this->fromAddress);

        $headers = array_merge($contentHeaders, $this->constructRecipientsHeader());

        return $headers;
    }

    /**
     * Sanitize header to prevent attacker is able to create new headers using charecter encoding.
     *
     * @param string $header
     * @return void
     */
	protected  function sanitize_header($header){
        return preg_replace( '=((<CR>|<LF>|0x0A/%0A|0x0D/%0D|\\n|\\r)\S).*=i', null, $header );
	}

    /**
     * Construct and return attachments
     * @return array
     */
    private function getAttachments() {

        $attachments = $this->attachmentFilename;

        return $attachments;
    }

    /**
     * Format Reply-To, CC, and BCC address header
     * @return array
     */
    private function constructRecipientsHeader() {
        $headers = [];

        // Could include `cc` and `bcc` in future
        $recipientParameters = array(
            'Reply-to' => $this->bulkSubmissionEmailParameters->getEmailReplyTo(),
        );

        foreach ($recipientParameters as $type => $email) {

            if (!$email) {
                continue;
            }

            $headers[] = $this->formatAddress($type, $email);
        }

        return $headers;
    }

    /**
     * Format address for header
     * 
     * @param string $type
     * @param string $email
     * @param string $name
     * @return string
     */
    private function formatAddress($type, $email, $name = '') {
        $formattedType = ucfirst($type);

        if (!$name) {
            $name = $email;
        }
        $recipient = "$formattedType: $name <$email>";

        return $this->sanitize_header($recipient);
    }

}
