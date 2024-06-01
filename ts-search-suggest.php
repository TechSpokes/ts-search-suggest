<?php
/**
 * Plugin Name:         Search Suggest by TechSpokes
 * Plugin URI:          https://github.com/TechSpokes/ts-search-suggest.git
 * Description:         Adds auto-suggest functionality to WordPress website search form.
 * Version:             1.0.2
 * Requires at least:   6.0
 * Requires PHP:        8.0
 * Author:              TechSpokes
 * Author URI:          https://www.techspokes.com
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:          https://www.techspokes.com
 * Text Domain:         ts-search-suggest
 * Domain Path:         /languages
 * Requires Plugins:
 */

// do not load this file directly
defined( 'ABSPATH' ) or die( sprintf( 'Please do not load %s directly', __FILE__ ) );

// load namespace
require_once( dirname( __FILE__ ) . '/autoload.php' );

// load plugin text domain
add_action( 'plugins_loaded', function () {

	load_plugin_textdomain(
		'ts-search-suggest',
		false,
		basename( dirname( __FILE__ ) . '/languages' )
	);
}, 10, 0 );

// load the plugin
add_action( 'plugins_loaded', array( 'TechSpokes\SearchSuggest\Core', 'getInstance' ), 10, 0 );
