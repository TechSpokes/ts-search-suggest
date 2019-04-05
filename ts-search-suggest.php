<?php
/**
 * Search Suggest by TechSpokes Inc.
 *
 * @package     TechSpokes\SearchSuggest
 * @author      TechSpokes Inc.
 * @copyright   2019 TechSpokes Inc. https://techspokes.com
 * @license     GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name: Search Suggest by TechSpokes Inc.
 * Plugin URI:  https://github.com/TechSpokes/ts-search-suggest.git
 * Description: Adds auto-suggest functionality to WordPress website search form.
 * Version:     0.0.1
 * Author:      TechSpokes Inc.
 * Author URI:  https://techspokes.com
 * Text Domain: ts-search-suggest
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
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
