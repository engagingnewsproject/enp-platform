<?php
	$parent = Roots\Sage\Extras\get_post_top_ancestor_id();
?>
	<div class="page-menu">
		<h5><a href="<?php echo get_permalink($parent); ?>"><?php echo get_the_title($parent); ?></a></h5>
		<ul class="nav">
	<?php
		if ( $post->post_parent ) {
			wp_list_pages( array( 'title_li' => false, 'child_of' => $post->post_parent ) );
		}
		else
			wp_list_pages( array( 'title_li' => false, 'child_of' => $post->ID ) );
	?>
		</ul>
	</div>
