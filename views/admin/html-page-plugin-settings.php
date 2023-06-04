<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>
	<hr class="wp-header-end">
	<form action="options.php" method="post">
		<?php settings_fields( 'post-to-discord' ); ?>
		<?php do_settings_sections( 'post-to-discord' ); ?>
		<?php submit_button(); ?>
	</form>
</div>
