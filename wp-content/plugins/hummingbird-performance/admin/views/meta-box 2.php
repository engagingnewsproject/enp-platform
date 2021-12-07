<?php
/**
 * Meta box template.
 *
 * @package Hummingbird
 *
 * @var callable $callback_header
 * @var callable $callback_footer
 * @var string   $orig_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="wphb-box-<?php echo esc_attr( $id ); ?>" class="box-<?php echo esc_attr( $id ); ?> <?php echo esc_attr( $args['box_class'] ); ?>">

	<?php if ( is_callable( $callback_header ) ) : ?>
		<div class="<?php echo esc_attr( $args['box_header_class'] ); ?>">
			<?php call_user_func( $callback_header ); ?>
		</div><!-- end sui-box-title -->
	<?php elseif ( $this->view_exists( $orig_id . '/meta-box-header' ) ) : ?>
		<div class="<?php echo esc_attr( $args['box_header_class'] ); ?>">
			<?php
			$this->view(
				$orig_id . '/meta-box-header',
				array(
					'title' => $title,
				)
			);
			?>
		</div><!-- end sui-box-title -->
	<?php elseif ( $this->view_exists( $orig_id . '-meta-box-header' ) ) : ?>
		<div class="<?php echo esc_attr( $args['box_header_class'] ); ?>">
			<?php
			$this->view(
				$orig_id . '-meta-box-header',
				array(
					'title' => $title,
				)
			);
			?>
		</div><!-- end sui-box-title -->
	<?php elseif ( $title ) : ?>
		<div class="<?php echo esc_attr( $args['box_header_class'] ); ?>">
			<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
		</div><!-- end sui-box-title -->
	<?php endif; ?>

	<?php if ( $args['box_content_class'] ) : ?>
		<div class="<?php echo esc_attr( $args['box_content_class'] ); ?>">
			<?php if ( is_callable( $callback ) ) : ?>
				<?php call_user_func( $callback ); ?>
			<?php else : ?>
				<?php $this->view( $orig_id . '-meta-box' ); ?>
			<?php endif; ?>
		</div><!-- end box_content_class -->
	<?php else : ?>
		<?php
		if ( is_callable( $callback ) ) {
			call_user_func( $callback );
		} else {
			$this->view( $orig_id . '-meta-box' );
		}
		?>
	<?php endif; ?>

	<?php if ( is_callable( $callback_footer ) ) : ?>
		<div class="<?php echo esc_attr( $args['box_footer_class'] ); ?>">
			<?php call_user_func( $callback_footer ); ?>
		</div><!-- end sui-box-footer -->
	<?php elseif ( $this->view_exists( $orig_id . '/meta-box-footer' ) ) : ?>
		<div class="<?php echo esc_attr( $args['box_footer_class'] ); ?>">
			<?php $this->view( $orig_id . '/meta-box-footer' ); ?>
		</div><!-- end sui-box-footer -->
	<?php elseif ( $this->view_exists( $orig_id . '-meta-box-footer' ) ) : ?>
		<div class="<?php echo esc_attr( $args['box_footer_class'] ); ?>">
			<?php $this->view( $orig_id . '-meta-box-footer' ); ?>
		</div><!-- end sui-box-footer -->
	<?php endif; ?>

</div><!-- end box-<?php echo esc_attr( $id ); ?> -->
