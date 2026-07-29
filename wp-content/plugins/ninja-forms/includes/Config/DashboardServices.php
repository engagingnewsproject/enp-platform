<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_services', [
    'sendwp' => [
        'name' => 'SendWP',
        'slug' => 'sendwp',
        'installPath' => 'sendwp/sendwp.php',
        'description' =>  __('SendWP is a business-grade email service and close partner of Ninja Forms dedicated to making sure all of your WordPress email is delivered every time. We send millions of emails every year and maintain a 99.5% deliverability rate! With SendWP, say goodbye to email support headaches. We have a team of email professionals to handle any issues that may arise.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://sendwp.com/',
    ],
    'siren' => [
        'name' => 'Siren Affiliates',
        'slug' => 'siren',
        'description' => __('Siren turns Ninja Forms submissions into a referral engine. Tie a collaborator to any form so a submission credits them as a lead, or add product fields to trigger sales and commissions. Run affiliate, refer-a-friend, or nonprofit donation programs.|||Siren pairs with Ninja Forms, so you can launch one for free.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://www.sirenaffiliates.com/integrations/ninja-forms?utm_source=ninjaforms&utm_medium=partner&utm_campaign=ninja-forms-integration&utm_content=plugin-card',
    ],
    'wpml' => [
        'name' => 'WPML',
        'slug' => 'wpml',
        'description' => __('WPML makes it easy to build and run multilingual WordPress websites. Translate pages, posts, custom types, taxonomies, menus, and more with a combination of automatic and human translations. Choose what to translate, who will translate it, and target languages from one dashboard. WPML is fully compatible with and a committed partner of Ninja Forms.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://wpml.org/plugin/ninja-forms/',
    ],
    'elementor' => [
        'name' => 'Elementor',
        'slug' => 'elementor',
        'description' => __('Elementor is a leading WordPress website builder and a valued Ninja Forms partner. Drop any Ninja Form into your Elementor designs and style the page around it with full creative control.|||Your forms fit seamlessly into landing pages, contact pages, and anything else you build, with no code required.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://ninjaforms.com/blog/how-to-add-ninja-forms-to-elementor/?utm_source=Ninja+Forms+Plugin&utm_medium=Partner+Apps&utm_campaign=Add-ons&utm_content=Elementor',
    ],
    'codeable' => [
        'name' => 'Codeable',
        'slug' => 'codeable',
        'description' => __('Codeable matches customers in need of custom WordPress solutions to professional WordPress experts. Match your needs with hand-picked WordPress and Ninja Forms specialists for any project size or type. Codeable\'s Ninja Forms experts excel at plugin and theme customization, custom integrations, and much more.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://www.codeable.io/partners/ninja-forms/?ref=nVHqb',
    ],
    'paypal' => [
        'name' => 'PayPal',
        'slug' => 'paypal',
        'description' => __('PayPal is a trusted payment partner of Ninja Forms. Collect payments, donations, and order totals right from your forms, backed by the security and global reach of the world\'s most recognized payment brand.|||Add PayPal Checkout to accept cards and PayPal balances in minutes, with no separate merchant account to set up.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://ninjaforms.com/extensions/paypal-checkout/?utm_source=Ninja+Forms+Plugin&utm_medium=Partner+Apps&utm_campaign=Add-ons&utm_content=PayPal',
    ],
    'omnisend' => [
        'name' => 'Omnisend',
        'slug' => 'omnisend',
        'description' => __('Omnisend\'s Ninja Forms Add-on connects Ninja Forms to Omnisend, automatically sending form data and contact information to Omnisend. This makes it simple to segment your contacts and send them personalized emails. Compatible with the Omnisend for WooCommerce plugin.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://wordpress.org/plugins/omnisend-for-ninja-forms-add-on/',
    ],
    'hostarmada' => [
        'name' => 'HostArmada',
        'slug' => 'hostarmada',
        'description' => __('HostArmada is a valued partner of Ninja Forms, delivering top-tier web hosting solutions that enhance your website\'s performance and reliability. Their services include powerful security features, blazing-fast speeds, and 24/7 expert support, ensuring your Ninja Forms function flawlessly.|||Enjoy seamless integration and an optimal environment for your forms.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://hostarmada.com/',
    ]
]);
