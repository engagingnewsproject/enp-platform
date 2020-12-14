<?php if(!$demo_enabled) : ?>
	<div class="demo-ad ga-panel ga-panel-default">
		<div class="ga-panel-heading">
			<strong>
				<?php esc_html_e('Get Demographic Data!'); ?>
				<button id="demographic-popup">Click Here To Enable</button>
			</strong>
		</div>
		<img src="<?php echo trailingslashit(get_home_url()) . 'wp-content/plugins/googleanalytics/assets/images/demo-ad.png'; ?>" />
	</div>
<?php else: ?>
	<div class="filter-choices">
		<a href="<?php echo get_admin_url('', $seven_url ); ?>" class="<?php echo esc_attr( $selected7 ); ?>">
			7 days
		</a>
		<a href="<?php echo get_admin_url('', $thirty_url ); ?>" class="<?php echo esc_attr( $selected30 ); ?>">
			30 days
		</a>
	</div>
	<div class="demo-ad ga-panel ga-panel-default">
		<div class="ga-panel-heading">
			<strong>
				<?php esc_html_e('Demographic by sessions'); ?>
			</strong>
		</div>
		<div class="ga-demo-chart">
			<div class="ga-panel-body ga-chart gender">
				<div id="demo_chart_gender_div" style="width: 100%;"></div>
				<div class="ga-loader-wrapper stats-page">
					<div class="ga-loader stats-page-loader"></div>
				</div>
			</div>
			<div class="ga-panel-body ga-chart gender">
				<div id="demo_chart_age_div" style="width: 100%;"></div>
				<div class="ga-loader-wrapper stats-page">
					<div class="ga-loader stats-page-loader"></div>
				</div>
			</div>
		</div>
	</div>
	<a href="<?php echo esc_url( $demographic_page_url ); ?>/" class="view-report" target="_blank">
		<?php echo esc_html__('View Full Report' ); ?>
	</a>
<hr>
<?php
endif;
