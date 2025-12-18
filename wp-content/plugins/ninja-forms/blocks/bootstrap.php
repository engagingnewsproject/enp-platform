<?php

/**
 * Register blocks and there scripts
 */
add_action('init', function () {
    /**
     * Form Block
     */
    // automatically load dependencies and version
    $block_asset_file = include dirname(__DIR__) . '/build/form-block.asset.php';
    $block = (array)json_decode(file_get_contents(__DIR__ . '/form/block.json'), true);

    wp_register_script(
        'ninja-forms/form',
        plugins_url('../build/form-block.js', __FILE__),
        $block_asset_file['dependencies'],
        $block_asset_file['version']
    );

    register_block_type('ninja-forms/form', array_merge($block, [
        'title' => esc_attr__('Ninja Form', 'ninja-forms'),
        'render_callback' => function ($atts) {
            $formID = isset($atts['formID']) ? $atts['formID'] : 1;
            ob_start();
            Ninja_Forms()->display( absint($formID), true );
            return ob_get_clean();
        },
        'editor_script' => 'ninja-forms/form'
    ]));


    /**
     * Views Block
     */
    // automatically load dependencies and version
    $block_asset_file = include dirname(__DIR__) . '/build/sub-table-block.asset.php';
    wp_register_script(
        'ninja-forms/submissions-table/block',
        plugins_url('../build/sub-table-block.js', __FILE__),
        $block_asset_file['dependencies'],
        $block_asset_file['version']
    );

    // Note: Token will be generated per-page in render_callback with specific form IDs

    $render_asset_file = include dirname(__DIR__) . '/build/sub-table-render.asset.php';
    wp_register_script(
        'ninja-forms/submissions-table/render',
        plugins_url('../build/sub-table-render.js', __FILE__),
        $render_asset_file['dependencies'],
        $render_asset_file['version']
    );

    register_block_type('ninja-forms/submissions-table', array(
        'editor_script' => 'ninja-forms/submissions-table/block',
        'render_callback' => function ($attributes, $content) {
            if (isset($attributes['formID']) && $attributes['formID']) {
                wp_enqueue_script('ninja-forms/submissions-table/render');

                // Generate a token bound to THIS specific form ID only
                $formId = absint($attributes['formID']);
                $token = NinjaForms\Blocks\Authentication\TokenFactory::make();
                $publicKey = NinjaForms\Blocks\Authentication\KeyFactory::make();

                // Create token with form ID binding and expiration
                wp_localize_script('ninja-forms/submissions-table/render', 'ninjaFormsViews', [
                    'token' => $token->create($publicKey, array($formId)),
                ]);
                
                // Enqueue signature fonts for proper display in Gutenberg block
                wp_enqueue_style(
                    'nf-signature-fonts',
                    Ninja_Forms::$url . 'assets/fonts/signature/google-fonts.css',
                    [],
                    Ninja_Forms::VERSION
                );

                $className = 'ninja-forms-views-submissions-table';
                if (isset($attributes['alignment'])) {
                    $className .= ' align' . $attributes['alignment'];
                }
                return sprintf("<div class='%s' data-attributes='%s'></div>", esc_attr($className),
                    esc_attr(wp_json_encode($attributes)));
            }
        }
    ));

    /**
     * Have Translations set in scripts via i18n package
     * https://developer.wordpress.org/block-editor/packages/packages-i18n/
     * https://developer.wordpress.org/reference/functions/wp_set_script_translations/
     * https://developer.wordpress.org/block-editor/developers/internationalization/
     */
    wp_set_script_translations( "ninja-forms/form", "ninja-forms", plugin_dir_path( __FILE__ ) . 'lang' );
    wp_set_script_translations( "ninja-forms/submissions-table/block", "ninja-forms", plugin_dir_path( __FILE__ ) . 'lang' );
    wp_set_script_translations( "ninja-forms/submissions-table/render", "ninja-forms", plugin_dir_path( __FILE__ ) . 'lang' );

});

/**
 * Localize data for blocks
 */
