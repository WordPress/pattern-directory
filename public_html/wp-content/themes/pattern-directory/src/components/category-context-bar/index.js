/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

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

	const category = useSelect( ( select ) => {
		const { getCategoryBySlug } = select( patternStore );
		const categorySlug = getCategoryFromPath( path );

		return getCategoryBySlug( categorySlug );
	} );

	useEffect( () => {
		const _context = category && category.count > 0 ? getContextMessage( category.count, category.name ) : {};

		setContext( _context );
	}, [ path, category ] );

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
