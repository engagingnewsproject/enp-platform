<div class="wrap">
    <table class="form-table">
        <tbody>
            <tr id="row-nf-import-response" style="display:none;background-color:#ffc;">
                <th></th>
                <td><?php printf( esc_html__( 'Form Imported Successfully. %sView Form%s', 'ninja-forms' ), '<a id="nf-import-url" href="#">', '</a>' ); ?></td>
            </tr>
            <tr id="row-nf-import-response-error" style="display:none;background-color:#ffc;color:red;">
                <th></th>
                <td></td>
            </tr>
            <tr id="row_nf_import_form">
                <th scope="row">
                    <label for="nf-import-file"><?php esc_html_e( 'Select a file', 'ninja-forms' ); ?></label>
                </th>
                <td>
                    <input type="file" id="nf-import-file" class="widefat">
                </td>
            </tr>
            <?php if( ! WPN_Helper::maybe_disallow_unfiltered_html_for_sanitization() ) { ?>
            <tr id="row_nf_import_form_trusted_source">
                <th scope="row">
                    <label for="nf_import_form_turn_off_extra_checks"><?php
                        esc_html_e( 'Trusted source', 'ninja-forms' );
                        ?></label>
                </th>
                <td colspan="2">
                    <input type="checkbox" name="nf_import_form_turn_off_extra_checks"
                            id="nf_import_form_turn_off_extra_checks">
                    <label style="font-style: italic;"
                            for="nf_import_form_turn_off_extra_checks">
                        <?php esc_html_e("Check this if the form comes from a source you trust. Less strict security measures will be applied on import, such as leaving HTML intact in field labels.", "ninja-forms"); ?>
                    </label>
                </td>
            </tr>
            <?php } ?>
            <tr id="row-nf-import-type-error" style="display:none;color:red;">
                <th></th>
                <td><?php printf( esc_html__( 'Please select a Ninja Forms export. %sMust be in .nff format%s.', 'ninja-forms' ), '<strong>', '</strong>' ); ?></td>
            </tr>
            <tr id="row_nf_import_form_submit">
                <th scope="row">
                    <label for="nf-import-form-submit"><?php esc_html_e( 'Import Form', 'ninja-forms' ); ?></label>
                </th>
                <td>
                    <input type="button" id="nf-import-form-submit" class="button-secondary" value="<?php esc_html_e( 'Import Form', 'ninja-forms' ) ;?>">
                </td>
            </tr>
        </tbody>
    </table>
</div>