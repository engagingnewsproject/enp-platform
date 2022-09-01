<?php
/**
 * Shortcode - PodcastEpisode
 *
 * @since      3.0.17
 * @package    RankMathPro
 * @subpackage RankMathPro\Schema
 */

use RankMath\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

if ( empty( $schema['associatedMedia'] ) || empty( $schema['associatedMedia']['contentUrl'] ) ) {
	return;
}

$post_title    = get_the_title( $post->ID );
$season        = ! empty( $schema['partOfSeason'] ) ? $schema['partOfSeason'] : [];
$time_required = [];
if ( isset( $schema['timeRequired'] ) && WordPress::get_formatted_duration( $schema['timeRequired'] ) ) {
	$duration      = new \DateInterval( $schema['timeRequired'] );
	$time_required[] = ! empty( $duration->h ) ? sprintf( esc_html__( '%d Hour', 'rank-math-pro' ), $duration->h ) : '';
	$time_required[] = ! empty( $duration->i ) ? sprintf( esc_html__( '%d Min', 'rank-math-pro' ), $duration->i ) : '';
	$time_required[] = ! empty( $duration->s ) ?sprintf( esc_html__( '%d Sec', 'rank-math-pro' ), $duration->s ) : '';
	$time_required   = array_filter( $time_required );
}

ob_start();
?>
<!-- wp:columns -->
<div class="wp-block-columns" style="gap: 2em;">
	<!-- wp:column -->
	<?php if ( ! empty( $schema['thumbnailUrl'] ) ) { ?>
		<div class="wp-block-column" style="flex: 0 0 25%;">
			<!-- wp:image -->
				<figure class="wp-block-image size-large is-resized">
					<img src="<?php echo esc_url( $schema['thumbnailUrl'] ); ?>" />
				</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	<?php } ?>

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p>
			<?php if ( ! empty( $schema['datePublished'] ) ) { ?>
				<span class="rank-math-podcast-date">
					<?php echo esc_html( date( "j F", strtotime( $schema['datePublished'] ) ) ); ?>
				</span> &#183;
			<?php } ?>
			<span>
				<?php if ( ! empty( $season['seasonNumber'] ) ) { ?>
					<?php echo esc_html__( 'Season', 'rank-math-pro' ); ?> <?php echo esc_html( $season['seasonNumber'] ); ?>
					<?php if ( ! empty( $season['name'] ) ) { ?>
						: <a href="<?php echo esc_url( $season['url'] ); ?>"><?php echo esc_html( $season['name'] ); ?></a>
					<?php } ?> &#183;
				<?php } ?>

				<?php if ( ! empty( $schema['episodeNumber'] ) ) { ?>
					<?php echo esc_html__( 'Episode', 'rank-math-pro' ); ?> <?php echo esc_html( $schema['episodeNumber'] ); ?>
				<?php } ?>
			</span>
		</p>
		<!-- /wp:paragraph -->

		<?php if ( $schema['name'] !== $post_title || $post->ID !== get_the_ID() ) { ?>
			<!-- wp:heading -->
				<h2>
					<?php echo esc_html( $schema['name'] ); ?>
				</h2>
			<!-- /wp:heading -->
		<?php } ?>

		<!-- wp:paragraph -->
			<p>
				<?php if ( ! empty( $time_required ) ) { ?>
					<span>
						<?php echo implode( ', ', $time_required ); ?>
					</span>
					&#183;
				<?php } ?>
				<?php if ( ! empty( $schema['author'] ) ) { ?>
					<?php echo esc_html__( 'By', 'rank-math-pro' ); ?> <?php echo esc_html( $schema['author']['name'] ); ?>
				<?php } ?>
			</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:audio -->
<figure class="wp-block-audio">
	<audio controls src="<?php echo esc_url( $schema['associatedMedia']['contentUrl'] ); ?>"></audio>
</figure>
<!-- /wp:audio -->

<?php if ( ! empty( $schema['description'] ) ) { ?>
	<!-- wp:paragraph -->
		<p><?php echo esc_html( $schema['description'] ); ?></p>
	<!-- /wp:paragraph -->
<?php } ?>
<?php

echo do_blocks( ob_get_clean() );