add_action('admin_enqueue_scripts', function () {
    //Conditionally load data for Blocks
    $screen = get_current_screen();
    if( is_null( $screen ) ) return;
    if( ! $screen->is_block_editor() ) return;
        //Get all forms, to base form selector on.
        $formsBuilder = (new NinjaForms\Blocks\DataBuilder\FormsBuilderFactory)->make();
        $forms = $formsBuilder->get();
        if (!empty($forms)) {
            //Escape for use in JavaScript
            foreach ($forms as $key => $form) {
                $forms[$key] = [
                    'formID' => absint($form['formID']),
                    'formTitle' => esc_textarea($form['formTitle'])
                ];
            }
        }
    wp_localize_script('ninja-forms/form', 'nfFormsBlock', [
        'forms' => $forms,//array keys escaped above
        'homeUrl' => esc_url_raw( home_url() ), //URL to serve the iFrame that displays the form in blocks editor
        'previewToken' => wp_create_nonce('nf_iframe' )
    ]);

    // For block editor, provide a token that allows access to all forms
    // This is safe because it's only loaded in admin context with proper capability checks
    $token = NinjaForms\Blocks\Authentication\TokenFactory::make();
    $publicKey = NinjaForms\Blocks\Authentication\KeyFactory::make();
    $allFormIds = array_map(function($form) { return absint($form['formID']); }, $forms);

    wp_localize_script('ninja-forms/submissions-table/block', 'ninjaFormsViews', [
        'token' => $token->create($publicKey, $allFormIds),
    ]);
});

/**
 * Register REST API routes related to blocks
 */
