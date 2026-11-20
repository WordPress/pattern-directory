/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import OpenverseThumbnail from './thumbnail';

export default function OpenverseGridActions( { actions, items, onClear } ) {
	return (
		<div className="pattern-openverse__footer">
			{ items.length ? (
				<div className="pattern-openverse__footer-selected">
					<div>
						<p className="pattern-openverse__footer-selected-label">
							{ sprintf(
								/* translators: %1$d: number of items selected. */
								_n( '%1$d item selected', '%1$d items selected', items.length, 'wporg-patterns' ),
								items.length
							) }
						</p>
						<Button variant="link" isDestructive onClick={ onClear }>
							{ __( 'Clear', 'wporg-patterns' ) }
						</Button>
					</div>
					{ items.map( ( item ) => (
						<OpenverseThumbnail key={ `thumb-${ item.id }` } item={ item } />
					) ) }
				</div>
			) : null }
			<div className="pattern-openverse__footer-actions">{ actions }</div>
		</div>
	);
}
