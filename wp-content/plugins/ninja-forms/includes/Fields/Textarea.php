<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_Textarea
 */
class NF_Fields_Textarea extends NF_Abstracts_Input
{
    protected $_name = 'textarea';

    protected $_section = 'common';

    protected $_icon = 'paragraph';

    protected $_type = 'textarea';

    protected $_templates = 'textarea';

    protected $_test_value = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.';

    protected $_settings = array( 'input_limit_set', 'rte_enable', 'rte_media', 'rte_mobile', 'disable_browser_autocomplete', 'textarea_rte', 'disable_rte_mobile', 'textarea_media', );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Paragraph Text', 'ninja-forms' );

        $this->_settings[ 'default' ][ 'type' ] = 'textarea';
        $this->_settings[ 'placeholder' ][ 'type' ] = 'textarea';

        add_filter( 'ninja_forms_subs_export_field_value_' . $this->_name, array( $this, 'filter_csv_value' ), 10, 2 );
    }

    public function admin_form_element( $id, $value )
    {
        return "<textarea class='widefat' name='fields[$id]'>" . esc_textarea( $value ) . "</textarea>";
    }

    public function filter_csv_value( $field_value, $field ) {

        // Rich Text Editor values are stored as HTML; export them as readable text
        $textarea_rte = false;

        if ( is_object( $field ) ) {
            $textarea_rte = $field->get_setting( 'textarea_rte' );
        } elseif ( is_array( $field ) && isset( $field['settings']['textarea_rte'] ) ) {
            $textarea_rte = $field['settings']['textarea_rte'];
        }

        if ( $textarea_rte && is_string( $field_value ) ) {
            $field_value = $this->rich_text_to_readable_text( $field_value );
        }

        $field_value = WPN_Helper::maybe_escape_csv_column( $field_value );

        return $field_value;
    }

    /**
     * Convert stored rich text HTML into readable plain text
     *
     * Paragraphs, headings, list items and table rows each become one part,
     * and the parts are joined with " / " so the answer stays on one line in
     * every spreadsheet. List items get "* " or a number, table cells are
     * separated by " | ", and links keep their address in brackets.
     *
     * @param string $html
     * @return string
     */
    protected function rich_text_to_readable_text( $html ) {
        if ( '' === trim( $html ) ) {
            return '';
        }

        if ( ! class_exists( 'DOMDocument' ) ) {
            $text = preg_replace( '/<(br|\/p|\/li|\/h[1-6]|\/tr|\/div)\b[^>]*>/i', "\n", $html );
            $text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            return $this->join_readable_parts( $text );
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors( true );
        $document->loadHTML( '<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>' );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );

        $body = $document->getElementsByTagName( 'body' )->item( 0 );
        if ( null === $body ) {
            return $this->join_readable_parts( html_entity_decode( strip_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        }

        return $this->join_readable_parts( $this->readable_text_of_children( $body ) );
    }

    /**
     * Readable text of every child of a node
     *
     * @param DOMNode $node
     * @return string
     */
    protected function readable_text_of_children( $node ) {
        $text = '';
        foreach ( $node->childNodes as $child ) {
            $text .= $this->readable_text_of_node( $child );
        }
        return $text;
    }

    /**
     * Readable text of one node; "\n" marks a part boundary
     *
     * @param DOMNode $node
     * @return string
     */
    protected function readable_text_of_node( $node ) {
        if ( XML_TEXT_NODE === $node->nodeType || XML_CDATA_SECTION_NODE === $node->nodeType ) {
            return preg_replace( '/[ \t\r\n]+/', ' ', $node->nodeValue );
        }

        if ( XML_ELEMENT_NODE !== $node->nodeType ) {
            return '';
        }

        $tag = strtolower( $node->nodeName );

        // The editor's own list-marker element carries no answer text
        if ( 'span' === $tag && preg_match( '/(^|\s)ql-ui(\s|$)/', (string) $node->getAttribute( 'class' ) ) ) {
            return '';
        }

        switch ( $tag ) {
            case 'br':
                return "\n";

            case 'ol':
            case 'ul':
                return "\n" . $this->readable_list( $node, $tag ) . "\n";

            case 'tr':
                $cells = array();
                foreach ( $node->childNodes as $cell ) {
                    if ( XML_ELEMENT_NODE === $cell->nodeType && in_array( strtolower( $cell->nodeName ), array( 'td', 'th' ), true ) ) {
                        $cells[] = trim( preg_replace( '/\s+/', ' ', $this->readable_text_of_children( $cell ) ) );
                    }
                }
                if ( '' === implode( '', $cells ) ) {
                    return "\n";
                }
                return "\n" . implode( ' | ', $cells ) . "\n";

            case 'a':
                $text = $this->readable_text_of_children( $node );
                $href = trim( (string) $node->getAttribute( 'href' ) );
                if ( '' === $href || trim( $text ) === $href ) {
                    return $text;
                }
                if ( '' === trim( $text ) ) {
                    return $href;
                }
                return $text . ' (' . $href . ')';

            case 'p':
            case 'div':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'blockquote':
            case 'pre':
            case 'li':
            case 'table':
            case 'tbody':
            case 'thead':
                return "\n" . $this->readable_text_of_children( $node ) . "\n";

            default:
                return $this->readable_text_of_children( $node );
        }
    }

    /**
     * Readable lines of a list; numbering restarts with each run of numbered items
     *
     * @param DOMElement $list
     * @param string $tag ol or ul
     * @return string
     */
    protected function readable_list( $list, $tag ) {
        $lines = array();
        $number = 0;

        foreach ( $list->childNodes as $item ) {
            if ( XML_ELEMENT_NODE !== $item->nodeType || 'li' !== strtolower( $item->nodeName ) ) {
                continue;
            }

            $text = trim( preg_replace( '/\s+/', ' ', $this->readable_text_of_children( $item ) ) );
            if ( '' === $text ) {
                continue;
            }

            $type = (string) $item->getAttribute( 'data-list' );
            if ( '' === $type ) {
                $type = 'ol' === $tag ? 'ordered' : 'bullet';
            }

            if ( 'ordered' === $type ) {
                $number++;
                $lines[] = $number . '. ' . $text;
            } else {
                $number = 0;
                $lines[] = '* ' . $text;
            }
        }

        return implode( "\n", $lines );
    }

    /**
     * Trim each line, drop empty lines, and join the rest with " / "
     *
     * @param string $text
     * @return string
     */
    protected function join_readable_parts( $text ) {
        $lines = array_map( 'trim', explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $text ) ) );
        $lines = array_filter( $lines, function ( $line ) {
            return '' !== $line;
        } );
        return implode( ' / ', $lines );
    }
}
