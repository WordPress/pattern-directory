/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { getQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useRoute } from '../../hooks';
import { getCategoryFromPath } from '../../utils';
import { getContextMessage } from './messaging';
import { store as patternStore } from '../../store';

function CategoryContextBar() {
	const { path } = useRoute();
	const [ height, setHeight ] = useState( 0 );
	const [ context, setContext ] = useState( {} );
	const innerRef = useRef( null );

	const { category } = useSelect( ( select ) => {
		const { getCategoryBySlug } = select( patternStore );
		const categorySlug = getCategoryFromPath( path );

		return {
			category: getCategoryBySlug( categorySlug ),
		};
	}, [ path ] );

	useEffect( () => {
		if ( ! category ) {
			return;
		}

		const searchTerm = getQueryArg( path, 'search' );

		let _context = {};

		// Use the category count as default
		let count = category.count;

		// If we have a search term use the pattern results.
		if ( searchTerm ) {
			count = 12345; // TO DO get the pattern count
		}

		if ( category.id !== -1 || searchTerm ) {
			_context = getContextMessage( count, category.name, searchTerm );
		}

		setContext( _context );
	}, [ category, path ] );

	useEffect( () => {
		const _height = context.message ? innerRef.current.offsetHeight : 0;
		setHeight( _height );
	}, [ context.message ] );

	return (
		<header className="category-context__bar" style={ { height: `${ height }px` } }>
			<div ref={ innerRef }>
				<h2 className="category-context__bar__copy">{ context.message }</h2>
				{ context.links && context.links.length > 0 && (
					<div className="category-context__bar__links">
						<h3 className="category-context__bar__title">{ context.title }</h3>

						<ul>
							{ context.links.map( ( i ) => (
								<li key={ i.href }>
									<a href={ i.href }>{ i.label }</a>
								</li>
							) ) }
						</ul>
					</div>
				) }
			</div>
		</header>
	);
}

export default CategoryContextBar;
