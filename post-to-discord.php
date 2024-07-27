<?php

/**
 * Plugin Name: Post to Discord
 * Plugin URI: https://michaels.website
 * Update URI: https://michaels.website
 * Description: WordPress plugin adding support for automatically sending messages to Discord when posts are published, based on the <a href="https://wordpress.org/plugins/wp-discord-post/">WP Discord Post</a> plugin.
 * Version: 0.3.1
 * Author: Michael Babker
 * Author URI: https://michaels.website
 * Text Domain: post-to-discord
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Tested up to: 6.6
 */

namespace BabDev\PostToDiscord;

// If this file is called directly, abort.
if ( ! \defined( 'WPINC' ) ) {
	die;
}

if ( ! \defined( __NAMESPACE__ . '\\PLUGIN_FILE' ) ) {
	/**
	 * Absolute path to this plugin file, for quick reference within the plugin classes
	 *
	 * @var string
	 */
	\define( __NAMESPACE__ . '\\PLUGIN_FILE', __FILE__ );
}

if ( ! \defined( __NAMESPACE__ . '\\VERSION' ) ) {
	/**
	 * Plugin version
	 *
	 * @var string
	 */
	\define( __NAMESPACE__ . '\\VERSION', '0.3.1' );
}

/**
 * @internal
 */
function missing_autoloader(): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "The Post to Discord plugin installation is incomplete. If installed from GitHub, ensure you've run 'composer install'." ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	add_action(
		'admin_notices',
		static function (): void {
			$notice = <<<HTML
<div class="notice notice-error">
<p>The Post to Discord plugin installation is incomplete. If installed from GitHub, ensure you've run <code>composer install</code>.</p>
</div>
HTML;

			echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	);
}

// Include the Composer autoloader and initialize it
if ( ! \file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	missing_autoloader();

	return;
}

require __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', [ Plugin::class, 'boot' ] );
