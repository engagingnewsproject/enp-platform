<?php
    /**
     * CTF Header Notices
     *
     * @since 2.0
     */
    do_action('ctf_header_notices');
?>
<div id="ctf-about" class="ctf-about">
    <?php
        TwitterFeed\Admin\CTF_View::render( 'sections.header' );
        TwitterFeed\Admin\CTF_View::render( 'about.content' );
        TwitterFeed\Admin\CTF_View::render( 'sections.sticky_widget' );
    ?>
</div>