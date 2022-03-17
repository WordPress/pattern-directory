<?php
/**
 * Pattern Creator settings.
 */

namespace WordPressdotorg\Pattern_Directory\View\Settings;

use const WordPressdotorg\Pattern_Directory\Admin\Settings\{ SECTION_NAME, PAGE_SLUG };

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Block Pattern Settings', 'wporg-patterns' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
		<?php
			do_settings_sections( PAGE_SLUG );
			settings_fields( SECTION_NAME );
			submit_button();
		?>
	</form>
</div>
