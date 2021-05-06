<header class="header-container">
	<a href="http://wpengine.com/" target="_blank" rel="noopener noreferrer">
		<img class="wpengine-logo" src="<?php echo plugins_url("/../assets/img/wpe-logo.svg", __FILE__); ?>">
	</a>
	<img class="blogvault-logo" src="<?php echo plugins_url("/../assets/img/bv-logo.svg", __FILE__); ?>">
</header>
<main class="text-center">
	<div class="card">
		<form action="<?php echo $this->bvinfo->appUrl(); ?>/migration/migrate" method="post" name="signup">
			<img class="wpe-logo-lg" src="<?php echo plugins_url("/../assets/img/wpe-logo-lg.svg", __FILE__); ?>">
			<?php $this->showErrors(); ?>
			<div class="form-content">
				<label class="email-label" required>Email Address</label>
				<input type="email" name="email" placeholder="Email address" class="email-input">
				<div class="tnc-check text-center mt-2">
					<label class="normal-text horizontal">
						<input type="hidden" name="bvsrc" value="wpplugin" />
						<input type="hidden" name="migrate" value="wpengine" />
						<input type="checkbox" name="consent" onchange="document.getElementById('migratesubmit').disabled = !this.checked;" value="1" autocomplete='off'>
						<span class="checkmark"></span>&nbsp;
						I agree to WP Engine's <a href="https://wpengine.com/legal/terms-of-service/" style="text-decoration: none; color: #9579F2;">Terms of Service</a>
					</label>
				</div>
			</div>
			<?php echo $this->siteInfoTags(); ?>
			<input type="submit" name="submit" id="migratesubmit" class="button button-secondary" value="Get started" style="display: block; margin: 2rem auto 2rem;" disabled>
		</form>
	</div>
</main>