<?php

if ( apply_filters( 'ninja_forms_disable_marketing', false ) ) return array();

return apply_filters( 'ninja_forms_available_actions', array(

    /**
     * User & Submission Management
     */

     'login-user'       => array(
        'group'             => 'management',
        'name'              => 'login-user',
        'nicename'          => 'Login User',
        'link'              => 'https://ninjaforms.com/extensions/user-management/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=User+Management+Ghost+Action',
        'plugin_path'       => 'ninja-forms-user-management/ninja-forms-user-management.php',
    ),

    'register-user'       => array(
        'group'             => 'management',
        'name'              => 'register-user',
        'nicename'          => 'Register User',
        'link'              => 'https://ninjaforms.com/extensions/user-management/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=User+Management+Ghost+Action',
        'plugin_path'       => 'ninja-forms-user-management/ninja-forms-user-management.php',
    ),

    'update-profile'       => array(
        'group'             => 'management',
        'name'              => 'update-profile',
        'nicename'          => 'Update Profile',
        'link'              => 'https://ninjaforms.com/extensions/user-management/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=User+Management+Ghost+Action',
        'plugin_path'       => 'ninja-forms-user-management/ninja-forms-user-management.php',
    ),

    'createposts'           => array(
        'group'             => 'management',
        'name'              => 'createposts',
        'nicename'          => 'Create Post',
        'link'              => 'https://ninjaforms.com/extensions/front-end-posting/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Front-End+Posting+Ghost+Action',
        'plugin_path'       => 'ninja-forms-post-creation/ninja-forms-post-creation.php',
    ),

    /**
     * Accept Payments & Donations
     */

    'paypal-checkout'       => array(
        'group'             => 'payments',
        'name'              => 'paypal-checkout',
        'nicename'          => 'PayPal Checkout',
        'link'              => 'https://ninjaforms.com/extensions/paypal-checkout/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=PayPal+Checkout+Ghost+Action',
        'plugin_path'       => 'ninja-forms-paypal/ninja-forms-paypal.php',
    ),

    'stripe'                => array(
        'group'             => 'payments',
        'name'              => 'stripe',
        'nicename'          => 'Stripe',
        'link'              => 'https://ninjaforms.com/extensions/stripe/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Stripe+Ghost+Action',
        'plugin_path'       => 'ninja-forms-stripe/ninja-forms-stripe.php',
    ),

    'authorize-net'         => array(
        'group'             => 'payments',
        'name'              => 'authorize-net',
        'nicename'          => 'Authorize.net',
        'link'              => 'https://ninjaforms.com/extensions/authorize-net/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Authorize+Ghost+Action',
        'plugin_path'       => 'ninja-forms-authorize-net/ninja-forms-authorize-net.php',
    ),

    'elavon'                => array(
        'group'             => 'payments',
        'name'              => 'elavon',
        'nicename'          => 'Elavon',
        'link'              => 'https://ninjaforms.com/extensions/elavon/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Elavon+Ghost+Action',
        'plugin_path'       => 'ninja-forms-elavon-payment-gateway/ninja-forms-elavon-payment-gateway.php',
    ),

    'recurly'               => array(
        'group'             => 'payments',
        'name'              => 'recurly',
        'nicename'          => 'Recurly',
        'link'              => 'https://ninjaforms.com/extensions/recurly/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Recurly+Ghost+Action',
        'plugin_path'       => 'ninja-forms-recurly/ninja-forms-recurly.php',
    ),

    /**
     * Automation
     */

    'zapier'                => array(
        'group'             => 'automation',
        'name'              => 'zapier',
        'nicename'          => 'Zapier',
        'link'              => 'https://ninjaforms.com/extensions/zapier/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Zapier+Ghost+Action',
        'plugin_path'       => 'ninja-forms-zapier/ninja-forms-zapier.php',
    ),

    'webhooks'              => array(
        'group'             => 'automation',
        'name'              => 'webhooks',
        'nicename'          => 'Webhooks',
        'link'              => 'https://ninjaforms.com/extensions/webhooks/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=WebHooks+Ghost+Action',
        'plugin_path'       => 'ninja-forms-webhooks/ninja-forms-webhooks.php',
    ),

    /**
     * Email Marketing
     */

    'active-campaign'       => array(
        'group'             => 'marketing',
        'name'              => 'active-campaign',
        'nicename'          => 'ActiveCampaign',
        'link'              => 'https://ninjaforms.com/extensions/active-campaign/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Active+Campaign+Ghost+Action',
        'plugin_path'       => 'ninja-forms-active-campaign/ninja-forms-active-campaign.php',
    ),

    'aweber'                => array(
        'group'             => 'marketing',
        'name'              => 'aweber',
        'nicename'          => 'AWeber',
        'link'              => 'https://ninjaforms.com/extensions/aweber/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=AWeber+Ghost+Action',
        'plugin_path'       => 'ninja-forms-aweber/ninja-forms-aweber.php',
    ),

    'campaignmonitor'       => array(
        'group'             => 'marketing',
        'name'              => 'campaignmonitor',
        'nicename'          => 'Campaign Monitor',
        'link'              => 'https://ninjaforms.com/extensions/campaign-monitor/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Campaign+Monitor+Ghost+Action',
        'plugin_path'       => 'ninja-forms-campaign-monitor/ninja-forms-campaign-monitor.php',
    ),

    'cleverreach'           => array(
        'group'             => 'marketing',
        'name'              => 'cleverreach',
        'nicename'          => 'CleverReach',
        'link'              => 'https://ninjaforms.com/extensions/cleverreach/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=CleverReach+Ghost+Action',
        'plugin_path'       => 'ninja-forms-cleverreach/ninja-forms-cleverreach.php',
    ),

    'constantcontact'       => array(
        'group'             => 'marketing',
        'name'              => 'constantcontact',
        'nicename'          => 'Constant Contact',
        'link'              => 'https://ninjaforms.com/extensions/constant-contact/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Constant+Contact+Ghost+Action',
        'plugin_path'       => 'ninja-forms-constant-contact/ninja-forms-constant-contact.php',
    ),

    'convertkit'            => array(
        'group'             => 'marketing',
        'name'              => 'convertkit',
        'nicename'          => 'ConvertKit',
        'link'              => 'https://ninjaforms.com/extensions/convertkit/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=ConvertKit+Ghost+Action',
        'plugin_path'       => 'ninja-forms-convertkit/ninja-forms-convertkit.php',
    ),

    'email_octopus'         => array(
        'group'             => 'marketing',
        'name'              => 'email_octopus',
        'nicename'          => 'EmailOctopus',
        'link'              => 'https://ninjaforms.com/extensions/emailoctopus/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=EmailOctopus+Ghost+Action',
        'plugin_path'       => 'ninja-forms-emailoctopus/ninja-forms-emailoctopus.php',
    ),

    'emma'                  => array(
        'group'             => 'marketing',
        'name'              => 'emma',
        'nicename'          => 'Emma',
        'link'              => 'https://ninjaforms.com/extensions/emma/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Emma+Ghost+Action',
        'plugin_path'       => 'ninja-forms-emma/ninja-forms-emma.php',
    ),

    'mailchimp'            => array(
       'group'             => 'marketing',
       'name'              => 'mailchimp',
       'nicename'          => 'Mailchimp',
       'link'              => 'https://ninjaforms.com/extensions/mailchimp/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=MailChimp+Ghost+Action',
       'plugin_path'       => 'ninja-forms-mailchimp/ninja-forms-mail-chimp.php',
   ),

   'mailpoet-signup'      => array(
      'group'             => 'marketing',
      'name'              => 'mailpoet-signup',
      'nicename'          => 'MailPoet',
      'link'              => 'https://ninjaforms.com/extensions/mailpoet/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=MailPoet+Ghost+Action',
      'plugin_path'       => 'ninja-forms-mailpoet/nf-mailpoet.php',
    ),

    /**
     * CRMs
     */

    'capsule-crm'           => array(
        'group'             => 'crms',
        'name'              => 'capsule-crm',
        'nicename'          => 'Capsule CRM',
        'link'              => 'https://ninjaforms.com/extensions/capsule-crm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Capsule+Ghost+Action',
        'plugin_path'       => 'ninja-forms-capsule-crm/ninja-forms-capsule-crm.php',
    ),

    'civi-crm'              => array(
        'group'             => 'crms',
        'name'              => 'nfcivicrmcreatecontact',
        'nicename'          => 'CiviCRM',
        'link'              => 'https://ninjaforms.com/extensions/civicrm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=CiviCRM+Ghost+Action',
        'plugin_path'       => 'ninja-forms-civicrm/ninja-forms-civicrm.php',
    ),

    'hubspot'               => array(
        'group'             => 'crms',
        'name'              => 'hubspot',
        'nicename'          => 'HubSpot',
        'link'              => 'https://ninjaforms.com/extensions/hubspot/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=HubSpot+Ghost+Action',
        'plugin_path'       => 'ninja-forms-hubspot/ninja-forms-hubspot.php',
    ),

    'insightly-crm'         => array(
        'group'             => 'crms',
        'name'              => 'insightly-crm',
        'nicename'          => 'Insightly CRM',
        'link'              => 'https://ninjaforms.com/extensions/insightly-crm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Insightly+Ghost+Action',
        'plugin_path'       => 'ninja-forms-insightly-crm/ninja-forms-insightly-crm.php',
    ),

    'onepage-crm'           => array(
        'group'             => 'crms',
        'name'              => 'addtoonepage',
        'nicename'          => 'OnePageCRM',
        'link'              => 'https://ninjaforms.com/extensions/onepage-crm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=OnePage+Ghost+Action',
        'plugin_path'       => 'ninja-forms-onepagecrm/ninja-forms-onepage-crm.php',
    ),

    'pipelinedeals-crm'     => array(
        'group'             => 'crms',
        'name'              => 'pipelinedeals-crm',
        'nicename'          => 'Pipeline CRM',
        'link'              => 'https://ninjaforms.com/extensions/pipelinedeals-crm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Pipeline+Ghost+Action',
        'plugin_path'       => 'ninja-forms-pipeline-deals-crm/ninja-forms-pipeline-crm.php',
    ),

    'salesforce-crm'        => array(
        'group'             => 'crms',
        'name'              => 'salesforce-crm',
        'nicename'          => 'Salesforce',
        'link'              => 'https://ninjaforms.com/extensions/salesforce-crm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Salesforce+Ghost+Action',
        'plugin_path'       => 'ninja-forms-salesforce-crm/ninja-forms-salesforce-crm.php',
    ),

    'zoho-crm'              => array(
        'group'             => 'crms',
        'name'              => 'zoho-crm',
        'nicename'          => 'ZohoCRM',
        'link'              => 'https://ninjaforms.com/extensions/zoho-crm/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=ZohoCRM+Ghost+Action',
        'plugin_path'       => 'ninja-forms-zoho-crm/ninja-forms-zoho-crm.php',
    ),

    /** 
     * Notifications & Workflow
     */

    'clicksend_sms'         => array(
        'group'             => 'notifications',
        'name'              => 'clicksend_sms',
        'nicename'          => 'ClickSend SMS',
        'link'              => 'https://ninjaforms.com/extensions/clicksend-sms/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=ClickSend+SMS+Ghost+Action',
        'plugin_path'       => 'ninja-forms-clicksend/ninja-forms-clicksend.php',
    ),

    'helpscout'             => array(
        'group'             => 'notifications',
        'name'              => 'helpscout',
        'nicename'          => 'Help Scout',
        'link'              => 'https://ninjaforms.com/extensions/help-scout/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Help+Scout+Ghost+Action',
        'plugin_path'       => 'ninja-forms-helpscout/ninja-forms-helpscout.php',
    ),

    'slack'                 => array(
        'group'             => 'notifications',
        'name'              => 'slack',
        'nicename'          => 'Slack',
        'link'              => 'https://ninjaforms.com/extensions/slack/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Slack+Ghost+Action',
        'plugin_path'       => 'ninja-forms-slack/ninja-forms-slack.php',
    ),

    'trello'                => array(
        'group'             => 'notifications',
        'name'              => 'trello',
        'nicename'          => 'Trello',
        'link'              => 'https://ninjaforms.com/extensions/trello/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Trello+Ghost+Action',
        'plugin_path'       => 'ninja-forms-trello/ninja-forms-trello.php',
    ),

    'twilio_sms'            => array(
        'group'             => 'notifications',
        'name'              => 'twilio_sms',
        'nicename'          => 'Twilio SMS',
        'link'              => 'https://ninjaforms.com/extensions/twilio-sms/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Action&utm_content=Twilio+SMS+Ghost+Action',
        'plugin_path'       => 'ninja-forms-twilio/ninja-forms-twilio.php',
    ),
) );
