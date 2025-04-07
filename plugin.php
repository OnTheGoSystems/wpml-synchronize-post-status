<?php
/*
Plugin Name: WPML Synchronize Post Status
Plugin URI: http://wpml.org
Description: Keep the status of a post in sync with its translations.
Version: 0.0.1
Author: Andrea Sciamanna
Author URI: https://www.onthegosystems.com/team/andrea-sciamanna/
*/

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_action( 'plugins_loaded', 'checkWPMLSPSrequirements' );

function checkWPMLSPSrequirements() {
	if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
		return;
	}
	require_once __DIR__ . '/src/SyncStatusOnPostUpdate.php';

	$syncStatusOnPostUpdate = new \WPML\Core\SyncStatusOnPostUpdate();
	$syncStatusOnPostUpdate->init_hooks();
}