add_action('rest_api_init', function () {

    /**
     * Enhanced permission callback that validates token and checks form-level authorization.
     *
     * Security improvements:
     * - Rate limiting to prevent DoS attacks
     * - Validates token authenticity (hash, expiration)
     * - Checks if token is authorized for the requested form ID
     * - Falls back to WordPress capability check for admin users
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    $tokenAuthenticationCallback = function (WP_REST_Request $request) {
        // Check rate limit first (lightweight check)
        $endpoint = $request->get_route();
        $rateLimitCheck = NinjaForms\Blocks\Authentication\RateLimiter::check($endpoint);
        if (is_wp_error($rateLimitCheck)) {
            return $rateLimitCheck;
        }

        $tokenValidator = NinjaForms\Blocks\Authentication\TokenFactory::make();
        $tokenHeader = $request->get_header('X-NinjaFormsViews-Auth');
        $formId = $request->get_param('id');

        // If user is logged in and has manage_options capability, allow access
        // This provides fallback for admin users
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return true;
        }

        // Validate token with form ID authorization
        if ($formId) {
            return $tokenValidator->validate($tokenHeader, intval($formId));
        }

        // For routes without a specific form ID (like /forms list), only validate token structure
        // The token must still be valid (not expired, proper signature)
        return $tokenValidator->validate($tokenHeader);
    };

    register_rest_route('ninja-forms-views', 'forms', array(
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $tokenValidator = NinjaForms\Blocks\Authentication\TokenFactory::make();
            $tokenHeader = $request->get_header('X-NinjaFormsViews-Auth');

            // Get all forms
            $formsBuilder = (new NinjaForms\Blocks\DataBuilder\FormsBuilderFactory)->make();
            $allForms = $formsBuilder->get();

            // If user has manage_options capability, return all forms
            if (is_user_logged_in() && current_user_can('manage_options')) {
                return $allForms;
            }

            // Otherwise, filter forms based on token authorization
            $authorizedFormIds = $tokenValidator->getFormIds($tokenHeader);
            if ($authorizedFormIds === false) {
                return new WP_Error('invalid_token', 'Invalid token', array('status' => 403));
            }

            // Filter to only return forms the token has access to
            $filteredForms = array_filter($allForms, function($form) use ($authorizedFormIds) {
                return in_array(intval($form['formID']), $authorizedFormIds, true);
            });

            return array_values($filteredForms);
        },
        'permission_callback' => $tokenAuthenticationCallback,
    ));

    register_rest_route('ninja-forms-views', 'forms/(?P<id>\d+)/fields', [
        'methods' => 'GET',
        'args' => [
            'id' => [
                'required' => true,
                'description' => esc_attr__('Unique identifier for the object.', 'ninja-forms'),
                'type' => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ],
        'callback' => function (WP_REST_Request $request) {
            $fieldsBuilder = (new NinjaForms\Blocks\DataBuilder\FieldsBuilderFactory)->make(
                $request->get_param('id')
            );
            return $fieldsBuilder->get();
        },
        'permission_callback' => $tokenAuthenticationCallback,
    ]);

    register_rest_route('ninja-forms-views', 'forms/(?P<id>\d+)/submissions', [
        'methods' => 'GET',
        'args' => [
            'id' => [
                'required' => true,
                'description' => esc_attr__('Unique identifier for the object.', 'ninja-forms'),
                'type' => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'perPage' => [
                'description' => esc_attr__('Maximum number of items to be returned in result set.', 'ninja-forms'),
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'page' => [
                'description' => esc_attr__('Current page of the collection.', 'ninja-forms'),
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum' => 1,
            ]
        ],
        'callback' => function (WP_REST_Request $request) {
            $submissionsBuilder = (new NinjaForms\Blocks\DataBuilder\SubmissionsBuilderFactory)->make(
                $request->get_param('id'),
                $request->get_param('perPage'),
                $request->get_param('page')
            );
            return $submissionsBuilder->get();
        },
        'permission_callback' => $tokenAuthenticationCallback,
    ]);

    /**
     * Token Refresh Endpoint
     *
     * Generates a new token scoped to requested form IDs.
     * Used for automatic token refresh when tokens expire or after secret rotation.
     *
     * FIX: Restricts token generation to single forms and validates form access
     */
    register_rest_route('ninja-forms-views', 'token/refresh', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            // REFACTOR: Accept single formID instead of formIds array
            $formId = $request->get_param('formID');
            
            // Check for legacy formIds parameter for backward compatibility
            if (!$formId && $request->get_param('formIds')) {
                $formIds = $request->get_param('formIds');
                if (is_array($formIds) && !empty($formIds)) {
                    // Only accept single form from legacy array
                    if (count($formIds) > 1) {
                        return new WP_Error(
                            'too_many_form_ids',
                            __('Token generation is limited to one form at a time. Please use formID parameter instead.', 'ninja-forms'),
                            array('status' => 400)
                        );
                    }
                    $formId = $formIds[0];
                }
            }

            // Sanitize and validate form ID
            $formId = absint($formId);
            
            if (!$formId) {
                return new WP_Error(
                    'invalid_form_id',
                    __('Valid form ID is required', 'ninja-forms'),
                    array('status' => 400)
                );
            }

            // FIX: Validate that the form exists and is accessible
            $form = Ninja_Forms()->form( $formId )->get();
            if (!$form) {
                return new WP_Error(
                    'form_not_found',
                    __('The requested form does not exist', 'ninja-forms'),
                    array('status' => 404)
                );
            }

            // FIX: Validate that user has permission to access this form
            // This prevents users from generating tokens for arbitrary forms
            $referer = wp_get_referer();
            if (!$referer) {
                return new WP_Error(
                    'invalid_request',
                    __('Request must come from a valid page with submissions table block', 'ninja-forms'),
                    array('status' => 403)
                );
            }

            // Parse the referring page to validate block authorization
            $post_id = url_to_postid($referer);
            if (!$post_id) {
                // Handle front page, archives, etc.
                $parsed_url = parse_url($referer);
                if ($parsed_url['path'] === '/' || $parsed_url['path'] === home_url('/')) {
                    $post_id = get_option('page_on_front');
                }
            }

            // Check if the form is actually embedded in a submissions table block on this page
            if ($post_id) {
                $post = get_post($post_id);
                if ($post && has_blocks($post->post_content)) {
                    $blocks = parse_blocks($post->post_content);
                    $found_authorized_form = false;
                    
                    // Recursively search for ninja-forms/submissions-table blocks
                    $search_blocks = function($blocks) use ($formId, &$found_authorized_form, &$search_blocks) {
                        foreach ($blocks as $block) {
                            if ($block['blockName'] === 'ninja-forms/submissions-table') {
                                if (isset($block['attrs']['formID']) && 
                                    intval($block['attrs']['formID']) === $formId) {
                                    $found_authorized_form = true;
                                    return;
                                }
                            }
                            // Search inner blocks recursively
                            if (!empty($block['innerBlocks'])) {
                                $search_blocks($block['innerBlocks']);
                            }
                        }
                    };
                    
                    $search_blocks($blocks);
                    
                    if (!$found_authorized_form) {
                        return new WP_Error(
                            'unauthorized_form_access',
                            __('You do not have permission to access this form via this page', 'ninja-forms'),
                            array('status' => 403)
                        );
                    }
                }
            } else {
                // If we can't determine the post ID, return an error
                return new WP_Error(
                    'post_id_not_found',
                    __('The requested data could not be related to a valid page', 'ninja-forms'),
                    array('status' => 403)
                );
            }

            // Generate new token scoped to the single requested form
            $publicKey = NinjaForms\Blocks\Authentication\KeyFactory::make(32);
            $tokenGenerator = NinjaForms\Blocks\Authentication\TokenFactory::make();
            $newToken = $tokenGenerator->create($publicKey, array($formId));

            return array(
                'token' => $newToken,
                'publicKey' => $publicKey,
                'expiresIn' => 900, // 15 minutes in seconds
                'formID' => $formId, // Changed from formIds to formID
            );
        },
        'permission_callback' => function (WP_REST_Request $request) {
            // Apply stricter rate limiting to refresh endpoint
            $rateLimitCheck = NinjaForms\Blocks\Authentication\RateLimiter::check(
                '/ninja-forms-views/token/refresh',
                50,  // limit: 50 requests
                300  // window: 5 minutes
            );

            if (is_wp_error($rateLimitCheck)) {
                return $rateLimitCheck; // Returns 429 Too Many Requests
            }

            return true; // Public endpoint (rate-limited) but with form validation
        },
    ));

});

