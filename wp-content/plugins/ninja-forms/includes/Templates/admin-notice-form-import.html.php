<div class="updated">
    <p>
        <?php esc_html_e( 'Form Imported Successfully.', 'ninja-forms' ); ?>
        <a href="<?php echo esc_attr( add_query_arg( 'form_id', $form_id, admin_url( 'admin.php?page=ninja-forms' ) ) ); ?>"><?php esc_html_e( 'View Form', 'ninja-forms' ); ?></a>
    </p>
</div>