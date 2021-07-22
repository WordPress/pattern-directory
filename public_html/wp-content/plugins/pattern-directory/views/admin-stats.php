<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Stats;

/** @var array $schema */
/** @var array $current_data */
/** @var array $snapshot_info */
/** @var object $next_snapshot */
/** @var array $inputs */
/** @var string $export_label */
?>
<style>
	.number {
		font-weight: 700;
		text-align: right;
	}

	.widefat.but-not-too-wide {
		width: auto;
	}

	.csv-export-form-fieldset {
		display: flex;
		margin-bottom: 1rem;
	}

	.csv-export-form-field {
		display: flex;
		flex-direction: column;
		margin-right: 1rem;
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
				<td class="<?php echo ( is_numeric( $current_data[ $field_name ] ) ) ? 'number' : ''; ?>">
					<?php if ( isset( $current_data[ $field_name ] ) ) : ?>
						<?php if ( is_numeric( $current_data[ $field_name ] ) ) : ?>
							<?php echo esc_html( number_format_i18n( $current_data[ $field_name ] ) ); ?>
						<?php else : ?>
							<?php echo esc_html( $current_data[ $field_name ] ); ?>
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
					esc_html( wp_date( 'r', $next_snapshot->timestamp ) )
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
				<?php echo esc_html( number_format_i18n( $snapshot_info['total_snapshots'] ) ); ?>
			</td>
		</tr>
		<tr>
			<td>
				Earliest snapshot
			</td>
			<td>
				<?php if ( $snapshot_info['total_snapshots'] > 0 ) : ?>
					<?php echo esc_html( $snapshot_info['earliest_date'] ); ?>
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
				<?php if ( $snapshot_info['total_snapshots'] > 0 ) : ?>
					<?php echo esc_html( $snapshot_info['latest_date'] ); ?>
				<?php else : ?>
					No data.
				<?php endif; ?>
			</td>
		</tr>
		</tbody>
	</table>

	<?php if ( $snapshot_info['total_snapshots'] > 0 ) : ?>
		<h2>
			Export
		</h2>
		<p>
			Choose a date range:
		</p>
		<form class="csv-export-form" method="post">
			<fieldset class="csv-export-form-fieldset">
				<div class="csv-export-form-field">
					<label for="csv-export-date-range-start">
						Start
					</label>
					<input
						id="csv-export-date-range-start"
						class="csv-export-date-range csv-export-date-range-start"
						name="start"
						type="date"
						min="<?php echo esc_attr( $snapshot_info['earliest_date'] ); ?>"
						max="<?php echo esc_attr( $snapshot_info['latest_date'] ); ?>"
						value="<?php echo esc_attr( $inputs['start'] ); ?>"
						required
					/>
				</div>

				<div class="csv-export-form-field">
					<label for="csv-export-date-range-end">
						End
					</label>
					<input
						id="csv-export-date-range-end"
						class="csv-export-date-range csv-export-date-range-end"
						name="end"
						type="date"
						min="<?php echo esc_attr( $snapshot_info['earliest_date'] ); ?>"
						max="<?php echo esc_attr( $snapshot_info['latest_date'] ); ?>"
						value="<?php echo esc_attr( $inputs['end'] ); ?>"
						required
					/>
				</div>
			</fieldset>

			<?php wp_nonce_field( $export_label ); ?>

			<input
				class="csv-export-submit button button-primary"
				name="action"
				type="submit"
				value="<?php echo esc_attr( $export_label ); ?>"
			/>
		</form>
	<?php endif; ?>
</div>
