/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

const useInView = ( { element } ) => {
	const [ visible, setVisible ] = useState( null );
	const [ windowHeight, setWindowHeight ] = useState(
		typeof window !== 'undefined' ? window.innerHeight : null
	);

	useEffect( () => {
		if ( ! element.current ) {
			return;
		}

		setWindowHeight( window.innerHeight );
		isVisible();
		window.addEventListener( 'scroll', debounce( isVisible, 200 ) ); // eslint-disable-line @wordpress/no-global-event-listener

		return () => window.removeEventListener( 'scroll', isVisible ); // eslint-disable-line @wordpress/no-global-event-listener
	}, [ element ] );

	const isVisible = () => {
		if ( ! element.current ) {
			return;
		}
		const { top } = element.current.getBoundingClientRect();

		if ( top >= 0 && top <= windowHeight ) {
			setVisible( true );
		} else {
			setVisible( false );
		}
	};

	return visible;
};

export default useInView;
