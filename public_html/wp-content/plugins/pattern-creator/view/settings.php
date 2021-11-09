<?php
/**
 * Pattern Creator settings.
 */

namespace WordPressdotorg\Pattern_Creator\View\Settings;
use const WordPressdotorg\Pattern_Creator\Admin\{SECTION_NAME,PAGE_SLUG};

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Block Pattern Settings', 'wporg-patterns' ); ?></h1>
	<form method="POST" action="options.php">
	<?php
		do_settings_sections( PAGE_SLUG );
		settings_fields( SECTION_NAME );
		submit_button();
	?>
	</form>
</div>
