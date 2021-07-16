<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Stats;

use function WordPressdotorg\Pattern_Directory\Stats\{ get_meta_field_schema, get_snapshot_data };
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\add_subpage' );

/**
 * Register an admin page under Block Patterns for viewing stats.
 *
 * @return void
 */
function add_subpage() {
	$parent_slug = add_query_arg( 'post_type', PATTERN_POST_TYPE, 'edit.php' );

	$post_type_object = get_post_type_object( PATTERN_POST_TYPE );

	add_submenu_page(
		$parent_slug,
		__( 'Pattern Stats', 'wporg-patterns' ),
		__( 'Stats', 'wporg-patterns' ),
		$post_type_object->cap->edit_posts,
		PATTERN_POST_TYPE . '-stats',
		__NAMESPACE__ . '\render_subpage'
	);
}

/**
 * Render the stats subpage.
 *
 * @return void
 */
function render_subpage() {
	$schema = get_meta_field_schema();
	$data   = get_snapshot_data();

	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Pattern Stats', 'wporg-patterns' ); ?>
		</h1>

		<p>
			This page is a work in progress. Someday there might be charts!
		</p>

		<h2>
			Right now:
		</h2>

		<table class="striped">
			<tbody>
				<?php foreach ( $schema['properties'] as $field_name => $field_schema ) : ?>
					<tr>
						<td>
							<abbr title="<?php echo esc_attr( $field_schema['description'] ); ?>">
								<?php echo esc_html( $field_name ); ?>
							</abbr>
						</td>
						<td>
							<?php if ( isset( $data[ $field_name ] ) ) : ?>
								<?php echo esc_html( $data[ $field_name ] ); ?>
							<?php else : ?>
								Data missing.
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
