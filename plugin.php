<?php
/*
Plugin Name: WPML Synchronize Post Status
Plugin URI: http://wpml.org
Description: Keep the status of a post in sync with its translations.
Version: 0.0.1
Author: Andrea Sciamanna
Author URI: https://www.onthegosystems.com/team/andrea-sciamanna/
*/

namespace WPML\Core;

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once __DIR__ . '/src/SyncStatusOnPostUpdate.php';

$syncStatusOnPostUpdate = new SyncStatusOnPostUpdate();
$syncStatusOnPostUpdate->init_hooks();
