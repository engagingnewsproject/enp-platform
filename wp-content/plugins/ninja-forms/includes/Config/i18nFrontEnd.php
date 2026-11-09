<?php if ( ! defined( 'ABSPATH' ) ) exit;
global $wp_locale;

$date_format = !empty(Ninja_Forms()->get_setting( 'date_format' )) ? Ninja_Forms()->get_setting( 'date_format' ) : get_option( 'date_format' );

return apply_filters( 'ninja_forms_i18n_front_end', array(

    'ninjaForms'                            => esc_html__( 'Ninja Forms', 'ninja-forms' ),
    'changeEmailErrorMsg'                   => esc_html__( 'Please enter a valid email address.', 'ninja-forms' ),
    'changeDateErrorMsg'                    => esc_html__( 'Please enter a valid date!', 'ninja-forms' ),
    'confirmFieldErrorMsg'                  => esc_html__( 'These fields must match!', 'ninja-forms' ),
    /* translators: Error message shown to a site visitor when the number entered is below the minimum allowed. */
    'fieldNumberNumMinError'                => esc_html__( 'Please enter a number that is not below the minimum.', 'ninja-forms' ),
    /* translators: Error message shown to a site visitor when the number entered is above the maximum allowed. */
    'fieldNumberNumMaxError'                => esc_html__( 'Please enter a number that is not above the maximum.', 'ninja-forms' ),
    /* translators: %s is a number: the step between the values the field accepts. */
    'fieldNumberIncrementBy'                => esc_html__( 'Please increment by %s', 'ninja-forms' ),
    'fieldTextareaRTEInsertLink'            => esc_html__( 'Insert Link', 'ninja-forms' ),
    'fieldTextareaRTEInsertMedia'           => esc_html__( 'Insert Media', 'ninja-forms' ),
    'fieldTextareaRTESelectAFile'           => esc_html__( 'Select a file', 'ninja-forms' ),
    'fieldsTermsAdd'                        => esc_html__( 'Add New', 'ninja-forms' ),
    'fieldsTermsNewName'                    => esc_html__( 'New term name', 'ninja-forms' ),
    'tableInsertRowAbove'                   => esc_html__( 'Insert Row Above', 'ninja-forms' ),
    'tableInsertRowBelow'                   => esc_html__( 'Insert Row Below', 'ninja-forms' ),
    'tableInsertColumnLeft'                 => esc_html__( 'Insert Column Left', 'ninja-forms' ),
    'tableInsertColumnRight'                => esc_html__( 'Insert Column Right', 'ninja-forms' ),
    'tableDeleteRow'                        => esc_html__( 'Delete Row', 'ninja-forms' ),
    'tableDeleteColumn'                     => esc_html__( 'Delete Column', 'ninja-forms' ),
    'tableDeleteTable'                      => esc_html__( 'Delete Table', 'ninja-forms' ),
    'tableLegacyNotice'                     => esc_html__( 'This content has tables in the old editor format. Custom styles are preserved until converted. Editing will update to the new format.', 'ninja-forms' ),
    'tableLegacyConvert'                    => esc_html__( 'Convert Now', 'ninja-forms' ),
    'tableInsertTable'                      => esc_html__( 'Insert Table', 'ninja-forms' ),
    'dismiss'                               => esc_html__( 'Dismiss', 'ninja-forms' ),
    'insertLink'                            => esc_html__( 'Insert Link', 'ninja-forms' ),
    'formErrorsCorrectErrors'               => esc_html__( 'Please correct errors before submitting this form.', 'ninja-forms' ),
    // Shown when a submit or save request fails without a readable reply:
    // a dropped connection, or a server error page instead of data.
    // See issue #8167.
    'formErrorsSubmissionFailed'            => esc_html__( "Something went wrong and your form wasn't sent. Please check your connection and try again.", 'ninja-forms' ),
    'formHoneypot'                          => esc_html__( 'If you are a human seeing this field, please leave it empty.', 'ninja-forms' ),
    // Shared with the server-side required check in
    // includes/Abstracts/Field.php::get_required_field_message(). One
    // required-field condition, one translatable string. See issue #8182.
    'validateRequiredField'                 => esc_html__( 'This is a required field.', 'ninja-forms' ),
    /* translators: Error message shown to a site visitor when the hidden anti-spam field (honeypot) was filled in. */
    'honeypotHoneypotError'                 => esc_html__( 'This form could not be submitted. Please reload the page and try again.', 'ninja-forms' ),
    'fileUploadOldCodeFileUploadInProgress' => esc_html__( 'File Upload in Progress.', 'ninja-forms' ),
    'fileUploadOldCodeFileUpload'           => esc_html__( 'File Upload', 'ninja-forms' ),
    'currencySymbol'                        => Ninja_Forms()->get_setting( 'currency_symbol' ),
    'fieldsMarkedRequired'                  => sprintf( esc_html__( 'Fields marked with an %s*%s are required', 'ninja-forms' ), '<span class="ninja-forms-req-symbol">', '</span>' ),
    'thousands_sep'                         => $wp_locale->number_format[ 'thousands_sep' ],
    'decimal_point'                         => $wp_locale->number_format[ 'decimal_point' ],
    'siteLocale'                            => get_locale(),
    'dateFormat'                            => $date_format,
    // The verbose format option is the only one that spells out month
    // and day names, so its word order follows the site's language
    // rather than a hardcoded English one. See issue #3308.
    'longDateFormat'                        => WPN_Helper::get_long_date_format(),
    'startOfWeek'                           => get_option( 'start_of_week' ),
    /* translators: Counter under a text field. 1: characters or words remaining. 2: the limit. It is followed by text the site owner sets, by default "characters left". */
    'of'                                    => esc_html__( '%1$s of %2$s', 'ninja-forms' ),
    'previousMonth'                         => esc_html__( 'Previous Month', 'ninja-forms' ),
    'nextMonth'                             => esc_html__( 'Next Month', 'ninja-forms' ),
    'months'                                => array(
                                                esc_html__( 'January', 'ninja-forms' ),
                                                esc_html__( 'February', 'ninja-forms' ),
                                                esc_html__( 'March', 'ninja-forms' ),
                                                esc_html__( 'April', 'ninja-forms' ),
                                                esc_html__( 'May', 'ninja-forms' ),
                                                esc_html__( 'June', 'ninja-forms' ),
                                                esc_html__( 'July', 'ninja-forms' ),
                                                esc_html__( 'August', 'ninja-forms' ),
                                                esc_html__( 'September', 'ninja-forms' ),
                                                esc_html__( 'October', 'ninja-forms' ),
                                                esc_html__( 'November', 'ninja-forms' ),
                                                esc_html__( 'December', 'ninja-forms' )
                                            ),
    'monthsShort'                           => array(
                                                esc_html_x( 'Jan', 'January abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Feb', 'February abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Mar', 'March abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Apr', 'April abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'May', 'May abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Jun', 'June abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Jul', 'July abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Aug', 'August abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Sep', 'September abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Oct', 'October abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Nov', 'November abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Dec', 'December abbreviation', 'ninja-forms' ),
                                            ),
    'weekdays'                              => array(
                                                esc_html__( 'Sunday', 'ninja-forms' ),
                                                esc_html__( 'Monday', 'ninja-forms' ),
                                                esc_html__( 'Tuesday', 'ninja-forms' ),
                                                esc_html__( 'Wednesday', 'ninja-forms' ),
                                                esc_html__( 'Thursday', 'ninja-forms' ),
                                                esc_html__( 'Friday', 'ninja-forms' ),
                                                esc_html__( 'Saturday', 'ninja-forms' ),
                                            ),
    'weekdaysShort'                         => array(
                                                esc_html_x( 'Sun', 'Sunday abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Mon', 'Monday abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Tue', 'Tuesday abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Wed', 'Wednesday abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Thu', 'Thursday abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Fri', 'Friday abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Sat', 'Saturday abbreviation', 'ninja-forms' ),
                                            ),
    'weekdaysMin'                           => array(//DEPRECATED format in the new date field
                                                esc_html_x( 'Su', 'Sunday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Mo', 'Monday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Tu', 'Tuesday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'We', 'Wednesday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Th', 'Thursday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Fr', 'Friday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Sa', 'Saturday, two-letter abbreviation', 'ninja-forms' )
                                            ),
    'recaptchaConsentMissing'               =>  esc_html__( 'reCAPTCHA validation couldn\'t load.', 'ninja-forms' ),
    'recaptchaMissingCookie'                =>  esc_html__( "reCaptcha v3 validation couldn't load the cookie needed to submit the form.", 'ninja-forms' ),
    'recaptchaConsentEvent'                 =>  esc_html__( 'Accept reCAPTCHA cookies before sending the form.', 'ninja-forms' ),
    'hourPlaceholder'                       => esc_html__( 'Hour', 'ninja-forms' ),
    'minutePlaceholder'                     => esc_html__( 'Minute', 'ninja-forms' ),
    'am'                                    => esc_html__( 'AM', 'ninja-forms' ),
    'pm'                                    => esc_html__( 'PM', 'ninja-forms' ),
    'paypalCancelled'                       =>  esc_html__( 'The PayPal payment was cancelled. Your form has not been sent — you can approve the payment to finish.', 'ninja-forms' ),
    'paypalError'                           =>  esc_html__( "PayPal couldn't complete your payment. You have not been charged. Please try again.", 'ninja-forms' ),
    // PayPal loaded and then offered this buyer nothing — a method the
    // merchant enabled but PayPal will not honour here, most often Venmo
    // outside the US. Without this the visitor waits for buttons that are
    // never coming.
    'paypalNoMethods'                       =>  esc_html__( 'No PayPal payment method is available for this payment. Your form has not been sent.', 'ninja-forms' ),
    // Only ever rendered against a service with no PayPal credentials,
    // which is the walkthrough's case. It says so plainly so that a real
    // site that somehow reached this state cannot mistake it for checkout.
    'paypalTestApprove'                     =>  esc_html__( 'Approve test payment', 'ninja-forms' ),
    // Spoken when the submission halts for payment. Without it the form simply
    // stops, and a visitor who cannot see the new control has nothing telling
    // them why or what to do.
    'paypalApprovalNeeded'                  =>  esc_html__( 'Almost done! Select your preferred payment option below to complete your payment.', 'ninja-forms' ),
    'paypalApprovalRegion'                  =>  esc_html__( 'PayPal payment approval', 'ninja-forms' ),
));
