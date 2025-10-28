<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class NF_Fields_Signature
 *
 * Provides signature capture functionality with both typed and drawn signature options.
 * This field is intended for collecting acknowledgments, confirmations, and user consent
 * in a visual format. It is NOT designed to create legally binding signatures or meet
 * legal requirements for electronic signatures (e.g., e-signature laws, digital certificates).
 *
 * Features:
 * - Typed signatures: User types their name, displayed with custom fonts
 * - Drawn signatures: User draws signature on a canvas
 * - Method selection: Can be configured for typed-only, drawn-only, or both
 * 
 * Data Storage:
 * - Signatures are stored as JSON with the following structure:
 *   {
 *     "signature_type": "typed" | "drawn",
 *     "typed_name": "John Doe" (for typed),
 *     "signature_font": "dancing-script" (for typed),
 *     "signature_data": "data:image/png;base64,..." (for drawn),
 *     "canvas_dimensions": {"width": 400, "height": 150} (for drawn),
 *     "timestamp": "2024-01-01T12:00:00Z"
 *   }
 * 
 * Export Formats:
 * - CSV: "Method: typed/drawn | Signed: true/false | Value: [name if typed]"
 * - PDF: Text representation (e.g., "John Doe (Typed signature - dancing-script font)")
 * - Email/HTML: Full HTML display with styled text or image
 * 
 * Usage in HTML fields:
 * - Use merge tag format: {field:signature_field_key} or {field:signature_field_id}
 * - In HTML emails: Displays styled text (typed) or base64 image (drawn)
 * - In plain text emails: Shows text representation only
 * - Example: <p>Customer Signature: {field:signature_1}</p>
 * 
 * Repeater Field Support:
 * - Fully supports signature fields within repeater fields
 * - Maintains consistent formatting across all export types
 * 
 * @since 3.x
 */
class NF_Fields_Signature extends NF_Abstracts_Input
{
    protected $_name = 'signature';
    protected $_type = 'signature';
    protected $_section = 'common';
    protected $_icon = 'pencil-square-o';
    protected $_templates = 'signature';
    protected $_test_value = '{"signature_type":"typed","typed_name":"John Doe","signature_font":"dancing-script"}';
    
    // Constants for validation and limits
    const MAX_SIGNATURE_SIZE = 137000; // ~100KB in base64
    const MAX_NAME_LENGTH = 100;
    const VALID_SIGNATURE_TYPES = array( 'drawn', 'typed' );
    const VALID_FONTS = array( 'dancing-script', 'satisfy', 'cursive' );

    protected $_settings = array(
        'signature_method',
        'signature_font',
        'typed_placeholder',
        'drawn_placeholder',
        'canvas_width',
        'canvas_height',
        'pen_color',
        'background_color'
    );

    protected $_settings_all_fields = array(
        'key', 'label', 'label_pos', 'required', 'classes', 'manual_key', 'admin_label', 'help', 'description',
        'signature_method',
        'signature_font',
        'typed_placeholder',
        'drawn_placeholder',
        'canvas_width',
        'canvas_height',
        'pen_color',
        'background_color'
    );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Signature', 'ninja-forms' );

        // Add default field settings
        $this->_settings[ 'label_pos' ][ 'value' ] = 'above';
        $this->_settings[ 'required' ][ 'value' ] = 0;

        // Add filter to localize field settings
        add_filter( 'ninja_forms_localize_field_' . $this->_name, array( $this, 'localize_field_settings' ) );
        
        // Primary filter for merge tag display (handles all signature merge tag conversions)
        add_filter( 'ninja_forms_merge_tag_value_' . $this->_type, array( $this, 'filter_merge_tag_value' ), 10, 2 );
        
        // Add filter for CSV export
        add_filter( 'ninja_forms_subs_export_field_value_' . $this->_type, array( $this, 'filter_csv_export_value' ), 10, 2 );
        
        // Add filter for PDF export
        add_filter( 'ninja_forms_pdf_field_value_' . $this->_type, array( $this, 'filter_pdf_value' ), 10, 2 );
        
        // Add filter for PDF field value display - this is the main handler
        add_filter( 'ninja_forms_pdf_field_value', array( $this, 'filter_pdf_field_display' ), 10, 3 );
        
        // Disable wpautop for signature fields in PDF
        add_filter( 'ninja_forms_pdf_field_value_wpautop', array( $this, 'disable_wpautop_for_signatures' ), 10, 3 );
        
        // Add signature to the list of HTML-safe fields
        add_filter( 'ninja_forms_get_html_safe_fields', array( $this, 'add_to_safe_fields' ), 10, 1 );

