/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { ifViewportMatches } from '@wordpress/viewport';

const updateIndicatorLocation = ( container, { left, width } ) => {
	if ( ! container ) {
		return;
	}

	container.style.backgroundPositionX = `${ left }px`;
	container.style.backgroundSize = `${ width }px 100%`;
};

const DefaultMenu = ( { path, options, onClick } ) => {
	const containerRef = useRef( null );
	const activeRef = useRef( null );

	useEffect( () => {
		if ( ! containerRef || ! containerRef.current || ! activeRef || ! activeRef.current ) {
			return;
		}

		const rect = activeRef.current.getBoundingClientRect();

		updateIndicatorLocation( containerRef.current, rect );
	}, [ containerRef, activeRef, path ] );

	return (
		<ul className="category-menu" ref={ containerRef }>
			{ options.map( ( i ) => (
				<li key={ i.value }>
					<a
						href={ i.value }
						ref={ path === i.value ? activeRef : null }
						onClick={ ( { target } ) => onClick( target.hash ) }
					>
						{ i.label }
					</a>
				</li>
			) ) }
		</ul>
	);
};

export default ifViewportMatches( '>= medium' )( DefaultMenu );
