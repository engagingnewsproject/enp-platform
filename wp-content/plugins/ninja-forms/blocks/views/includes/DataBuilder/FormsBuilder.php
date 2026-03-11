<?php

namespace NinjaForms\Blocks\DataBuilder;

class FormsBuilder {

    protected $forms;

    public function __construct( $forms ) {
        $this->forms = $forms;
    }

    public function get() {
        $forms = array_map( [ $this, 'toArray' ], $this->forms );
        return array_reduce( $forms, function( $forms, $form ) {
            $forms[ $form[ 'formID' ] ] = $form;
            return $forms;
        }, []);
    }

    protected function toArray( $form ) {
        // Security: Use explicit array access instead of extract() to prevent variable overwriting attacks
        return [
            'formID' => $form['id'] ?? null,
            'formTitle' => $form['title'] ?? '',
        ];
    }
}
