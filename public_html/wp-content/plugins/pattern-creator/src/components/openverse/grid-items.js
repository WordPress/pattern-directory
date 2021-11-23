/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	/* eslint-disable @wordpress/no-unsafe-wp-apis -- Composite is OK. */
	Button,
	__unstableComposite as Composite,
	__unstableCompositeItem as CompositeItem,
	__unstableUseCompositeState as useCompositeState,
	/* eslint-enable @wordpress/no-unsafe-wp-apis */
} from '@wordpress/components';

export default function OpenverseGridItems( { items, selected, onSelect } ) {
	const composite = useCompositeState();

	if ( ! items.length ) {
		return null;
	}

	return (
		<Composite
			{ ...composite }
			className="pattern-openverse__grid-items"
			role="listbox"
			aria-label={ __( 'Openverse Media', 'wporg-patterns' ) }
		>
			{ items.map( ( item ) => {
				const classes = classnames( {
					'pattern-openverse__grid-item': true,
					'is-selected': selected.includes( item ),
				} );
				return (
					<CompositeItem
						key={ item.id }
						role="option"
						as={ Button }
						{ ...composite }
						label={ item.title }
						className={ classes }
						onClick={ ( event ) => {
							event.preventDefault();
							onSelect( item );
						} }
					>
						<img src={ item.thumbnail } alt={ item.title } />
					</CompositeItem>
				);
			} ) }
		</Composite>
	);
}
