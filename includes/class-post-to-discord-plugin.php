<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Allowed Shortcodes plugin class.
 *
 * Responsible for initializing the plugin and its resources.
 */
final class Post_To_Discord_Plugin {
	/**
	 * Singleton instance of the plugin
	 */
	private static ?Post_To_Discord_Plugin $instance = null;

	/**
	 * Plugin constructor.
	 *
	 * This constructor is private to force instantiation through the {@see boot} method.
	 */
	private function __construct() {
		$this->boot_integration_classes();
	}

	/**
	 * Boots the plugin.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 */
	public static function boot(): void {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
	}

	/**
	 * Fetch the plugin's singleton instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @throws RuntimeException if trying to fetch the singleton instance before the plugin has been booted.
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			throw new RuntimeException( 'The "Post to Discord" plugin has not been booted.' );
		}

		return self::$instance;
	}

	private function boot_integration_classes(): void {
		Post_To_Discord_Activation::boot();

		if ( is_admin() ) {
			Post_To_Discord_Admin::boot();
		}
	}
}
