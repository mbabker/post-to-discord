<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Activation integration class.
 *
 * Responsible for registering hooks to run when the plugin is activated, deactivated, and checking for post-update actions.
 */
final class Post_To_Discord_Activation {
	/**
	 * Singleton instance of the integration
	 */
	private static ?Post_To_Discord_Activation $instance = null;

	/**
	 * Integration constructor.
	 *
	 * This constructor is private to force instantiation through the {@see boot} method.
	 */
	private function __construct() {
	}

	/**
	 * Boots the integration.
	 */
	public static function boot(): void {
		if ( self::$instance === null ) {
			self::$instance = new self();

			register_activation_hook( POST_TO_DISCORD_PLUGIN_FILE, [ self::$instance, 'install' ] );
			add_action( 'init', [ self::$instance, 'check_and_update_plugin' ] );
		}
	}

	/**
	 * Fetch the integration's singleton instance.
	 *
	 * Ensures only one instance of the integration is loaded or can be loaded.
	 *
	 * @throws RuntimeException if trying to fetch the singleton instance before the integration has been booted.
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			throw new RuntimeException( 'The "Post to Discord" activation integration has not been booted.' );
		}

		return self::$instance;
	}

	/**
	 * Ensures the plugin is updated and applies updates if necessary.
	 */
	public function check_and_update_plugin(): void {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'post_to_discord_version' ), POST_TO_DISCORD_VERSION, '<' ) ) {
			$this->install();

			/**
			 * Fires after the plugin is updated.
			 */
			do_action( 'post_to_discord_updated' );
		}
	}

	/**
	 * Activation hook to run when the plugin is installed.
	 */
	public function install(): void {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine
		if ( get_transient( 'post_to_discord_installing' ) === 'yes' ) {
			return;
		}

		// If we made it to here nothing is running yet, lets set the transient now
		set_transient( 'post_to_discord_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		$this->set_default_options();
		$this->update_plugin_version();

		delete_transient( 'post_to_discord_installing' );

		/**
		 * Fires after the plugin is installed.
		 */
		do_action( 'post_to_discord_installed' );
	}

	/**
	 * Set the default options for the plugin.
	 */
	private function set_default_options(): void {
		if ( false === get_option( 'post_to_discord_bot_username', false ) ) {
			add_option( 'post_to_discord_bot_username', '', '', false );
		}

		if ( false === get_option( 'post_to_discord_bot_avatar_url', false ) ) {
			add_option( 'post_to_discord_bot_avatar_url', '', '', false );
		}

		if ( false === get_option( 'post_to_discord_webhook_url', false ) ) {
			add_option( 'post_to_discord_webhook_url', '', '', false );
		}

		if ( false === get_option( 'post_to_discord_mention_everyone', false ) ) {
			add_option( 'post_to_discord_mention_everyone', '', '', false );
		}

		if ( false === get_option( 'post_to_discord_message_template', false ) ) {
			add_option( 'post_to_discord_message_template', 'New %post_type% "%title%" by "%author%" (%url%)', '', false );
		}

		if ( false === get_option( 'post_to_discord_supported_post_types', false ) ) {
			$post_types = [];

			if ( post_type_exists( 'post' ) ) {
				$post_types[] = 'post';
			}

			if ( post_type_exists( 'page' ) ) {
				$post_types[] = 'page';
			}

			add_option( 'post_to_discord_supported_post_types', $post_types, '', false );
		}
	}

	/**
	 * Updates the plugin's version option
	 */
	private function update_plugin_version(): void {
		delete_option( 'post_to_discord_version' );
		add_option( 'post_to_discord_version', POST_TO_DISCORD_VERSION, '', false );
	}
}
