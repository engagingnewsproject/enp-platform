<?php

if ( apply_filters( 'ninja_forms_disable_marketing', false ) ) return array();

return apply_filters( 'ninja_forms_available_settings', array(

    'pdf_submissions'   => array(
        'id' 			=> 'pdf_submissions',
        'nicename' 		=> esc_html__( 'PDF Submissions', 'ninja-forms' ),
        'link'          => 'https://ninjaforms.com/extensions/pdf-form-submission/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=PDF+Form+Submission+Ghost+Setting',
        'modal_content' => '<div class="available-action-modal">
            <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/pdf-form-submission.png"/>
            <p>In order to use this action, you need PDF Submissions for Ninja Forms.</p>
            <p>Generate a PDF of any WordPress form submission. Export any submission as a PDF, or attach it to an email and send a copy to whoever needs one!</p>
            <div class="actions">
                <a target="_blank" href="https://ninjaforms.com/extensions/pdf-form-submission/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=PDF+Form+Submission+Ghost+Setting" title="PDF Form Submissions" class="primary nf-button">Learn More</a>
            </div>
        </div>',
    ),

    'save_progress' => array(
        'id'        => 'save_progress',
        'nicename'  => esc_html__( 'Save Progress', 'ninja-forms' ),
        'link'      => 'https://ninjaforms.com/extensions/save-progress/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Save+Progress+Ghost+Setting',
        'modal_content' => '<div class="available-action-modal">
            <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/save-progress.png"/>
            <p>In order to use this action, you need Save Progress for Ninja Forms.</p>
            <p>Let your users save their work and reload it all when they have time to return. Don&apos;t lose out on valuable submissions for longer forms!</p>
            <div class="actions">
                <a target="_blank" href="https://ninjaforms.com/extensions/save-progress/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Save+Progress+Ghost+Setting" title="Save Progress" class="primary nf-button">Learn More</a>
            </div>
        </div>',
    ),

    'styles' => array(
        'id'        => 'styles',
        'nicename'  => esc_html__( 'Styles', 'ninja-forms' ),
        'link'      => 'https://ninjaforms.com/extensions/layout-styles/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Layout+and+Styles+Ghost+Setting',
        'modal_content' => '<div class="available-action-modal">
            <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/layout-styles.png"/>
            <p>In order to use this action, you need Layouts&amp;Styles for Ninja Forms.</p>
            <p>Drag and drop fields into columns and rows. Resize fields. Add backgrounds, adjust borders, and more. Design gorgeous forms without being a designer!</p>
            <div class="actions">
                <a target="_blank" href="https://ninjaforms.com/extensions/layout-styles/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Layout+and+Styles+Ghost+Setting" title="Layouts Styles" class="primary nf-button">Learn More</a>
            </div>
        </div>',
    ),

    'conditional_logic' => array(
        'id' => 'conditional_logic',
        'nicename' => esc_html__( 'Conditional Logic', 'ninja-forms' ),
        'link'      => 'https://ninjaforms.com/extensions/conditional-logic/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Conditional+Logic+Ghost+Setting',
        'modal_content' => '<div class="available-action-modal">
            <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/conditional-logic.png"/>
            <p>In order to use this action, you need Conditional Logic for Ninja Forms.</p>
            <p>Create forms that change as they&apos;re filled out! Show and hide fields. Modify lists. Send email to different recipients conditionally, and much more!</p>
            <div class="actions">
                <a target="_blank" href="https://ninjaforms.com/extensions/conditional-logic/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Conditional+Logic+Ghost+Setting" title="Conditional Logic" class="primary nf-button">Learn More</a>
            </div>
        </div>',
    ),

    'multi_part' => array(
        'id'        => 'multi_part',
        'nicename'  => esc_html__( 'Multi-Part', 'ninja-forms' ),
        'link'      => 'https://ninjaforms.com/extensions/multi-step-forms/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Multi+Step+Forms+Ghost+Setting',
        'modal_content' => '<div class="available-action-modal">
            <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/multi-step-forms.png"/>
            <p>In order to use this action, you need Multi Step Forms for Ninja Forms.</p>
            <p>Give submissions a boost on any longer form by making it a multi-page form. Drag and drop fields between pages, add breadcrumb navigation, a progress bar, and loads more!</p>
            <div class="actions">
                <a target="_blank" href="https://ninjaforms.com/extensions/multi-step-forms/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Multi+Step+Forms+Ghost+Setting" title="Multi Step Forms" class="primary nf-button">Learn More</a>
            </div>
        </div>',
    ),

    'scheduled_exports' => array(
        'id'        => 'scheduled_exports',
        'nicename'  => esc_html__( 'Scheduled Exports', 'ninja-forms' ),
        'link'      => 'https://ninjaforms.com/extensions/scheduled-submissions-export/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Scheduled+Exports+Ghost+Setting',
        'modal_content' => '<div class="available-action-modal">
            <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/scheduled-exports.png"/>
            <p>In order to use this action, you need Scheduled Exports for Ninja Forms.</p>
            <p>Use Scheduled Submissions Export to set hourly, daily, or weekly exports of any WordPress form submissions to any email address(es)!</p>
            <div class="actions">
                <a target="_blank" href="https://ninjaforms.com/extensions/scheduled-submissions-export/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Ghost+Setting&utm_content=Scheduled+Exports+Ghost+Setting" title="Scheduled Exports" class="primary nf-button">Learn More</a>
            </div>
        </div>',
    ),

) );
