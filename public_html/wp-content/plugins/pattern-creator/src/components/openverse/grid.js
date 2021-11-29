/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { fetchImages } from './utils';
import OpenverseGridActions from './grid-actions';
import OpenverseGridItems from './grid-items';

function formatImageObject( item ) {
	return {
		sizes: [],
		mime: '',
		type: 'image',
		subtype: '',
		id: null, // @todo Passing item.id triggers an API request to media/[id], but leaving this out makes the `value` prop null (so replacing a selected image does not stay selected), `addToGallery` is null even when gallery items exist, etc.
		url: item.url,
		alt: '',
		link: '',
		caption: item.title,
	};
}

/* other props: addToGallery, allowedTypes, gallery, value */
export default function OpenverseGrid( { searchTerm, onClose, onSelect, multiple } ) {
	const [ debouncedSearchTerm, _setDebouncedSearchTerm ] = useState( searchTerm );
	const setDebouncedSearchTerm = useDebounce( _setDebouncedSearchTerm, 500 );

	const [ isLoading, setIsLoading ] = useState( false );
	const [ items, setItems ] = useState( [] );
	const [ selected, setSelected ] = useState( [] );
	const [ total, setTotal ] = useState( 0 );
	const hasItems = items.length > 0;

	// Set up a debounced search term, so we don't query constantly while someone is typing.
	useEffect( () => {
		setDebouncedSearchTerm( searchTerm );
	}, [ searchTerm ] );

	useEffect( () => {
		setIsLoading( true );

		if ( ! debouncedSearchTerm ) {
			setIsLoading( false );
			setItems( [] );
			setTotal( 0 );
			return;
		}

		fetchImages( debouncedSearchTerm, {
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
	}, [ debouncedSearchTerm ] );

	const onCommitSelected = useCallback( () => {
		if ( ! selected || ! selected.length ) {
			return;
		}

		if ( multiple ) {
			onSelect( selected.map( formatImageObject ) );
		} else {
			onSelect( formatImageObject( selected[ 0 ] ) );
		}

		onClose();
	}, [ selected, multiple ] );

	const onClick = useCallback(
		( newValue ) => {
			const index = selected.indexOf( newValue );
			if ( multiple ) {
				// Value already in list, remove it.
				if ( -1 !== index ) {
					setSelected( [
						...selected.slice( 0, index ),
						...selected.slice( index + 1, selected.length ),
					] );
				} else {
					setSelected( [ ...selected, newValue ] );
				}
				return;
			}
			// Value already in list, but not multiple, so set to empty array.
			if ( -1 !== index ) {
				setSelected( [] );
			} else {
				setSelected( [ newValue ] );
			}
		},
		[ selected, multiple ]
	);

	if ( isLoading ) {
		return (
			<div>
				<Spinner />
			</div>
		);
	}

	if ( ! debouncedSearchTerm.length ) {
		return (
			<div className="pattern-openverse__collection-notice">
				<p>
					{ __(
						'Patterns are required to use our collection of license-free media provided by Openverse. You won’t be able to upload or link to any other videos in your patterns.',
						'wporg-patterns'
					) }
				</p>
			</div>
		);
	}

	if ( ! hasItems ) {
		return (
			<div>
				<h1 className="pattern-openverse__title">
					{ sprintf(
						/* translators: %s: media search query */
						__( 'No results found for "%s"', 'wporg-patterns' ),
						debouncedSearchTerm
					) }
				</h1>
			</div>
		);
	}

	return (
		<div className="pattern-openverse__grid">
			<h1 className="pattern-openverse__title">
				{ debouncedSearchTerm.length
					? sprintf(
							/* translators: %d: number of results. %s: media search query */
							_n(
								'%1$s result found for "%2$s"',
								'%1$s results found for "%2$s"',
								total,
								'wporg-patterns'
							),
							new Intl.NumberFormat().format( total ),
							debouncedSearchTerm
					  )
					: sprintf(
							/* translators: %d: number of results. */
							_n( '%1$s result found', '%1$s results found', total, 'wporg-patterns' ),
							new Intl.NumberFormat().format( total )
					  ) }
			</h1>
			<OpenverseGridItems items={ items } multiple={ multiple } selected={ selected } onSelect={ onClick } />
			<p>Pagination…</p>
			<OpenverseGridActions
				items={ selected }
				onClear={ () => setSelected( [] ) }
				actions={
					<>
						<Button variant="secondary" onClick={ onClose }>
							{ __( 'Cancel', 'wporg-patterns' ) }
						</Button>
						<Button variant="primary" onClick={ onCommitSelected }>
							{ __( 'Add media', 'wporg-patterns' ) }
						</Button>
					</>
				}
			/>
		</div>
	);
}
