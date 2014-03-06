				<div id="footer">
					<div id="footer-widgets" class="clearfix">
						<?php
							$footer_sidebars = array('footer-area-1','footer-area-2','footer-area-3');
							if ( is_active_sidebar( $footer_sidebars[0] ) || is_active_sidebar( $footer_sidebars[1] ) || is_active_sidebar( $footer_sidebars[2] ) ) {
								foreach ( $footer_sidebars as $key => $footer_sidebar ){
									if ( is_active_sidebar( $footer_sidebar ) ) {
										echo '<div class="footer-widget' . (  2 == $key ? ' last' : '' ) . '">';
										dynamic_sidebar( $footer_sidebar );
										echo '</div>';
									}
								}
							}
						?>
					</div> <!-- end #footer-widgets -->
				</div> <!-- end #footer -->
			</div> <!-- end #content -->
		</div> <!-- end #wrapper -->

		<p id="copyright"><?php printf( __('Housed at %s', 'Trim'), '<a href="http://communication.utexas.edu/strauss" title="The Annette Strauss Institute for Civic Life">The Annette Strauss Institute for Civic Life</a>' ); ?></p>
	</div> <!-- end #container -->

	<?php wp_footer(); ?>
</body>
</html>