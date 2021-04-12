/**
 * WordPress dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';

const StateContext = createContext();

export function RouteProvider( { children } ) {
	const [ path, setPath ] = useState( window.location.pathname );

	const _pushState = ( _path ) => {
		window.history.pushState( '', '', _path );
		setPath( _path );
	};

	return (
		<StateContext.Provider
			value={ {
				path: path,
				push: _pushState,
			} }
		>
			{ children }
		</StateContext.Provider>
	);
}

export function useRoute() {
	const context = useContext( StateContext );

	if ( context === undefined ) {
		throw new Error( 'useRoute must be used within a Provider' );
	}

	return context;
}
