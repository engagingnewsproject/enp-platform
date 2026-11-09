<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_i18n_dashboard', array(

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
    'weekdaysMin'                           => array(
                                                esc_html_x( 'Su', 'Sunday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Mo', 'Monday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Tu', 'Tuesday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'We', 'Wednesday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Th', 'Thursday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Fr', 'Friday, two-letter abbreviation', 'ninja-forms' ),
                                                esc_html_x( 'Sa', 'Saturday, two-letter abbreviation', 'ninja-forms' )
                                            ),
    /* translators: %s is the title of the form. */
    'deleteWarningA'                        => esc_html__( 'You are about to delete the form %s.', 'ninja-forms' ),
    'deleteWarningB'                        => esc_html__( 'Once deleted, its fields and submissions cannot be recovered. Proceed with caution.',
                                                   'ninja-forms' ),
    'deleteConfirmA'                        => esc_html__( 'Type', 'ninja-forms' ),
    /* translators: %s is the word DELETE. It is not translated, because the code compares what the user types with it. */
    'deleteConfirmB'                        => esc_html__( 'Type %s to confirm', 'ninja-forms' ),
    'delete'                                => esc_html__( 'Delete', 'ninja-forms' ),
    'cancel'                                => esc_html__( 'Cancel', 'ninja-forms' ),
    'deleteTitle'                           => esc_html__( 'Confirm Delete', 'ninja-forms' ),
    'deleteXForm'                           => esc_html__( 'Export Form', 'ninja-forms' ),
    'deleteXSubs'                           => esc_html__( 'Export Submissions', 'ninja-forms' ),
    'optinContent'                          => '<p>' . esc_html__( 'We would like to collect data about how Ninja Forms is used so that we can improve the experience for everyone. This data will not include ANY submission data or personally identifiable information.', 'ninja-forms' ) . '</p><p>'
                                                /* translators: 1 and 2: opening and closing tag of the link. */
                                                . sprintf( esc_html__( 'Please check out our %1$sprivacy policy%2$s for additional clarification.', 'ninja-forms' ), '<a href="https://ninjaforms.com/privacy-policy/" target="_blank">', '</a>' ) . '</p>',
    'optinYesplease'                        => esc_html__( 'Yes, please send me occasional emails about Ninja Forms.', 'ninja-forms' ),
    'optinSecondary'                        => esc_html__( 'Not Now', 'ninja-forms' ),
    'optinPrimary'                          => esc_html__( 'Yes, I agree!', 'ninja-forms' ),
    'optinAwesome'                          => esc_html__( 'Keep being awesome!', 'ninja-forms' ),
    'optinThanks'                           => esc_html__( 'Thank you for opting in!', 'ninja-forms' ),
    'cleanupContent'                        => '<p>' . esc_html__( 'Once we begin this process, it might take several minutes to complete.', 'ninja-forms' ) . '</p><p>'
                                                . esc_html__( 'Navigating away from this page before it is finished could lead to unexpected results.', 'ninja-forms' ) . '</p><p>'
                                                . esc_html__( 'Please confirm when you are ready to begin.', 'ninja-forms' ) . '</p>',
    'cleanupSecondary'                      => esc_html__( 'Cancel', 'ninja-forms' ),
    'cleanupPrimary'                        => sprintf( esc_html__( 'Clean up my data', 'ninja-forms' ) ),
    'cleanupLoading'                        => esc_html__( 'Processing...', 'ninja-forms' ),
    'noResult'                              => esc_html__( 'No Results Found.', 'ninja-forms' ),
    'importingForm'                         => esc_html__( 'Importing...', 'ninja-forms' ),

    /**
     * Services Tab
     */

    /** OAuth Controller */
    'oauthDisconnectContent'               => sprintf( esc_html__( 'Disconnecting from my.ninjaforms.com will disrupt the functionality of all services. To manage your service subscriptions please visit %smy.ninjaforms.com%s', 'ninja-forms' ), '<a href="https://my.ninjaforms.com">', '</a>' ),
    'oauthDisconnectConfirm'               => esc_html__( 'Disconnect', 'ninja-forms' ),
    'oauthDisconnectCancle'                => esc_html__( 'Stay Connected', 'ninja-forms' ),
    'oauthLearnMoreContent'                => '<p>' . sprintf(
        /* translators: 1 and 2: opening and closing tag of the link. */
        esc_html__( 'Since you’re using one of our Ninja Forms services, like Ninja Mail or our Add-on Manager, your site is connected to my.ninjaforms.com. This allows us to send data between your site and my.ninjaforms.com. For details about what is being shared, you can see our %1$sPrivacy Policy%2$s.', 'ninja-forms' ),
        '<a href="https://ninjaforms.com/privacy-policy/">',
        '</a>'
    ) . '</p>',

    /** Service Model */
    'serviceRedirect'                     => '<p style="text-align:center;">' . esc_html__( 'Redirecting to NinjaForms.com', 'ninja-forms' ) . '</p>',
    'serviceUpdateError'                  => esc_html__( 'Unable to update the service.' , 'ninja-forms' ),

    /** AI Form Builder tile */
    'aiStages'                            => array(
                                                esc_html__( 'Reading your description', 'ninja-forms' ),
                                                esc_html__( 'Choosing the right fields', 'ninja-forms' ),
                                                esc_html__( 'Designing your form', 'ninja-forms' ),
                                                esc_html__( 'Building your form', 'ninja-forms' ),
                                            ),
    'aiError'                             => esc_html__( 'Something went wrong. Please try again.', 'ninja-forms' ),
    'aiProviderChoose'                    => esc_html__( 'Choose a model', 'ninja-forms' ),
    'aiProviderRequired'                  => esc_html__( 'Choose an AI model before generating your form.', 'ninja-forms' ),
    'aiShowAllModels'                     => esc_html__( 'Show all compatible models', 'ninja-forms' ),
    /* translators: 1: current character count, 2: maximum character count. */
    'aiPromptCount'                       => esc_html__( '%1$s / %2$s characters', 'ninja-forms' ),
    /* translators: 1: characters over the limit, 2: maximum character count. */
    'aiPromptOverLimit'                   => esc_html__( 'Description exceeds the %2$s-character limit by %1$s. Shorten it to continue.', 'ninja-forms' ),
    'aiFormReady'                         => esc_html__( 'Form ready — opening the builder…', 'ninja-forms' ),
    'aiFormReadyNotice'                   => esc_html__( 'Your form is ready.', 'ninja-forms' ),
    'aiOpenBuilder'                       => esc_html__( 'Open it in the builder', 'ninja-forms' ),
    'aiDismiss'                           => esc_attr__( 'Dismiss', 'ninja-forms' ),
));
