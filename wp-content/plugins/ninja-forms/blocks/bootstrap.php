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
        'api_version' => 3,
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
        'api_version' => 3,
        'editor_script' => 'ninja-forms/submissions-table/block',
        'render_callback' => function ($attributes, $content) {
            if (isset($attributes['formID']) && $attributes['formID']) {

                // SECURITY: For non-published posts (draft previews, pending review, etc.),
                // require submission-viewing capability before issuing a token.
                // This prevents Contributor/Author users from obtaining tokens by adding
                // a submissions-table block to a draft post and previewing it.
                //
                // For published pages, tokens are issued to all viewers because:
                // 1. The allowed_block_types_all filter prevents unauthorized users from inserting this block
                // 2. The content_save_pre filter strips unauthorized blocks and protects formID
                // 3. Therefore, any block present on a published page was authorized
                //
                // See Issue #8013 for security model details.
                $current_post = get_post();
                if ( $current_post && 'publish' !== $current_post->post_status ) {
                    $views_capability = apply_filters(
                        'ninja_forms_views_token_capability',
                        apply_filters( 'ninja_forms_admin_submissions_capabilities', 'manage_options' )
                    );
                    if ( ! current_user_can( $views_capability ) ) {
                        return '';
                    }
                }

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
 * Helper: Get the capability required for submissions table block access
 *
 * @return string The capability name
 */
function nf_security_get_views_capability() {
    return apply_filters(
        'ninja_forms_views_token_capability',
        apply_filters( 'ninja_forms_admin_submissions_capabilities', 'manage_options' )
    );
}

/**
 * Helper: Strip all submissions-table blocks from content
 *
 * @param string $content The post content
 * @return string Content with submissions-table blocks removed
 */
function nf_security_strip_submissions_blocks( $content ) {
    $blocks = parse_blocks( $content );
    $filtered = nf_security_remove_submissions_blocks( $blocks );
    return serialize_blocks( $filtered );
}

/**
 * Helper: Remove submissions-table blocks from a blocks array
 *
 * @param array $blocks Array of parsed blocks
 * @return array Filtered blocks array
 */
function nf_security_remove_submissions_blocks( $blocks ) {
    return array_values( array_filter( $blocks, function( $block ) {
        return $block['blockName'] !== 'ninja-forms/submissions-table';
    } ) );
}

/**
 * Helper: Extract submissions-table blocks from a blocks array
 *
 * @param array $blocks Array of parsed blocks
 * @return array Array of submissions-table blocks with original indices preserved
 */
function nf_security_extract_submissions_blocks( $blocks ) {
    $result = [];
    foreach ( $blocks as $index => $block ) {
        if ( $block['blockName'] === 'ninja-forms/submissions-table' ) {
            $result[] = $block;
        }
    }
    return $result;
}

/**
 * Helper: Process blocks for unauthorized save
 *
 * - Preserves existing submissions-table blocks with their original formID
 * - Strips any newly-added submissions-table blocks
 *
 * @param array $new_blocks Blocks from new content being saved
 * @param array $original_submissions Original submissions-table blocks to preserve
 * @return array Processed blocks array
 */
function nf_security_process_blocks_for_unauthorized_save( $new_blocks, $original_submissions ) {
    $original_count = count( $original_submissions );
    $found_submissions = 0;
    $processed_blocks = [];

    foreach ( $new_blocks as $block ) {
        if ( $block['blockName'] === 'ninja-forms/submissions-table' ) {
            // Check if this corresponds to an original block
            if ( $found_submissions < $original_count ) {
                // Preserve the block but restore original formID
                $original_block = $original_submissions[ $found_submissions ];

                // Keep the new block's other attributes, but restore original formID
                if ( isset( $original_block['attrs']['formID'] ) ) {
                    $block['attrs']['formID'] = $original_block['attrs']['formID'];
                }

                $processed_blocks[] = $block;
                $found_submissions++;
            }
            // If beyond original count, this is a NEW block - skip it (don't add to processed)
        } else {
            // Non-submissions block - keep as-is
            $processed_blocks[] = $block;
        }
    }

    return $processed_blocks;
}

/**
 * Layer 1: Block Inserter Filter
 *
 * Hide ninja-forms/submissions-table from the block inserter for users
 * who lack the required capability.
 *
 * @since 3.8.21
 */
add_filter( 'allowed_block_types_all', function( $allowed_block_types, $editor_context ) {
    $views_capability = nf_security_get_views_capability();

    // Authorized users see all blocks
    if ( current_user_can( $views_capability ) ) {
        return $allowed_block_types;
    }

    // Unauthorized user - filter out submissions-table block
    if ( is_array( $allowed_block_types ) ) {
        return array_values( array_filter( $allowed_block_types, function( $block ) {
            return $block !== 'ninja-forms/submissions-table';
        } ) );
    }

    // If true (all blocks allowed), convert to array excluding submissions-table
    if ( $allowed_block_types === true ) {
        $all_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        return array_values( array_filter( array_keys( $all_blocks ), function( $block ) {
            return $block !== 'ninja-forms/submissions-table';
        } ) );
    }

    return $allowed_block_types;
}, 10, 2 );

/**
 * Layer 2: Content Save Filter
 *
 * For unauthorized users:
 * - Strip any newly-added submissions-table blocks
 * - Preserve existing submissions-table blocks with their original formID
 *
 * This implements the "Protect the Future, Preserve the Past" principle:
 * existing configurations continue working, but new unauthorized changes are blocked.
 *
 * @since 3.8.21
 */
add_filter( 'content_save_pre', function( $content ) {
    // Skip if content is empty or not a string
    if ( empty( $content ) || ! is_string( $content ) ) {
        return $content;
    }

    // Skip if no submissions-table blocks in new content
    if ( strpos( $content, 'ninja-forms/submissions-table' ) === false ) {
        return $content;
    }

    $views_capability = nf_security_get_views_capability();

    // Authorized users can save any content
    if ( current_user_can( $views_capability ) ) {
        return $content;
    }

    // Determine post ID from various sources
    $post_id = 0;
    if ( isset( $_POST['post_ID'] ) ) {
        $post_id = absint( $_POST['post_ID'] );
    } elseif ( isset( $_POST['id'] ) ) {
        // REST API uses 'id'
        $post_id = absint( $_POST['id'] );
    }

    // New post with no ID - strip all submissions-table blocks
    if ( ! $post_id ) {
        return nf_security_strip_submissions_blocks( $content );
    }

    // Get original post content
    $original_post = get_post( $post_id );
    if ( ! $original_post || empty( $original_post->post_content ) ) {
        return nf_security_strip_submissions_blocks( $content );
    }

    // Extract original submissions-table blocks
    $original_blocks = parse_blocks( $original_post->post_content );
    $original_submissions = nf_security_extract_submissions_blocks( $original_blocks );

    // If no original submissions blocks, strip all from new content
    if ( empty( $original_submissions ) ) {
        return nf_security_strip_submissions_blocks( $content );
    }

    // Parse new content and process blocks
    $new_blocks = parse_blocks( $content );
    $processed_blocks = nf_security_process_blocks_for_unauthorized_save( $new_blocks, $original_submissions );

    return serialize_blocks( $processed_blocks );
}, 10, 1 );

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
    // SECURITY: Only users with appropriate capability can receive tokens for viewing submissions
    // This prevents Contributors/Authors from accessing form submission data via the REST API
    //
    // Uses ninja_forms_admin_submissions_capabilities filter for consistency with Submissions menu
    // Additional filter ninja_forms_views_token_capability allows specific customization for Views API
    $views_capability = apply_filters(
        'ninja_forms_views_token_capability',
        apply_filters( 'ninja_forms_admin_submissions_capabilities', 'manage_options' )
    );

    if ( current_user_can( $views_capability ) ) {
        $token = NinjaForms\Blocks\Authentication\TokenFactory::make();
        $publicKey = NinjaForms\Blocks\Authentication\KeyFactory::make();
        $allFormIds = array_map(function($form) { return absint($form['formID']); }, $forms);

        wp_localize_script('ninja-forms/submissions-table/block', 'ninjaFormsViews', [
            'token' => $token->create($publicKey, $allFormIds),
        ]);
    }
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

        // If user is logged in and has appropriate capability, allow access
        // This provides fallback for admin users
        // Uses same capability filter as token generation for consistency
        $views_capability = apply_filters(
            'ninja_forms_views_token_capability',
            apply_filters( 'ninja_forms_admin_submissions_capabilities', 'manage_options' )
        );

        if (is_user_logged_in() && current_user_can($views_capability)) {
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

            // If user has appropriate capability, return all forms
            $views_capability = apply_filters(
                'ninja_forms_views_token_capability',
                apply_filters( 'ninja_forms_admin_submissions_capabilities', 'manage_options' )
            );

            if (is_user_logged_in() && current_user_can($views_capability)) {
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
     * Generates a new token scoped to the same form ID as the previous token.
     * Used for automatic token refresh when tokens expire or after secret rotation.
     *
     * SECURITY: Requires the old token to be provided. This ensures:
     * - Only tokens that were legitimately issued can be refreshed
     * - Tokens can only be refreshed for the same form ID
     * - No reliance on spoofable Referer headers
     */
    register_rest_route('ninja-forms-views', 'token/refresh', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            $tokenValidator = NinjaForms\Blocks\Authentication\TokenFactory::make();

            // SECURITY: Require the old token for refresh
            // This prevents attackers from generating tokens without having a legitimate one first
            $oldToken = $request->get_header('X-NinjaFormsViews-Auth');
            if (!$oldToken) {
                return new WP_Error(
                    'missing_token',
                    __('A valid token is required for refresh. Include the current token in X-NinjaFormsViews-Auth header.', 'ninja-forms'),
                    array('status' => 401)
                );
            }

            // Validate the old token's signature (allows expired tokens for refresh)
            // This ensures the token was legitimately issued by this site
            if (!$tokenValidator->validateSignatureOnly($oldToken)) {
                return new WP_Error(
                    'invalid_token',
                    __('The provided token is invalid or has been tampered with.', 'ninja-forms'),
                    array('status' => 403)
                );
            }

            // Extract form IDs from the old token - these are the only forms allowed for refresh
            $authorizedFormIds = $tokenValidator->getFormIds($oldToken);
            if ($authorizedFormIds === false || empty($authorizedFormIds)) {
                return new WP_Error(
                    'invalid_token_payload',
                    __('Could not extract form authorization from token.', 'ninja-forms'),
                    array('status' => 403)
                );
            }

            // Get the requested form ID (optional - defaults to first form in old token)
            $formId = $request->get_param('formID');

            // Check for legacy formIds parameter for backward compatibility
            if (!$formId && $request->get_param('formIds')) {
                $formIds = $request->get_param('formIds');
                if (is_array($formIds) && !empty($formIds)) {
                    $formId = $formIds[0];
                }
            }

            // If no form ID specified, use the first (and typically only) form from old token
            if (!$formId) {
                $formId = $authorizedFormIds[0];
            }

            // Sanitize form ID
            $formId = absint($formId);

            if (!$formId) {
                return new WP_Error(
                    'invalid_form_id',
                    __('Valid form ID is required', 'ninja-forms'),
                    array('status' => 400)
                );
            }

            // SECURITY: Verify the requested form ID was in the old token
            // This prevents upgrading a single-form token to access other forms
            if (!in_array($formId, array_map('intval', $authorizedFormIds), true)) {
                return new WP_Error(
                    'unauthorized_form_access',
                    __('The requested form was not authorized in your original token.', 'ninja-forms'),
                    array('status' => 403)
                );
            }

            // Validate that the form still exists
            $form = Ninja_Forms()->form($formId)->get();
            if (!$form) {
                return new WP_Error(
                    'form_not_found',
                    __('The requested form does not exist', 'ninja-forms'),
                    array('status' => 404)
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
                'formID' => $formId,
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

            return true; // Rate-limited, but token validation happens in callback
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
