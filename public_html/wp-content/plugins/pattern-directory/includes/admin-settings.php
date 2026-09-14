<?php
/**
 * Admin settings for the Pattern Directory.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

namespace WordPressdotorg\Pattern_Directory\Admin\Settings;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\admin_init' );

/**
 * Constants.
 */
const PAGE_SLUG    = 'wporg-pattern-directory';
const SECTION_NAME = 'wporg-pattern-settings';

/**
 * Registers a new settings page under Settings.
 *
 * @return void
 */
function admin_menu() {
	add_options_page(
		__( 'Block Patterns', 'wporg-patterns' ),
		__( 'Block Patterns', 'wporg-patterns' ),
		'manage_options',
		PAGE_SLUG,
		__NAMESPACE__ . '\render_page'
	);
}

/**
 * Registers a new settings page under Settings.
 *
 * @return void
 */
function admin_init() {
	add_settings_section(
		SECTION_NAME,
		'',
		'__return_empty_string',
		PAGE_SLUG
	);

	// Default status.
	register_setting(
		SECTION_NAME,
		'wporg-pattern-default_status',
		array(
			'type'              => 'string',
			'sanitize_callback' => function ( $value ) {
				return in_array( $value, array( 'publish', 'pending' ), true ) ? $value : 'publish';
			},
			'default'           => 'publish',
		)
	);
	add_settings_field(
		'wporg-pattern-default_status',
		esc_html__( 'Default status of new patterns', 'wporg-patterns' ),
		__NAMESPACE__ . '\render_status_field',
		PAGE_SLUG,
		SECTION_NAME,
		array(
			'label_for' => 'wporg-pattern-default_status',
		)
	);

	// Flag threshold.
	register_setting(
		SECTION_NAME,
		'wporg-pattern-flag_threshold',
		array(
			'type'              => 'integer',
			'sanitize_callback' => function ( $value ) {
				$value = absint( $value );

				if ( $value < 1 || $value > 100 ) {
					return 5;
				}

				return $value;
			},
			'default'           => 5,
		)
	);
	add_settings_field(
		'wporg-pattern-flag_threshold',
		esc_html__( 'Flag threshold', 'wporg-patterns' ),
		__NAMESPACE__ . '\render_threshold_field',
		PAGE_SLUG,
		SECTION_NAME,
		array(
			'label_for' => 'wporg-pattern-flag_threshold',
		)
	);
}

/**
 * Render the default status field.
 *
 * @return void
 */
function render_status_field() {
	$current = get_option( 'wporg-pattern-default_status' );
	$statii  = array(
		'publish' => __( 'Published', 'wporg-patterns' ),
		'pending' => __( 'Pending', 'wporg-patterns' ),
	);

	echo '<select name="wporg-pattern-default_status" id="wporg-pattern-default_status" aria-describedby="wporg-pattern-default_status-help">';
	foreach ( $statii as $value => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $value, $current, false ), esc_html( $label ) );
	}
	echo '</select>';
	printf( '<p id="wporg-pattern-default_status-help">%s</p>', esc_html__( 'Use this setting to control whether new patterns need moderation before showing up (pending) or not (published).', 'wporg-patterns' ) );
}

/**
 * Render the flag threshold field.
 *
 * @return void
 */
function render_threshold_field() {
	$current = get_option( 'wporg-pattern-flag_threshold' );
	?>
		<input
			class="small-text"
			type="number"
			name="wporg-pattern-flag_threshold"
			id="wporg-pattern-flag_threshold"
			aria-describedby="wporg-pattern-default_status-help"
			value="<?php echo esc_attr( $current ); ?>"
		/>
	<?php
	printf(
		'<p id="wporg-pattern-flag_threshold-help">%s</p>',
		esc_html__( 'Use this setting to change the number of people who can report a pattern before it is automatically unpublished and held for moderator review.', 'wporg-patterns' )
	);
}

/**
 * Display the Block Patterns settings page.
 *
 * @return void
 */
function render_page() {
	require_once dirname( __DIR__ ) . '/views/admin-settings.php';
}
