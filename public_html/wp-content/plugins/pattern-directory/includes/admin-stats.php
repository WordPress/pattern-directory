<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Stats;

use function WordPressdotorg\Pattern_Directory\Stats\{ get_meta_field_schema, get_snapshot_data, get_snapshots };
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
	$schema         = get_meta_field_schema();
	$data           = get_snapshot_data();
	$snapshot_query = get_snapshots( array(), true );
	$snapshots      = $snapshot_query->get_posts();
	$next_snapshot  = wp_get_scheduled_event( PATTERN_POST_TYPE . '_record_snapshot' );

	?>
	<style>
		.number {
			font-weight: 700;
			text-align: right;
		}

		.widefat.but-not-too-wide {
			width: auto;
		}
	</style>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Pattern Stats', 'wporg-patterns' ); ?>
		</h1>

		<p>
			This page is a work in progress. Someday there might be charts!
		</p>

		<h2>
			Right now
		</h2>

		<table class="widefat but-not-too-wide striped">
			<thead>
				<tr>
					<th>
						Meta Key
					</th>
					<td>
						Description
					</td>
					<td>
						Value
					</td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $schema['properties'] as $field_name => $field_schema ) : ?>
					<tr>
						<th>
							<code><?php echo esc_html( $field_name ); ?></code>
						</th>
						<td>
							<?php echo esc_html( $field_schema['description'] ); ?>
						</td>
						<td class="<?php echo ( is_numeric( $data[ $field_name ] ) ) ? 'number' : ''; ?>">
							<?php if ( isset( $data[ $field_name ] ) ) : ?>
								<?php if ( is_numeric( $data[ $field_name ] ) ) : ?>
									<?php echo esc_html( number_format_i18n( $data[ $field_name ] ) ); ?>
								<?php else : ?>
									<?php echo esc_html( $data[ $field_name ] ); ?>
								<?php endif; ?>
							<?php else : ?>
								Data missing.
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2>
			Snapshots
		</h2>

		<p>
			Snapshot frequency should be daily at around 00:00 UTC.
			<strong>
				<?php if ( $next_snapshot ) : ?>
					<?php
					printf(
						'The next snapshot will be captured on %s.',
						wp_date( 'r', $next_snapshot->timestamp )
					);
					?>
				<?php else : ?>
					No snapshot is currently scheduled.
				<?php endif; ?>
			</strong>
		</p>

		<table class="widefat but-not-too-wide striped">
			<tbody>
				<tr>
					<td>
						Number of snapshots
					</td>
					<td class="number">
						<?php echo esc_html( number_format_i18n( $snapshot_query->found_posts ) ); ?>
					</td>
				</tr>
				<tr>
					<td>
						Earliest snapshot
					</td>
					<td>
						<?php if ( $snapshot_query->found_posts > 0 ) : ?>
							<?php echo esc_html( reset( $snapshots )->post_title ); ?>
						<?php else : ?>
							No data.
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td>
						Latest snapshot
					</td>
					<td>
						<?php if ( $snapshot_query->found_posts > 0 ) : ?>
							<?php echo esc_html( end( $snapshots )->post_title ); ?>
						<?php else : ?>
							No data.
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<h2>
			Export
		</h2>
		<p>
			Coming soon! 🎉
		</p>
	</div>
	<?php
}
