<?php
/**
 * Smash Balloon Custom Twitter Feeds Header Template
 * Information about the person tweeting, replying, or quoting
 *
 * @version 2.0.4 Custom Twitter Feeds by Smash Balloon
 *
 */
use TwitterFeed\CTF_Parse;
use TwitterFeed\CTF_Display_Elements;
use TwitterFeed\CTF_GDPR_Integrations;

$header_no_bio = ( !$feed_options['showbio'] || empty( $header_info['description'] ) ) ? $header_no_bio = ' ctf-no-bio' : $header_no_bio = "";

$header_info = CTF_Parse::get_user_header_json( $feed_options );
$header_attr = CTF_Display_Elements::get_element_attribute( 'header', $feed_options );

$username 			= CTF_Parse::get_user_name( $header_info );
$header_text 		= CTF_Parse::get_header_text( $header_info, $feed_options );
$header_description = CTF_Parse::get_header_description( $header_info );
$verified_account 	= ( $header_info['verified'] == 1 ) ? ctf_get_fa_el( 'fa-check-circle' ) : "";
$bio_attr 			= CTF_Display_Elements::get_element_attribute( 'headerbio', $feed_options );
$avatar 			= CTF_Parse::get_header_avatar( $header_info, $feed_options );
?>

<div class="ctf-header <?php echo $header_no_bio ?>" <?php echo $header_attr ?>>
    <a href="<?php echo esc_url('https://twitter.com/' . $username . '/' ); ?>" target="_blank" rel="noopener noreferrer" title="@<?php echo esc_attr( $username ); ?>" class="ctf-header-link">
        <div class="ctf-header-text">
            <p class="ctf-header-user">
                <span class="ctf-header-name"><?php echo wp_kses_post( $header_text ); ?></span>
                <span class="ctf-verified"><?php echo $verified_account; ?></span>
                <span class="ctf-header-follow"><?php echo  ctf_get_fa_el( 'fa-twitter' ) . ' ' . esc_html__( 'Follow', 'custom-twitter-feeds' ); ?></span>
            </p>

			<?php if ($feed_options['showbio'] && ! empty( $header_description )  || ctf_doing_customizer( $feed_options )) : ?>
                <p class="ctf-header-bio" <?php echo $bio_attr ?> >
					<?php echo wp_kses_post( $header_description ); ?>
                </p>
			<?php endif; ?>
        </div>

        <div class="ctf-header-img">
            <div class="ctf-header-img-hover"><?php echo ctf_get_fa_el( 'fa-twitter' )  ?></div>
			<?php if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) : ?>
                <span data-avatar="<?php echo esc_url( CTF_Parse::get_avatar( $header_info ) ) ?>" data-alt="<?php echo esc_attr( $username ); ?>" style="display: none;"><?php esc_html_e( 'Avatar', 'custom-twitter-feeds' ); ?></span>
			<?php else : ?>
                <img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $username ); ?>" width="48" height="48">
			<?php endif; ?>
        </div>
    </a>
</div>
