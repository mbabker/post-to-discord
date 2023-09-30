<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post to Discord publisher class.
 *
 * Processes the Discord webhook.
 */
final class Post_To_Discord_Publisher {
	/**
	 * Singleton instance of the integration
	 */
	private static ?Post_To_Discord_Publisher $instance = null;

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

			foreach ( get_option( 'post_to_discord_supported_post_types', [] ) as $post_type ) {
				add_action( "publish_$post_type", [ self::$instance, 'publish_to_discord' ], 10, 2 );
			}
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
			throw new RuntimeException( 'The "Post to Discord" publisher integration has not been booted.' );
		}

		return self::$instance;
	}

	/**
	 * Publishes the post to Discord.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function publish_to_discord( int $post_id, WP_Post $post ): void {
		/**
		 * Checks if the post is allowed to be posted to Discord.
		 *
		 * @param bool    $is_allowed_to_publish Flag indicating the post is allowed to be published to Discord.
		 * @param WP_Post $post                  The post being published.
		 */
		$is_allowed_to_publish = apply_filters( 'post_to_discord_is_allowed_to_publish', $this->is_publishable( $post ), $post );

		if ( false === $is_allowed_to_publish ) {
			return;
		}

		$message  = $this->get_discord_post_message( $post );
		$embeds   = $this->get_discord_post_embeds( $post );
		$username = $this->get_discord_post_username( $post );
		$avatar   = $this->get_discord_post_avatar( $post );
		$webhook  = $this->get_discord_post_webhook( $post );

		// Sanity check, if the webhook URL was never set or was filtered out, bail out
		if ( '' === trim( $webhook ) ) {
			return;
		}

		$request_body = [
			'content'    => $message,
			'username'   => $username,
			'avatar_url' => esc_url( $avatar ),
		];

		if ( [] !== $embeds ) {
			$request_body['embeds'] = $this->format_embeds_for_discord( $embeds );
		}

		/**
		 * Filters the request body for the webhook message.
		 *
		 * @param array   $request_body The request body.
		 * @param WP_Post $post         The post being published.
		 */
		$request_body = apply_filters( 'post_to_discord_request_body', $request_body, $post );

		$request = [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $request_body ),
		];

		/**
		 * Filters the request payload for the webhook message.
		 *
		 * @param array   $request The request payload.
		 * @param WP_Post $post    The post being published.
		 */
		$request = apply_filters( 'post_to_discord_request', $request, $post );

		/**
		 * Fires before the request is sent to the webhook endpoint.
		 *
		 * @param array   $request The request payload.
		 * @param string  $webhook The webhook URL.
		 * @param WP_Post $post    The post being published.
		 */
		do_action( 'post_to_discord_before_request', $request, $webhook, $post );

		$response = wp_remote_post( $webhook, $request );

		/**
		 * Fires after the request is sent to the webhook endpoint.
		 *
		 * @param array|WP_Error $response The response or WP_Error on failure.
		 * @param WP_Post        $post     The post being published.
		 */
		do_action( 'post_to_discord_after_request', $response, $post );

		$code = wp_remote_retrieve_response_code( $response );

		if ( '' !== $code && 200 <= $code && 300 > $code ) {
			$this->add_published_meta( $post );
		}
	}

	private function is_publishable( WP_Post $post ): bool {
		// If the post was previously published, don't send it again
		if ( $this->already_published( $post ) ) {
			return false;
		}

		// Don't send notifications for revisions
		if ( wp_is_post_revision( $post ) ) {
			return false;
		}

		$post_date = get_post_datetime( $post, 'date', 'gmt' );

		// If we can't get the post date, assume the post is publishable
		if ( false === $post_date ) {
			return true;
		}

		// Don't publish until the post time has passed
		return new DateTimeImmutable( 'now', wp_timezone() ) >= $post_date;
	}

	private function already_published( WP_Post $post ): bool {
		if ( 'yes' === get_post_meta( $post->ID, '_post_to_discord_published', true ) ) {
			return true;
		}

		// Account for posts made with the old plugin as well
		return 'yes' === get_post_meta( $post->ID, '_wp_discord_post_published', true );
	}

	private function get_discord_post_message( WP_Post $post ): string {
		$mention_everyone = 'yes' === strtolower( get_option( 'post_to_discord_mention_everyone', '' ) );

		$message_template = get_option( 'post_to_discord_message_template', '' );

		if ( '' === trim( $message_template ) ) {
			$message_template = 'New %post_type% "%title%" by "%author%" (%url%)';
		}

		$message = strtr(
			$message_template,
			[
				'%post_type%' => get_post_type_object( $post->post_type )->labels->singular_name,
				'%title%'     => get_the_title( $post ),
				'%author%'    => $this->get_discord_post_author( $post ),
				'%url%'       => get_permalink( $post ),
			]
		);

		// If the "mention everyone" option is enabled and the template doesn't already include the mention, prepend it
		if ( $mention_everyone && false === strpos( $message, '@everyone' ) ) {
			$message = '@everyone ' . $message;
		}

		/**
		 * Filters the message content to be posted to Discord.
		 *
		 * @param string  $message The message to post to Discord.
		 * @param WP_Post $post    The post being published.
		 */
		return apply_filters( 'post_to_discord_message', $message, $post );
	}

	private function get_discord_post_embeds( WP_Post $post ): array {
		$embed = [
			'title'       => html_entity_decode( get_the_title( $post ) ),
			'description' => $this->get_discord_embed_description( $post ),
			'url'         => get_permalink( $post ),
			'timestamp'   => get_the_date( 'c', $post ),
			'image'       => $this->get_discord_embed_thumbnail( $post ),
			'author'      => $this->get_discord_post_author( $post ),
			'fields'      => [],
		];

		if ( ! empty( get_the_category_list() ) ) {
			$embed['fields'][] = [
				'name'  => esc_html( get_taxonomy( 'category' )->labels->name ),
				'value' => wp_strip_all_tags( get_the_category_list( ', ', '', $post->ID ) ),
			];
		}

		if ( ! empty( get_the_tag_list() ) ) {
			$embed['fields'][] = [
				'name'  => esc_html( get_taxonomy( 'post_tag' )->labels->name ),
				'value' => wp_strip_all_tags( get_the_tag_list( '', ', ', '', $post->ID ) ),
			];
		}

		/**
		 * Filters the list of embed objects for the Discord message.
		 *
		 * @param array   $embeds The list of embed objects for the Discord message.
		 * @param WP_Post $post   The post being published.
		 */
		return apply_filters( 'post_to_discord_embeds', [ $embed ], $post );
	}

	private function get_discord_post_username( WP_Post $post ): string {
		/**
		 * Filters the username for the bot account to post to Discord as.
		 *
		 * @param string  $username The username for the bot account to post to Discord as
		 * @param WP_Post $post     The post being published.
		 */
		return apply_filters( 'post_to_discord_bot_username', get_option( 'post_to_discord_bot_username', '' ), $post );
	}

	private function get_discord_post_avatar( WP_Post $post ): string {
		/**
		 * Filters the URL for the bot account's Discord avatar.
		 *
		 * @param string  $avatar The URL for the bot account's Discord avatar.
		 * @param WP_Post $post   The post being published.
		 */
		return apply_filters( 'post_to_discord_bot_avatar_url', get_option( 'post_to_discord_bot_avatar_url', '' ), $post );
	}

	private function get_discord_post_webhook( WP_Post $post ): string {
		/**
		 * Filters the Discord webhook URL.
		 *
		 * @param string  $webhook_url The Discord webhook URL.
		 * @param WP_Post $post        The post being published.
		 */
		return apply_filters( 'post_to_discord_webhook_url', get_option( 'post_to_discord_webhook_url', '' ), $post );
	}

	private function get_discord_embed_thumbnail( WP_Post $post ): string {
		if ( false === has_post_thumbnail( $post ) ) {
			return '';
		}

		/**
		 * Filters the image size for the embed thumbnail.
		 *
		 * @param string  $size The image size for the embed thumbnail.
		 * @param WP_Post $post The post being published.
		 */
		$size = apply_filters( 'post_to_discord_embed_thumbnail_size', 'full', $post );

		return wp_get_attachment_image_url( get_post_thumbnail_id( $post ), $size );
	}

	private function get_discord_embed_description( WP_Post $post ): string {
		$excerpt_more_filter = static fn (): string => ' ...';

		add_filter( 'excerpt_more', $excerpt_more_filter, 9999 );

		/**
		 * Filters the description for the embed.
		 *
		 * @param string  $description The description for the Discord embed.
		 * @param WP_Post $post        The post being published.
		 */
		$description = apply_filters( 'post_to_discord_embed_description', wp_trim_excerpt( '', $post ), $post );

		remove_filter( 'excerpt_more', $excerpt_more_filter );

		return $description;
	}

	private function get_discord_post_author( WP_Post $post ): string {
		/** This filter is documented in wp-includes/author-template.php */
		return apply_filters( 'the_author', get_user_by( 'ID', $post->post_author )->display_name ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	private function format_embeds_for_discord( array $embeds ): array {
		return array_map(
			static function ( array $embed ): array {
				$formatted = [
					'title'       => $embed['title'] ?? '',
					'type'        => 'rich',
					'description' => $embed['description'] ?? '',
					'url'         => $embed['url'] ?? site_url(),
					'timestamp'   => $embed['timestamp'] ?? date( 'c' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'footer'      => [
						'text'     => get_bloginfo( 'name' ),
						'icon_url' => get_site_icon_url(),
					],
					'author'      => [
						'name' => $embed['author'] ?? get_bloginfo( 'name' ),
					],
					'fields'      => $embed['fields'] ?? [],
				];

				if ( ! empty( $embed['image'] ) ) {
					$formatted['image'] = [
						'url' => $embed['image'],
					];
				}

				return $formatted;
			},
			$embeds,
		);
	}

	private function add_published_meta( WP_Post $post ): void {
		add_post_meta( $post->ID, '_post_to_discord_published', 'yes', true );
	}
}
