/**
 * External dependencies
 */
import classnames from 'classnames';
import useDeepCompareEffect from 'use-deep-compare-effect';

/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useRoute } from '../../hooks';
import { getLoadingMessage, getMessage, getSearchMessage } from './messaging';
import { store as patternStore } from '../../store';

/**
 * Check if the query is just the "home" query, and doesn't need a message.
 *
 * If the query has no properties, or only "orderby", it is considered a "home" query.
 *
 * @param {Object} query
 * @return {boolean}
 */
const isHomeQuery = ( query ) => {
	const keys = Object.keys( query || {} );
	if ( ! keys.length ) {
		return true;
	}
	return keys.length === 1 && keys[ 0 ] === 'orderby';
};

/**
 * Check if the query is a valid query.
 *
 * @param {Object} query
 * @return {boolean}
 */
const isQueryValid = ( query = {} ) => {
	// If there is an "include", it should have items.
	if ( query.hasOwnProperty( 'include' ) ) {
		return query.include.length > 0;
	}
	return true;
};

function ContextBar( props ) {
	const { path } = useRoute();
	const [ message, setMessage ] = useState();
	const [ context ] = useState( {
		title: '',
		links: [],
	} );

	const { author, category, count, isLoadingPatterns, query } = useSelect(
		( select ) => {
			const { getCategoryById, getPatternTotalsByQuery, getQueryFromUrl, isLoadingPatternsByQuery } = select(
				patternStore
			);
			const _query = { ...getQueryFromUrl( path ), ...props.query };

			return {
				author: wporgPatternsData.currentAuthorName || _query?.author_name,
				category: getCategoryById( _query[ 'pattern-categories' ] ),
				count: getPatternTotalsByQuery( _query ),
				isLoadingPatterns: isLoadingPatternsByQuery( _query ),
				query: _query,
			};
		},
		[ path, props.query ]
	);

	useDeepCompareEffect( () => {
		// Show the loading message
		if ( isQueryValid( query ) && isLoadingPatterns ) {
			setMessage( getLoadingMessage( { category: category?.name, author: author } ) );
			return;
		}

		// We don't show a message when the query is empty.
		if ( isHomeQuery( query ) ) {
			setMessage( '' );
			return;
		}

		const searchTerm = query?.search || '';
		if ( searchTerm.length > 0 ) {
			setMessage( getSearchMessage( count, searchTerm ) );
			return;
		}

		setMessage( getMessage( { category: category?.name, author: author }, count ) );

		// Remove the context message from favorites.
		if ( query?.include && ! category ) {
			setMessage( '' );
		}
	}, [ query, isLoadingPatterns ] );

	const classes = classnames( {
		'context-bar__spinner': true,
		'context-bar__spinner--is-hidden': ! isLoadingPatterns,
	} );

	return ! message ? null : (
		<header className="context-bar">
			<h2 className="context-bar__copy">
				<span className={ classes }>
					<Spinner />
				</span>
				<span>{ message }</span>
			</h2>
			{ context.links && context.links.length > 0 && (
				<div className="context-bar__links">
					<h3 className="context-bar__title">{ context.title }</h3>

					<ul>
						{ context.links.map( ( i ) => (
							<li key={ i.href }>
								<a href={ i.href }>{ i.label }</a>
							</li>
						) ) }
					</ul>
				</div>
			) }
		</header>
	);
}

export default ContextBar;
