<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_services', [
    'sendwp' => [
        'name' => 'SendWP',
        'slug' => 'sendwp',
        'installPath' => 'sendwp/sendwp.php',
        'description' =>  esc_html__('SendWP is a business-grade email service and close partner of Ninja Forms dedicated to making sure all of your WordPress email is delivered every time. We send millions of emails every year and maintain a 99.5% deliverability rate! With SendWP, say goodbye to email support headaches. We have a team of email professionals to handle any issues that may arise.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://sendwp.com/',
    ],
    'codeable' => [
        'name' => 'Codeable',
        'slug' => 'codeable',
        'description' => esc_html__('Codeable matches customers in need of custom WordPress solutions to professional WordPress experts. Match your needs with hand-picked WordPress and Ninja Forms specialists for any project size or type. Codeable’s Ninja Forms experts excel at plugin and theme customization, custom integrations, and much more.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://www.codeable.io/partners/ninja-forms/?ref=nVHqb',
    ],
    'wpml' => [
        'name' => 'WPML',
        'slug' => 'wpml',
        'description' => esc_html__('WPML makes it easy to build and run multilingual WordPress websites. Translate pages, posts, custom types, taxonomies, menus, and more with a combination of automatic and human translations. Choose what to translate, who will translate it, and target languages from one dashboard. WPML is fully compatible with and a committed partner of Ninja Forms.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://wpml.org/plugin/ninja-forms/',
    ],
    'omnisend' => [
        'name' => 'Omnisend',
        'slug' => 'omnisend',
        'description' => esc_html__('Omnisend’s Ninja Forms Add-on connects Ninja Forms to Omnisend, automatically sending form data and contact information to Omnisend. This makes it simple to segment your contacts and send them personalized emails. Compatible with the Omnisend for WooCommerce plugin.', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://wordpress.org/plugins/omnisend-for-ninja-forms-add-on/',
    ],
    'hostarmada' => [
        'name' => 'HostArmada',
        'slug' => 'hostarmada',
        'description' => esc_html__('HostArmada is a valued partner of Ninja Forms, delivering top-tier web hosting solutions that enhance your website’s performance and reliability. Their services include powerful security features, blazing-fast speeds, and 24/7 expert support, ensuring your Ninja Forms function flawlessly. With HostArmada, enjoy seamless integration and an optimal environment for your forms. Elevate your web experience with HostArmada!', 'ninja-forms'),
        'enabled' => null,
        'learnMore' => 'https://hostarmada.com/',
    ]
]);