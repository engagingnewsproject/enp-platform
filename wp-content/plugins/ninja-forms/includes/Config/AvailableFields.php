<?php

if ( apply_filters( 'ninja_forms_disable_marketing', false ) ) return array();

return apply_filters( 'ninja_forms_available_fields', array(

    'file_upload'           => array(
        'section'           => 'common',
        'name'              => 'file_upload',
        'nicename'          => 'File Upload',
        'link'              => 'https://ninjaforms.com/extensions/file-uploads/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Form+Field&utm_content=File+Uploads',
        'plugin_path'       => 'ninja-forms-uploads/file-uploads.php',
        'modal_content'     => '<div class="available-action-modal">
                                <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/file-uploads.png"/>
                                <p>In order to use this field, you need File Uploads for Ninja Forms.</p>
                                <p>Upload files to WordPress, Google Drive, Dropbox, or Amazon S3. Upload documents, images, media, and more. Easily control file type and size. Add an upload field to any form!</p>
                                <div class="actions">
                                    <a target="_blank" href="https://ninjaforms.com/extensions/file-uploads/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Form+Field&utm_content=File+Uploads" title="File Uploads" class="primary nf-button">Learn More</a>
                                </div>
                            </div>',
    ),

    'save'           => array(
        'section'           => 'common',
        'name'              => 'save',
        'nicename'          => 'Save',
        'link'              => 'https://ninjaforms.com/extensions/save-progress/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Form+Field&utm_content=Save+Progress',
        'plugin_path'       => 'ninja-forms-save-progress/ninja-forms-save-progress.php',
        'modal_content'     => '<div class="available-action-modal">
                                <img src="' . Ninja_Forms::$url . 'assets/img/add-ons/save-progress.png"/>
                                <p>In order to use this field, you need Save Progress for Ninja Forms.</p>
                                <p>Add save fields to let your users save their work and reload it all when they have time to return. Don&apos;t lose out on valuable submissions for longer forms!</p>
                                <div class="actions">
                                    <a target="_blank" href="https://ninjaforms.com/extensions/save-progress/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Form+Field&utm_content=Save+Progress" title="Save Progress" class="primary nf-button">Learn More</a>
                                </div>
                            </div>',
    ),
) );
