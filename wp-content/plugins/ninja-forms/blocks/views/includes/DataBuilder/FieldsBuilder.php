<?php

namespace NinjaForms\Blocks\DataBuilder;

class FieldsBuilder {

    protected $fields;

    public function __construct( $fields ) {
        $this->fields = $fields;
    }

    public function get() {
        $fields = array_filter( $this->fields, function( $field ) {
            return ! in_array( $field[ 'type' ], [ 'submit', 'html', 'hr' ] );
        });
        return array_map( [ $this, 'toArray' ], $fields );
    }

    protected function toArray( $field ) {
        // Security: Use explicit array access instead of extract() to prevent variable overwriting attacks
        return [
            'id' => $field['id'] ?? null,
            'label' => $field['label'] ?? '',
            'type' => $field['type'] ?? ''
        ];
    }
}