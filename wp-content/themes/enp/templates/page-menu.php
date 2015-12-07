<?php 
	$parent = Roots\Sage\Extras\get_post_top_ancestor_id();
?>
	<div class="page-menu">
		<h5><?php echo get_the_title($parent); ?></h5>
		<ul class="nav">
	<?php
		wp_list_pages( array( 'title_li' => false, 'include' => $parent ) );
		global $post;
		if ( $post->post_parent ) {
			wp_list_pages( array( 'title_li' => false, 'child_of' => $post->post_parent ) );
		}
		else
			wp_list_pages( array( 'title_li' => false, 'child_of' => $post->ID ) );
	?>
		</ul>
	</div>