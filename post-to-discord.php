<?php

/**
 * Plugin Name: Post to Discord
 * Plugin URI: https://michaels.website
 * Update URI: https://michaels.website
 * Description: WordPress plugin adding support for automatically sending messages to Discord when posts are published, based on the <a href="https://wordpress.org/plugins/wp-discord-post/">WP Discord Post</a> plugin.
 * Version: 0.2.1
 * Author: Michael Babker
 * Author URI: https://michaels.website
 * Text Domain: post-to-discord
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Tested up to: 6.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'POST_TO_DISCORD_PLUGIN_FILE' ) ) {
	/**
	 * Absolute path to this plugin file, for quick reference within the plugin classes
	 *
	 * @var string
	 */
	define( 'POST_TO_DISCORD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'POST_TO_DISCORD_VERSION' ) ) {
	/**
	 * Plugin version
	 *
	 * @var string
	 */
	define( 'POST_TO_DISCORD_VERSION', '0.2.1' );
}

// Include the autoloader and initialize it
require plugin_dir_path( POST_TO_DISCORD_PLUGIN_FILE ) . 'includes/class-post-to-discord-autoloader.php';

Post_To_Discord_Autoloader::register();

add_action( 'plugins_loaded', [ Post_To_Discord_Plugin::class, 'boot' ] );
