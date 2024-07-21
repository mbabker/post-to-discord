<?php

namespace BabDev\PostToDiscord;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post to Discord admin class.
 *
 * Manages the WordPress admin integrations.
 */
final class Admin {
	/**
	 * Singleton instance of the integration
	 */
	private static ?self $instance = null;

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

			add_action( 'admin_menu', [ self::$instance, 'register_plugin_options_page' ] );
			add_action( 'admin_init', [ self::$instance, 'register_plugin_settings' ] );
			add_filter( 'plugin_action_links', [ self::$instance, 'modify_plugin_action_links' ], 10, 2 );
			add_filter( 'network_admin_plugin_action_links', [ self::$instance, 'modify_plugin_action_links' ], 10, 2 );
		}
	}

	/**
	 * Fetch the integration's singleton instance.
	 *
	 * Ensures only one instance of the integration is loaded or can be loaded.
	 *
	 * @throws \RuntimeException if trying to fetch the singleton instance before the integration has been booted.
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			throw new \RuntimeException( 'The "Post to Discord" admin integration has not been booted.' );
		}

		return self::$instance;
	}

	/**
	 * Adds extra links to the plugin's listing.
	 *
	 * @param string[] $actions     An array of plugin action links.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[] The filtered list of action links.
	 */
	public function modify_plugin_action_links( array $actions, string $plugin_file ): array {
		if ( plugin_basename( PLUGIN_FILE ) !== $plugin_file ) {
			return $actions;
		}

		return array_merge(
			$actions,
			[
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-general.php?page=post-to-discord' ) ),
					esc_html__( 'Settings', 'post-to-discord' ),
				),
			],
		);
	}

	/**
	 * Registers the plugin's options page.
	 */
	public function register_plugin_options_page(): void {
		add_options_page(
			__( 'Post to Discord', 'post-to-discord' ),
			__( 'Post to Discord', 'post-to-discord' ),
			'manage_options',
			'post-to-discord',
			[ $this, 'show_settings_page' ],
		);
	}

	/**
	 * Register the plugin's settings.
	 */
	public function register_plugin_settings(): void {
		register_setting( 'post-to-discord', 'post_to_discord_supported_post_types' );

		add_settings_section(
			'post-to-discord-general-settings',
			__( 'General Configuration', 'post-to-discord' ),
			'__return_null',
			'post-to-discord',
		);

		add_settings_field(
			'post_to_discord_bot_username',
			__( 'Bot Username', 'post-to-discord' ),
			[ $this, 'print_bot_username_field' ],
			'post-to-discord',
			'post-to-discord-general-settings',
			[
				'label_for' => 'post_to_discord_bot_username',
			],
		);

		add_settings_field(
			'post_to_discord_bot_avatar_url',
			__( 'Avatar URL', 'post-to-discord' ),
			[ $this, 'print_avatar_url_field' ],
			'post-to-discord',
			'post-to-discord-general-settings',
			[
				'label_for' => 'post_to_discord_bot_avatar_url',
			],
		);

		add_settings_field(
			'post_to_discord_webhook_url',
			__( 'Webhook URL', 'post-to-discord' ),
			[ $this, 'print_webhook_url_field' ],
			'post-to-discord',
			'post-to-discord-general-settings',
			[
				'label_for' => 'post_to_discord_webhook_url',
			],
		);

		add_settings_field(
			'post_to_discord_mention_everyone',
			__( 'Mention Everyone?', 'post-to-discord' ),
			[ $this, 'print_mention_everyone_field' ],
			'post-to-discord',
			'post-to-discord-general-settings',
			[
				'label_for' => 'post_to_discord_mention_everyone',
			],
		);

		add_settings_field(
			'post_to_discord_message_template',
			__( 'Message Template', 'post-to-discord' ),
			[ $this, 'print_message_template_field' ],
			'post-to-discord',
			'post-to-discord-general-settings',
			[
				'label_for' => 'post_to_discord_message_template',
			],
		);

		add_settings_field(
			'post_to_discord_supported_post_types',
			__( 'Supported Post Types', 'post-to-discord' ),
			[ $this, 'print_supported_post_types_field' ],
			'post-to-discord',
			'post-to-discord-general-settings',
			[
				'label_for' => 'post_to_discord_supported_post_types',
			],
		);
	}

	/**
	 * Prints the bot username field.
	 */
	public function print_bot_username_field(): void {
		printf(
			'<input type="text" id="post_to_discord_bot_username" name="post_to_discord_bot_username" class="regular-text code" value="%s" />',
			esc_attr( get_option( 'post_to_discord_bot_username', '' ) )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'The username for the bot account which posts to your Discord server.', 'post-to-discord' ),
		);
	}

	/**
	 * Prints the avatar URL field.
	 */
	public function print_avatar_url_field(): void {
		printf(
			'<input type="url" id="post_to_discord_bot_avatar_url" name="post_to_discord_bot_avatar_url" class="regular-text code" value="%s" />',
			esc_attr( get_option( 'post_to_discord_bot_avatar_url', '' ) )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'An optional URL for the bot avatar.', 'post-to-discord' ),
		);
	}

	/**
	 * Prints the webhook URL field.
	 */
	public function print_webhook_url_field(): void {
		printf(
			'<input type="url" id="post_to_discord_webhook_url" name="post_to_discord_webhook_url" class="regular-text code" value="%s" />',
			esc_attr( get_option( 'post_to_discord_webhook_url', '' ) )
		);

		printf(
			'<p class="description">%s</p>',
			sprintf(
				/* translators: Link to Discord "Intro to Webhooks" article. */
				__( 'The webhook URL to execute, learn more <a href="%s">on Discord</a>.', 'post-to-discord' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks',
			),
		);
	}

	/**
	 * Prints the mention everyone field.
	 */
	public function print_mention_everyone_field(): void {
		printf(
			'<label for="mention_everyone"><input type="checkbox" id="mention_everyone" name="post_to_discord_mention_everyone" value="yes"%1$s /> %2$s</label><br />',
			'yes' === strtolower( get_option( 'post_to_discord_mention_everyone', '' ) ) ? ' checked' : '',
			esc_html__( 'Mention @everyone when sending the message to Discord.', 'post-to-discord' ),
		);
	}

	/**
	 * Prints the message template field.
	 */
	public function print_message_template_field(): void {
		printf(
			'<textarea id="post_to_discord_message_template" name="post_to_discord_message_template" class="large-text code" rows="4" placeholder="%s">%s</textarea>',
			esc_attr(
				sprintf(
					/* Translators: 1: %post_type% code placeholder, 2: %title% code placeholder, 3: %author% code placeholder, 4: %url% code placeholder */
					__( 'New %1$s "%2$s" by "%3$s" (%4$s)', 'post-to-discord' ),
					'%post_type%',
					'%title%',
					'%author%',
					'%url%',
				),
			),
			esc_textarea( get_option( 'post_to_discord_message_template', '' ) )
		);

		printf(
			'<p class="description">%s</p>',
			sprintf(
				/* Translators: 1: %post_type% code placeholder, 2: %title% code placeholder, 3: %author% code placeholder, 4: %url% code placeholder */
				esc_html__( 'Customize the message template for messages sent to Discord. Supported placeholders are %1$s, %2$s, %3$s, and %4$s. HTML is not allowed.', 'post-to-discord' ),
				'%post_type%',
				'%title%',
				'%author%',
				'%url%',
			),
		);
	}

	/**
	 * Prints the supported post types field.
	 *
	 * @global array $wp_post_types List of post types.
	 */
	public function print_supported_post_types_field(): void {
		global $wp_post_types;

		$supported_post_types = get_option( 'post_to_discord_supported_post_types', [] );

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'The list of post types which will trigger a Discord webhook when they are published.', 'post-to-discord' ),
		);

		printf(
			'<fieldset><legend class="screen-reader-text">%s</legend>',
			esc_html__( 'Supported Post Types', 'post-to-discord' ),
		);

		foreach ( array_keys( $wp_post_types ) as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( null === $post_type_object || ! $post_type_object->public ) {
				continue;
			}

			printf(
				'<label for="post_type_%1$s"><input type="checkbox" id="post_type_%1$s" name="post_to_discord_supported_post_types[]" value="%1$s"%2$s /> %3$s</label><br />',
				esc_attr( $post_type ),
				in_array( $post_type, $supported_post_types, true ) ? ' checked' : '',
				esc_html( $post_type_object->label ),
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Method handling requests for the settings page.
	 *
	 * @return void
	 */
	public function show_settings_page() {
		include plugin_dir_path( PLUGIN_FILE ) . 'views/admin/html-page-plugin-settings.php';
	}
}
