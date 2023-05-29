<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Post to Discord Settings', 'post-to-discord' ); ?></h1>
	<hr class="wp-header-end">
	<form action="options.php" method="post">
		<?php settings_fields( 'post-to-discord' ); ?>
		<?php do_settings_sections( 'post-to-discord' ); ?>
		<?php submit_button(); ?>
	</form>
</div>
