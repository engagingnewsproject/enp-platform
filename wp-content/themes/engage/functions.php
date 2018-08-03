<?php

include 'vendor/autoload.php';

Timber::$dirname = array('templates');

use Engage\Managers\Login;
use Engage\Managers\Permalinks;
use Engage\Managers\PostTypes\PostTypes;
use Engage\Managers\Theme;
use Engage\Managers\TinyMCE;

// Start the site
add_action('after_setup_theme', function () {
	$managers = [
		new Login(),
		new Permalinks(),
		new PostTypes(['Research', 'Team', 'Funders']),
		new TinyMCE()
	];

    new Theme($managers);
});
