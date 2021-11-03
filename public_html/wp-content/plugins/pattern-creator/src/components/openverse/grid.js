/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { fetchImages } from './utils';

function formatImageObject( item ) {
	return {
		sizes: [],
		mime: '',
		type: '',
		subtype: '',
		id: item.id,
		url: item.url,
		alt: '',
		link: '',
		caption: item.title,
	};
}

export default function OpenverseGrid( { filterValue, onSelect } ) {
	const [ debouncedValue, _setDebouncedValue ] = useState( filterValue );
	const setDebouncedValue = useDebounce( _setDebouncedValue, 500 );

	const [ isLoading, setIsLoading ] = useState( false );
	const [ items, setItems ] = useState( [] );
	const [ total, setTotal ] = useState( 0 );
	const hasItems = items.length > 0;

	// Set up a debounced search term, so we don't query constantly while someone is typing.
	useEffect( () => {
		setDebouncedValue( filterValue );
	}, [ filterValue ] );

	useEffect( () => {
		setIsLoading( true );
		fetchImages( debouncedValue, {
			onSuccess: ( data ) => {
				setIsLoading( false );
				setItems( data.results );
				setTotal( data.result_count );
			},
			onError: () => {
				setIsLoading( false );
				setItems( [] );
				setTotal( 0 );
			},
		} );
	}, [ debouncedValue ] );

	if ( isLoading ) {
		return (
			<div>
				<Spinner />
			</div>
		);
	}

	if ( ! hasItems ) {
		return (
			<div>
				<h1 className="pattern-openverse__title">
					{ filterValue.length
						? sprintf(
								/* translators: %s: media search query */
								__( 'No results found for "%2$s"', 'wporg-patterns' ),
								filterValue
						  )
						: __( 'No results found', 'wporg-patterns' ) }
				</h1>
			</div>
		);
	}

	return (
		<div>
			<h1 className="pattern-openverse__title">
				{ filterValue.length
					? sprintf(
							/* translators: %d: number of results. %s: media search query */
							_n(
								'%1$d result found for "%2$s"',
								'%1$d results found for "%2$s"',
								total,
								'wporg-patterns'
							),
							total,
							filterValue
					  )
					: sprintf(
							/* translators: %d: number of results. */
							_n( '%1$d result found', '%1$d results found', total, 'wporg-patterns' ),
							total
					  ) }
			</h1>
			<div className="pattern-openverse__grid">
				{ items.map( ( item ) => (
					<div key={ item.id } onClick={ () => onSelect( formatImageObject( item ) ) }>
						<img src={ item.thumbnail } alt="" />
					</div>
				) ) }
			</div>
		</div>
	);
}