        // Add filter for CPT custom columns display
        add_filter( 'ninja_forms_custom_columns', array( $this, 'custom_columns' ), 10, 3 );

    }

    /**
     * Localize field settings for frontend
     */
    public function localize_field_settings( $settings )
    {
        // Ensure signature-specific settings are passed to frontend
        $signature_settings = [
            'signature_method',
            'signature_font',
            'typed_placeholder',
            'drawn_placeholder',
            'canvas_width',
            'canvas_height',
            'pen_color',
            'background_color'
        ];

        foreach ( $signature_settings as $setting ) {
            if ( ! isset( $settings[ $setting ] ) && isset( $this->_settings[ $setting ][ 'value' ] ) ) {
                $settings[ $setting ] = $this->_settings[ $setting ][ 'value' ];
            }
        }
        
        // If there's a value that looks like JSON, process it for display
        if ( isset( $settings['value'] ) && is_string( $settings['value'] ) && strpos( $settings['value'], '{' ) === 0 ) {
            // Store the raw value for potential merge tag processing
            $settings['raw_signature_value'] = $settings['value'];
        }

        return $settings;
    }

    /**
     * Validate field
     */
    public function validate( $field, $data )
    {
        $errors = parent::validate( $field, $data );

        // Get field value
        $value = $field[ 'value' ];

        // If field is required and empty
        if ( 1 == $field[ 'required' ] && empty( $value ) ) {
            $errors[] = esc_html__( 'Please provide a signature', 'ninja-forms' );
            return $errors;
        }

        // If not empty, validate signature data
        if ( ! empty( $value ) ) {
            $signature_data = json_decode( $value, true );
            
            if ( ! is_array( $signature_data ) || ! isset( $signature_data[ 'signature_type' ] ) ) {
                $errors[] = esc_html__( 'Invalid signature data', 'ninja-forms' );
                return $errors;
            }
            
            // Validate signature type
            if ( ! in_array( $signature_data[ 'signature_type' ], self::VALID_SIGNATURE_TYPES ) ) {
                $errors[] = esc_html__( 'Invalid signature type', 'ninja-forms' );
                return $errors;
            }

            // Validate based on signature type
            if ( 'typed' === $signature_data[ 'signature_type' ] ) {
                if ( empty( $signature_data[ 'typed_name' ] ) ) {
                    if ( 1 == $field[ 'required' ] ) {
                        $errors[] = esc_html__( 'Please provide a signature', 'ninja-forms' );
                    } else {
                        $errors[] = esc_html__( 'Please type your name', 'ninja-forms' );
                    }
                }
                
                // Check name length
                if ( isset( $signature_data[ 'typed_name' ] ) && strlen( $signature_data[ 'typed_name' ] ) > self::MAX_NAME_LENGTH ) {
                    $errors[] = esc_html__( 'Name is too long', 'ninja-forms' );
                }
            } elseif ( 'drawn' === $signature_data[ 'signature_type' ] ) {
                if ( empty( $signature_data[ 'signature_data' ] ) || 
                     ! preg_match( '/^data:image\/(png|jpeg);base64,/', $signature_data[ 'signature_data' ] ) ) {
                    if ( 1 == $field[ 'required' ] ) {
                        $errors[] = esc_html__( 'Please provide a signature', 'ninja-forms' );
                    } else {
                        $errors[] = esc_html__( 'Please draw your signature', 'ninja-forms' );
                    }
                }
                
                // Check signature size
                if ( isset( $signature_data[ 'signature_data' ] ) && 
                     strlen( $signature_data[ 'signature_data' ] ) > self::MAX_SIGNATURE_SIZE ) {
                    $errors[] = esc_html__( 'Signature data is too large', 'ninja-forms' );
                }
            }
        }

        return $errors;
    }

    /**
     * Sanitize field value
     */
    public function sanitize_field_value( $value )
    {
        if ( empty( $value ) ) {
            return '';
        }

        // Decode JSON
        $signature_data = json_decode( $value, true );
        
        if ( ! is_array( $signature_data ) ) {
            return '';
        }

        $sanitized = [];

        // Validate and sanitize signature type
        $sanitized[ 'signature_type' ] = in_array( $signature_data[ 'signature_type' ], self::VALID_SIGNATURE_TYPES ) 
            ? $signature_data[ 'signature_type' ] 
            : 'typed';

        // Sanitize based on type
        if ( 'drawn' === $sanitized[ 'signature_type' ] ) {
            // Validate base64 image data
            if ( isset( $signature_data[ 'signature_data' ] ) && 
                 preg_match( '/^data:image\/(png|jpeg);base64,(.+)/', $signature_data[ 'signature_data' ], $matches ) ) {
                // Limit image data size
                if ( strlen( $signature_data[ 'signature_data' ] ) < self::MAX_SIGNATURE_SIZE ) {
                    // Additional validation: verify base64 can be decoded
                    $decoded = base64_decode( $matches[2], true );
                    if ( false !== $decoded && ! empty( $decoded ) ) {
                        $sanitized[ 'signature_data' ] = $signature_data[ 'signature_data' ];
                    }
                }
            }
            
            // Sanitize canvas dimensions
            if ( isset( $signature_data[ 'canvas_dimensions' ] ) && is_array( $signature_data[ 'canvas_dimensions' ] ) ) {
                $width = isset( $signature_data[ 'canvas_dimensions' ][ 'width' ] ) ? 
                    absint( $signature_data[ 'canvas_dimensions' ][ 'width' ] ) : 400;
                $height = isset( $signature_data[ 'canvas_dimensions' ][ 'height' ] ) ? 
                    absint( $signature_data[ 'canvas_dimensions' ][ 'height' ] ) : 150;
                
                // Clamp to reasonable ranges
                $width = max( 100, min( 2000, $width ) );
                $height = max( 50, min( 1000, $height ) );
                
                $sanitized[ 'canvas_dimensions' ] = [
                    'width' => $width,
                    'height' => $height
                ];
            }
        } elseif ( 'typed' === $sanitized[ 'signature_type' ] ) {
            // Sanitize typed name
            if ( isset( $signature_data[ 'typed_name' ] ) ) {
                $sanitized[ 'typed_name' ] = sanitize_text_field( $signature_data[ 'typed_name' ] );
            }
            // Sanitize font
            if ( isset( $signature_data[ 'signature_font' ] ) ) {
                $sanitized[ 'signature_font' ] = in_array( $signature_data[ 'signature_font' ], self::VALID_FONTS )
                    ? $signature_data[ 'signature_font' ]
                    : 'dancing-script';
            }
        }

        // Add metadata
        if ( isset( $signature_data[ 'timestamp' ] ) ) {
            // Validate timestamp format (ISO 8601 or Unix timestamp)
            $timestamp = $signature_data[ 'timestamp' ];
            if ( is_numeric( $timestamp ) || preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $timestamp ) ) {
                $sanitized[ 'timestamp' ] = sanitize_text_field( $timestamp );
            }
        }

        return wp_json_encode( $sanitized );
    }

    /**
     * Format value for display in the legacy admin interface
     * 
     * Note: This method is used by the old WordPress admin submission view interface
     * (WordPress Admin → Ninja Forms → Submissions → View individual submission).
     * The modern submission management interface uses a React component instead,
     * but this method is kept for backwards compatibility.
     * 
     * @param array $field Field data
     * @param string $value Field value (JSON string)
     * @return string HTML formatted signature for display
     */
    public function admin_form_element( $field, $value )
    {
        if ( empty( $value ) ) {
            return '<div style="color: #999; font-style: italic;">' . esc_html__( 'Not signed', 'ninja-forms' ) . '</div>';
        }

        // Decode HTML entities if present
        $decoded_value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
        $signature_data = json_decode( $decoded_value, true );

        // Try with stripslashes if needed
        if ( ! is_array( $signature_data ) && is_string( $value ) ) {
            $signature_data = json_decode( stripslashes( $value ), true );
        }

        // If still not valid, try original value
        if ( ! is_array( $signature_data ) ) {
            $signature_data = json_decode( $value, true );
        }

        if ( ! is_array( $signature_data ) || ! isset( $signature_data['signature_type'] ) ) {
            return '<div style="color: #999; font-style: italic;">' . esc_html__( 'Invalid signature data', 'ninja-forms' ) . '</div>';
        }

        $output = '';
        $method = $signature_data['signature_type'];

        // Display typed signature
        if ( 'typed' === $method && isset( $signature_data['typed_name'] ) && ! empty( $signature_data['typed_name'] ) ) {
            $font = isset( $signature_data['signature_font'] ) ? $signature_data['signature_font'] : 'dancing-script';
            $font_family = $this->get_signature_font_family( $font );

            $output .= '<div style="margin-bottom: 15px;">';
            $output .= '<div style="font-family: ' . esc_attr( $font_family ) . '; font-size: 32px; color: #000; padding: 15px; background: transparent; display: inline-block;">';
            $output .= esc_html( $signature_data['typed_name'] );
            $output .= '</div>';
            $output .= '<div style="margin-top: 8px; font-size: 12px; color: #666;">';
            $output .= '<strong>' . esc_html__( 'Type:', 'ninja-forms' ) . '</strong> ' . esc_html__( 'Typed signature', 'ninja-forms' );
            $output .= '</div>';
            $output .= '</div>';
        }
        // Display drawn signature
        elseif ( 'drawn' === $method && isset( $signature_data['signature_data'] ) && ! empty( $signature_data['signature_data'] ) ) {
            if ( preg_match( '/^data:image\/(png|jpeg);base64,/', $signature_data['signature_data'] ) ) {
                $output .= '<div style="margin-bottom: 15px;">';
                $output .= '<img src="' . esc_attr( $signature_data['signature_data'] ) . '" ';
                $output .= 'alt="' . esc_attr__( 'Signature', 'ninja-forms' ) . '" ';
                $output .= 'style="max-width: 400px; height: auto; display: block;" />';
                $output .= '<div style="margin-top: 8px; font-size: 12px; color: #666;">';
                $output .= '<strong>' . esc_html__( 'Type:', 'ninja-forms' ) . '</strong> ' . esc_html__( 'Drawn signature', 'ninja-forms' );
                $output .= '</div>';
                $output .= '</div>';
            } else {
                return '<div style="color: #999; font-style: italic;">' . esc_html__( 'Invalid signature image data', 'ninja-forms' ) . '</div>';
            }
        }

        // Add non-editable notice
        $output .= '<div style="font-size: 12px; color: #666; font-style: italic; margin-top: 8px;">';
        $output .= esc_html__( 'Signatures are not editable', 'ninja-forms' );
        $output .= '</div>';

        return $output;
    }


    /**
     * Filter merge tag value for display in various contexts
     *
     * This method handles signature display for:
     * - HTML fields (via merge tags)
     * - Email notifications
     * - PDF documents
     * - Any other merge tag usage
     *
     * @param mixed $value The field value
     * @param array $field The field data (may contain additional field settings)
     * @return string Formatted value for display
     */
    public function filter_merge_tag_value( $value, $field )
    {
        if ( empty( $value ) ) {
            return '';
        }

        // Handle HTML entity-encoded JSON (common in email contexts)
        if ( is_string( $value ) && ( strpos( $value, '&quot;' ) !== false || strpos( $value, '&amp;' ) !== false ) ) {
            $value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }

        $signature_data = json_decode( $value, true );

        if ( ! is_array( $signature_data ) ) {
            return '';
        }

        // Check if we need plain text output (for plain text emails only)
        $is_plain_text = isset( $field['email_format'] ) && 'plain' === $field['email_format'];

        // Also check if this is being used in a context that requires plain text
        $context = isset( $field['merge_tag_context'] ) ? $field['merge_tag_context'] : 'default';

        if ( $is_plain_text && 'email' === $context ) {
            // Plain text format for emails only
            if ( 'typed' === $signature_data[ 'signature_type' ] && ! empty( $signature_data[ 'typed_name' ] ) ) {
                return $signature_data[ 'typed_name' ] . ' (Typed Signature)';
            }

            if ( 'drawn' === $signature_data[ 'signature_type' ] ) {
                $timestamp = isset( $signature_data[ 'timestamp' ] ) ? ' - ' . $signature_data[ 'timestamp' ] : '';
                return 'Drawn Signature' . $timestamp;
            }
        } else {
            // Detect if we're in a PDF context
            $is_pdf_context = $this->is_pdf_rendering_context();

            // HTML format - return HTML for merge tags in HTML fields, emails, and PDFs
            $options = $this->get_signature_html_options( $context );

            // For PDF context with typed signatures, generate an image instead of HTML text
            if ( $is_pdf_context && 'typed' === $signature_data['signature_type'] && ! empty( $signature_data['typed_name'] ) ) {
                error_log( 'Ninja Forms Signature: PDF context detected, generating image for typed signature' );
                $html = $this->get_signature_pdf_image_html( $signature_data, $options );
            } else {
                if ( $is_pdf_context ) {
                    error_log( 'Ninja Forms Signature: PDF context but not typed signature (type: ' . ( isset( $signature_data['signature_type'] ) ? $signature_data['signature_type'] : 'unknown' ) . ')' );
                }
                // Allow filter to override options
                $options = apply_filters( 'ninja_forms_signature_merge_tag_options', $options, $field, $context );

                // Generate HTML that will display properly in HTML fields
                $html = $this->get_signature_email_html( $signature_data, $options );
            }

            // Check if we're in a repeater context (field data has different structure)
            $is_repeater_context = isset( $field['id'] ) && strpos( $field['id'], '_' ) !== false;

            // Wrap HTML for proper display (skip wrapper if in repeater context)
            if ( ! empty( $html ) ) {
                if ( $is_repeater_context ) {
                    return $html;
                }
                return $this->wrap_signature_html( $html, 'merge-tag' );
            }
        }

        return '';
    }

    /**
     * Filter CSV export value
     * 
     * Formats signature data for CSV exports in a human-readable format:
     * - Empty: "Method: none | Signed: false"
     * - Typed: "Method: typed | Signed: true | Value: John Doe"
     * - Drawn: "Method: drawn | Signed: true"
     * 
     * @param mixed $value The field value (JSON string)
     * @param object|array $field The field object or array (unused but required by filter signature)
     * @return string Formatted value for CSV export
     */
    public function filter_csv_export_value( $value, $field )
    {
        // Handle already processed values (e.g., from Email action)
        if ( is_string( $value ) && ( strpos( $value, 'Method:' ) !== false || strpos( $value, 'Signed:' ) !== false ) ) {
            return $value;
        }

        // Build CSV parts
        $csv_parts = array();
        
        // Handle empty values - field was not signed
        if ( empty( $value ) ) {
            $csv_parts[] = 'Method: none';
            $csv_parts[] = 'Signed: false';
            return implode( ' | ', $csv_parts );
        }

        // Try to decode JSON - handle potential escaping issues
        $signature_data = json_decode( $value, true );
        
        // If first decode fails, try with stripslashes
        if ( ! is_array( $signature_data ) && is_string( $value ) ) {
            $signature_data = json_decode( stripslashes( $value ), true );
        }
        
        // If still not valid, try html_entity_decode (common in email contexts)
        if ( ! is_array( $signature_data ) && is_string( $value ) ) {
            $decoded_value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $signature_data = json_decode( $decoded_value, true );
        }
        
        // If we can't parse the data, check if it might be just the signature type string
        if ( ! is_array( $signature_data ) ) {
            // Sometimes the value might be just the signature type as a plain string
            if ( in_array( $value, array( 'typed', 'drawn' ) ) ) {
                $csv_parts[] = 'Method: ' . $value;
                $csv_parts[] = 'Signed: false';
                return implode( ' | ', $csv_parts );
            }
            
            $csv_parts[] = 'Method: error';
            $csv_parts[] = 'Signed: false';
            return implode( ' | ', $csv_parts );
        }

        // Get the method used - signature_type contains the actual method used during submission
        $method = isset( $signature_data['signature_type'] ) ? $signature_data['signature_type'] : 'unknown';
        $csv_parts[] = 'Method: ' . $method;
        
        // Check if a value was actually provided based on the method
        $has_value = false;
        $typed_value = '';
        
        if ( 'typed' === $method ) {
            // For typed signatures, check if name was provided
            if ( isset( $signature_data['typed_name'] ) && ! empty( $signature_data['typed_name'] ) ) {
                $has_value = true;
                $typed_value = $signature_data['typed_name'];
            }
        } elseif ( 'drawn' === $method ) {
            // For drawn signatures, check if signature data exists
            if ( isset( $signature_data['signature_data'] ) && ! empty( $signature_data['signature_data'] ) ) {
                $has_value = true;
            }
        }
        
        // Add signed status based on whether value exists
        $csv_parts[] = 'Signed: ' . ( $has_value ? 'true' : 'false' );
        
        // Add the value when method is typed
        if ( 'typed' === $method && ! empty( $typed_value ) ) {
            $csv_parts[] = 'Value: ' . $typed_value;
        }
        
        return implode( ' | ', $csv_parts );
    }

    /**
     * Filter PDF export value - transforms signature data for PDF export
     * 
     * This filter is specific to the signature field type and prepares
     * the signature data in a format suitable for PDF generation.
     * 
     * @param mixed $value The field value (JSON string)
     * @param object $field The field object (unused but required by filter signature)
     * @return array Formatted value for PDF export with type and value keys
     */
    public function filter_pdf_value( $value, $field )
    {
        if ( empty( $value ) ) {
            return [
                'type' => 'empty',
                'value' => ''
            ];
        }

        $signature_data = json_decode( $value, true );
        
        if ( ! is_array( $signature_data ) ) {
            return [
                'type' => 'empty',
                'value' => ''
            ];
        }

        // Get PDF-ready data using internal method
        return $this->get_signature_pdf_data( $signature_data );
    }
    
    /**
     * Filter PDF field display to show signature
     * 
     * This is the main filter for controlling how signature fields appear in PDFs.
     * It handles both the default PDF table (which uses MultiCell and doesn't support HTML)
     * and custom templates (which can use WriteHTML for full HTML support).
     * 
     * Note: The default PDF table generation doesn't support HTML, so we provide
     * a text representation. For proper signature image display, users should
     * use a custom PDF template or the document body feature.
     * 
     * @param string $field_value The current field value
     * @param string $original_value The original field value (JSON string)
     * @param array $field The field data including type, id, etc.
     * @return string Modified field value for PDF display
     */
    public function filter_pdf_field_display( $field_value, $original_value, $field )
    {
        // Only process signature fields
        if ( ! isset( $field['type'] ) || 'signature' !== $field['type'] ) {
            return $field_value;
        }
        
        // If value is empty, return empty message
        if ( empty( $original_value ) ) {
            return esc_html__( 'Not signed', 'ninja-forms' );
        }
        
        // Try to decode JSON
        $signature_data = json_decode( $original_value, true );
        
        // If first decode fails, try with stripslashes
        if ( ! is_array( $signature_data ) && is_string( $original_value ) ) {
            $signature_data = json_decode( stripslashes( $original_value ), true );
        }
        
        // If we can't parse the data, return error message
        if ( ! is_array( $signature_data ) || ! isset( $signature_data['signature_type'] ) ) {
            return esc_html__( 'Invalid signature data', 'ninja-forms' );
        }
        
        // Handle based on signature type
        if ( 'typed' === $signature_data['signature_type'] && ! empty( $signature_data['typed_name'] ) ) {
            // For typed signatures, show name and font
            $font = isset( $signature_data['signature_font'] ) ? $signature_data['signature_font'] : 'dancing-script';
            
            // Return text representation since default PDF table doesn't support HTML
            return sprintf(
                '%s (Typed signature - %s font)',
                $signature_data['typed_name'],
                $font
            );
            
        } elseif ( 'drawn' === $signature_data['signature_type'] && ! empty( $signature_data['signature_data'] ) ) {
            // For drawn signatures in default PDF table, we can only show text
            // To display the actual image, users need to use a custom template
            // or enable the "use_document_body" option
            return esc_html__( 'Drawn signature captured', 'ninja-forms' );
        }
        
        return esc_html__( 'Not signed', 'ninja-forms' );
    }
    
    /**
     * Disable wpautop for signature fields in PDF
     * 
     * @param bool $apply_wpautop Whether to apply wpautop
     * @param string $field_value The field value
     * @param array $field The field data
     * @return bool
     */
    public function disable_wpautop_for_signatures( $apply_wpautop, $field_value, $field )
    {
        if ( isset( $field['type'] ) && 'signature' === $field['type'] ) {
            return false;
        }
        
        return $apply_wpautop;
    }
    
    /**
     * Add signature to the list of HTML-safe fields
     * 
     * This ensures signature field values are not stripped of HTML
     * when used in merge tags
     * 
     * @param array $safe_fields Current list of safe fields
     * @return array Modified list
     */
    public function add_to_safe_fields( $safe_fields )
    {
        if ( ! in_array( 'signature', $safe_fields ) ) {
            $safe_fields[] = 'signature';
        }
        
        return $safe_fields;
    }
    /**
     * Get default HTML options for signature display
     *
     * @param string $context The display context (merge_tag, email, etc.)
     * @return array HTML generation options
     */
    private function get_signature_html_options( $context = 'merge_tag' )
    {
        $default_options = [
            'max_width' => '400px',
            'font_size' => '28px',
            'typed_color' => '#000000',
            'container_style' => 'display: inline-block;'
        ];

        // Allow context-specific overrides
        return apply_filters( 'ninja_forms_signature_html_options', $default_options, $context );
    }

    /**
     * Detect if we're rendering in a PDF context
     *
     * Checks various indicators to determine if the current rendering
     * is happening within a PDF generation process.
     *
     * @return bool True if in PDF context, false otherwise
     */
    private function is_pdf_rendering_context()
    {
        // Check if this is being called from PDF generation actions
        if ( doing_action( 'nf_pdf_before_template_part' ) || doing_action( 'nf_pdf_after_template_part' ) ) {
            return true;
        }

        // Check the call stack for PDF-related classes
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 15 );
        foreach ( $backtrace as $trace ) {
            if ( isset( $trace['class'] ) && strpos( $trace['class'], 'NF_Pdf_Submissions' ) !== false ) {
                return true;
            }
            if ( isset( $trace['file'] ) && strpos( $trace['file'], 'ninja-forms-pdf-submissions' ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate HTML for signature display in PDF using an image
     *
     * Converts typed signature text to an image to ensure proper font rendering
     * in PDF documents without affecting other text.
     *
     * @param array $signature_data Decoded signature JSON data
     * @param array $options Display options
     * @return string HTML with image tag or fallback text
     */
    private function get_signature_pdf_image_html( $signature_data, $options = [] )
    {
        // Validate we have typed signature data
        if ( 'typed' !== $signature_data['signature_type'] || empty( $signature_data['typed_name'] ) ) {
            return '';
        }

        $font = isset( $signature_data['signature_font'] ) ? $signature_data['signature_font'] : 'dancing-script';
        $text = $signature_data['typed_name'];

        // Generate image with signature font
        $image_options = [
            'font_size' => 48,
            'color' => isset( $options['typed_color'] ) ? $options['typed_color'] : '#000000',
            'padding' => 15,
        ];

        $image_data = $this->generate_signature_image( $text, $font, $image_options );

        // If image generation failed, fall back to plain text
        if ( false === $image_data ) {
            return sprintf(
                '<div style="font-family: serif; font-style: italic; font-size: 24px;">%s</div>',
                esc_html( $text )
            );
        }

        // Return image tag with appropriate styling
        $max_width = isset( $options['max_width'] ) ? $options['max_width'] : '400px';

        return sprintf(
            '<img src="%s" alt="%s" style="max-width: %s; height: auto; display: block;" />',
            esc_attr( $image_data ),
            esc_attr( sprintf( __( 'Signature: %s', 'ninja-forms' ), $text ) ),
            esc_attr( $max_width )
        );
    }
    
    /**
     * Generate HTML wrapper for signature content
     * @param string $html The signature HTML content
     * @param string $context The display context
     * @return string Wrapped HTML
     */
    private function wrap_signature_html( $html, $context = 'merge_tag' )
    {
        if ( empty( $html ) ) {
            return '';
        }

        $class = 'nf-signature-' . sanitize_html_class( $context );
        return sprintf(
            '<div class="%s" style="display: block; margin: 10px 0;">%s</div>',
            esc_attr( $class ),
            $html
        );
    }
    
    /**
     * Convert signature data to HTML for email display
     *
     * @param array $signature_data Decoded signature JSON data
     * @param array $options Display options
     * @return string HTML output
     */
    private function get_signature_email_html( $signature_data, $options = [] )
    {
        $defaults = [
            'max_width' => '400px',
            'font_size' => '28px',
            'typed_color' => '#000000',
            'container_style' => '',
        ];

        $options = array_merge( $defaults, $options );

        // For typed signatures
        if ( 'typed' === $signature_data['signature_type'] && ! empty( $signature_data['typed_name'] ) ) {
            $font = isset( $signature_data['signature_font'] ) ? $signature_data['signature_font'] : 'dancing-script';
            $font_family = $this->get_signature_font_family( $font );

            $style = sprintf(
                'font-family: %s; font-size: %s; color: %s; padding: 10px 15px; background-color: transparent; display: inline-block; %s',
                esc_attr( $font_family ),
                esc_attr( $options['font_size'] ),
                esc_attr( $options['typed_color'] ),
                esc_attr( $options['container_style'] )
            );

            return sprintf(
                '<div style="%s">%s</div>',
                $style,
                esc_html( $signature_data['typed_name'] )
            );
        }

        // For drawn signatures
        if ( 'drawn' === $signature_data['signature_type'] && ! empty( $signature_data['signature_data'] ) ) {
            $style = sprintf(
                'max-width: %s; height: auto; display: block; %s',
                esc_attr( $options['max_width'] ),
                esc_attr( $options['container_style'] )
            );

            return sprintf(
                '<img src="%s" alt="%s" style="%s" />',
                esc_attr( $signature_data['signature_data'] ),
                esc_attr__( 'Signature', 'ninja-forms' ),
                $style
            );
        }

        return '';
    }
    
    /**
     * Get font family CSS value
     *
     * @param string $font Font identifier
     * @return string CSS font-family value
     */
    private function get_signature_font_family( $font )
    {
        static $fonts = [
            'dancing-script' => '"Dancing Script", cursive',
            'satisfy' => '"Satisfy", cursive',
            'cursive' => 'cursive',
        ];

        return isset( $fonts[ $font ] ) ? $fonts[ $font ] : $fonts['dancing-script'];
    }

    /**
     * Get font file path for signature font
     *
     * @param string $font Font identifier
     * @return string|false Path to TTF font file or false if not found
     */
    private function get_signature_font_file( $font )
    {
        // Get the Ninja Forms plugin directory
        // __FILE__ is /includes/Fields/Signature.php, so we need to go up 2 levels
        $plugin_dir = dirname( dirname( dirname( __FILE__ ) ) );
        $font_dir = $plugin_dir . '/assets/fonts/signature/';

        $font_files = [
            'dancing-script' => 'dancing-script-400.ttf',
            'satisfy' => 'satisfy-400.ttf',
            'cursive' => 'dancing-script-400.ttf', // Fallback to Dancing Script
        ];

        $font_file = isset( $font_files[ $font ] ) ? $font_files[ $font ] : $font_files['dancing-script'];
        $full_path = $font_dir . $font_file;

        return file_exists( $full_path ) ? $full_path : false;
    }

    /**
     * Generate image from typed signature text
     *
     * Creates a PNG image with the signature text rendered in the selected font.
     * This is used for PDF display to ensure fonts render correctly without affecting
     * the rest of the PDF document.
     *
     * @param string $text The signature text to render
     * @param string $font Font identifier (dancing-script, satisfy, cursive)
     * @param array $options Image generation options (font_size, color, padding)
     * @return string|false Base64 encoded PNG data URL or false on failure
     */
    private function generate_signature_image( $text, $font = 'dancing-script', $options = [] )
    {
        // Check if GD library is available
        if ( ! function_exists( 'imagecreatetruecolor' ) ) {
            error_log( 'Ninja Forms Signature: GD library not available' );
            return false;
        }

        $defaults = [
            'font_size' => 48,      // Font size in points
            'color' => '#000000',   // Text color
            'padding' => 20,        // Padding around text
            'bg_color' => 'transparent', // Background color
        ];

        $options = array_merge( $defaults, $options );

        // Get font file path
        $font_file = $this->get_signature_font_file( $font );
        if ( ! $font_file ) {
            error_log( 'Ninja Forms Signature: Font file not found for font: ' . $font );
            return false;
        }

        // Log which font is being used
        error_log( 'Ninja Forms Signature: Generating image with font: ' . $font . ' (' . basename( $font_file ) . ')' );

        // Calculate text dimensions
        $font_size = $options['font_size'];
        $angle = 0;

        // Get bounding box for text
        $bbox = imagettfbbox( $font_size, $angle, $font_file, $text );
        if ( ! $bbox ) {
            return false;
        }

        // Calculate image dimensions
        $text_width = abs( $bbox[4] - $bbox[0] );
        $text_height = abs( $bbox[5] - $bbox[1] );
        $padding = $options['padding'];

        $img_width = $text_width + ( $padding * 2 );
        $img_height = $text_height + ( $padding * 2 );

        // Create image
        $image = imagecreatetruecolor( $img_width, $img_height );
        if ( ! $image ) {
            return false;
        }

        // Handle transparency
        imagesavealpha( $image, true );
        $transparent = imagecolorallocatealpha( $image, 0, 0, 0, 127 );
        imagefill( $image, 0, 0, $transparent );

        // Parse color
        $hex_color = ltrim( $options['color'], '#' );
        $r = hexdec( substr( $hex_color, 0, 2 ) );
        $g = hexdec( substr( $hex_color, 2, 2 ) );
        $b = hexdec( substr( $hex_color, 4, 2 ) );

        $text_color = imagecolorallocate( $image, $r, $g, $b );

        // Calculate text position (centered vertically, padded horizontally)
        $x = $padding;
        $y = $padding + $text_height;

        // Draw text
        imagettftext( $image, $font_size, $angle, $x, $y, $text_color, $font_file, $text );

        // Capture image as PNG
        ob_start();
        imagepng( $image, null, 9 ); // Max compression
        $image_data = ob_get_clean();

        // Clean up
        imagedestroy( $image );

        if ( ! $image_data ) {
            return false;
        }

        // Convert to base64 data URL
        return 'data:image/png;base64,' . base64_encode( $image_data );
    }
    
    /**
     * Generate PDF-ready signature data
     * 
     * @param array $signature_data Decoded signature JSON data
     * @return array PDF-compatible data structure
     */
    private function get_signature_pdf_data( $signature_data )
    {
        if ( ! is_array( $signature_data ) || ! isset( $signature_data['signature_type'] ) ) {
            return [
                'type' => 'empty',
                'value' => ''
            ];
        }
        
        // For typed signatures
        if ( 'typed' === $signature_data['signature_type'] && ! empty( $signature_data['typed_name'] ) ) {
            $font = isset( $signature_data['signature_font'] ) ? $signature_data['signature_font'] : 'dancing-script';
            
            return [
                'type' => 'styled_text',
                'value' => $signature_data['typed_name'],
                'font_family' => $this->get_signature_font_family( $font ),
                'font_size' => 24,
                'color' => '#000000',
                'style' => 'italic'
            ];
        }
        
        // For drawn signatures
        if ( 'drawn' === $signature_data['signature_type'] && ! empty( $signature_data['signature_data'] ) ) {
            // Try to extract just the base64 data part
            if ( preg_match( '/^data:image\/(png|jpeg);base64,(.+)/', $signature_data['signature_data'], $matches ) ) {
                return [
                    'type' => 'image',
                    'format' => $matches[1],
                    'data' => $matches[2],
                    'base64_full' => $signature_data['signature_data'],
                    'width' => 300,
                    'height' => 100,
                    'maintain_aspect_ratio' => true,
                    'alt' => 'Signature'
                ];
            }
        }
        
        return [
            'type' => 'empty',
            'value' => ''
        ];
    }

    /**
     * Format signature field display for CPT custom columns
     *
     * This method handles signature display in the WordPress CPT submissions table
     * (edit.php?post_type=nf_sub). It displays a compact signature representation.
     *
     * @param mixed $value Field value
     * @param object $field Field object
     * @param int $sub_id Submission ID
     * @return string Formatted HTML for table display
     */
    public function custom_columns( $value, $field, $sub_id = 0 )
    {
        // Only process signature fields
        if ( ! is_object( $field ) || $field->get_setting( 'type' ) !== 'signature' ) {
            return $value;
        }

        // Handle empty value
        if ( empty( $value ) || $value === '' ) {
            return '<span style="color: #999; font-style: italic;">' . esc_html__( 'Not signed', 'ninja-forms' ) . '</span>';
        }

        // Decode HTML entities first (common in WordPress contexts)
        $decoded_value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

        // Try to decode JSON from decoded value
        $signature_data = json_decode( $decoded_value, true );

        // If that didn't work, try the original value
        if ( ! is_array( $signature_data ) ) {
            $signature_data = json_decode( $value, true );
        }

        // Handle invalid JSON
        if ( ! is_array( $signature_data ) || ! isset( $signature_data['signature_type'] ) ) {
            return '<span style="color: #999; font-style: italic;">' . esc_html__( 'Invalid signature', 'ninja-forms' ) . '</span>';
        }

        // Display typed signature
        if ( 'typed' === $signature_data['signature_type'] && ! empty( $signature_data['typed_name'] ) ) {
            $font = isset( $signature_data['signature_font'] ) ? $signature_data['signature_font'] : 'dancing-script';
            $font_family = $this->get_signature_font_family( $font );

            $output = '<div style="display: inline-block;">';
            $output .= '<div style="font-family: ' . esc_attr( $font_family ) . '; font-size: 20px; color: #000; padding: 8px 12px; background: transparent;">';
            $output .= esc_html( $signature_data['typed_name'] );
            $output .= '</div>';
            $output .= '<div style="font-size: 11px; color: #666; margin-top: 3px;">' . esc_html__( 'Typed signature', 'ninja-forms' ) . '</div>';
            $output .= '</div>';

            return $output;
        }

        // Display drawn signature
        if ( 'drawn' === $signature_data['signature_type'] && ! empty( $signature_data['signature_data'] ) ) {
            if ( preg_match( '/^data:image\/(png|jpeg);base64,/', $signature_data['signature_data'] ) ) {
                $output = '<img src="' . esc_attr( $signature_data['signature_data'] ) . '" ';
                $output .= 'alt="' . esc_attr__( 'Signature', 'ninja-forms' ) . '" ';
                $output .= 'style="max-width: 150px; max-height: 50px; display: block;" />';
                $output .= '<div style="font-size: 11px; color: #666; margin-top: 3px;">' . esc_html__( 'Drawn signature', 'ninja-forms' ) . '</div>';

                return $output;
            }
        }

        return '<span style="color: #999; font-style: italic;">' . esc_html__( 'Invalid signature data', 'ninja-forms' ) . '</span>';
    }

}