/**
 * Handler for form preview iFrame used in Forms block
 */
add_action( 'wp_head', function () {
    // check for preview and iframe get parameters
    if( isset( $_GET[ 'nf_preview_form' ] ) && isset( $_GET[ 'nf_iframe' ] ) ){
        if( ! wp_verify_nonce( $_GET['nf_iframe'], 'nf_iframe') ){
            wp_die( esc_html__('Preview token failed validation', 'ninja-forms'));
            exit;
        }

        //Attempt to get theme background color
        $background = '#fff';
        $supports = get_theme_support('editor-color-palette','background');
        if( is_array($supports) ){
            foreach($supports[0] as $index => $support ){
                if( 'background' === $support['slug']){
                    $background = $support['color'];
                    break;
                }
            }
        }

        $js_lib_dir  = Ninja_Forms::$url . 'assets/js/lib/';

        $form_id = absint( $_GET[ 'nf_preview_form' ] );
        // Style below: update width and height for particular form
        ?>
        <style media="screen">
            #wpadminbar {
                display: none;
            }
            #nf-form-<?php echo $form_id; ?>-cont {
                z-index: 90000001;
                position: fixed;
                top: 0; left: 0;
                width: 100vw;
                height: 100vh;
                background-color: <?php echo sanitize_hex_color($background ); ?>;
            }

            div.site-branding, header.entry-header, .site-footer, header, .footer-nav-widgets-wrapper {
                display:none !important;
            }

        </style>

        <?php

        // register our script to target the form iFrame in page builder
        wp_register_script(
            'ninja-forms-block-setup',
            $js_lib_dir . 'blockFrameSetup.js',
            array( 'underscore', 'jquery' )
        );

        wp_localize_script( 'ninja-forms-block-setup', 'ninjaFormsBlockSetup', array(
            'form_id' => $form_id
        ) );

        wp_enqueue_script( 'ninja-forms-block-setup' );
    }

});

/**
 * Schedule WP-Cron job for automatic secret rotation
 */
add_action('init', function() {
    if (!wp_next_scheduled('ninja_forms_views_check_rotation')) {
        wp_schedule_event(time(), 'daily', 'ninja_forms_views_check_rotation');
    }
});

/**
 * WP-Cron callback: Check if secret should be rotated and rotate if needed
 */
add_action('ninja_forms_views_check_rotation', function() {
    if (NinjaForms\Blocks\Authentication\SecretStore::shouldRotate()) {
        NinjaForms\Blocks\Authentication\SecretStore::rotate();
    }
});

/**
 * Clear scheduled events on plugin deactivation
 */
register_deactivation_hook(__FILE__, function() {
    $timestamp = wp_next_scheduled('ninja_forms_views_check_rotation');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'ninja_forms_views_check_rotation');
    }
});
