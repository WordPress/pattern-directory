/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Editor from '../editor';
import Header from '../header';
import Inspector from '../inspector';
import Provider from '../provider';
import './style.css';

export default function Layout( { settings, postId } ) {
	const [ patternId ] = useState( postId );
	const [ isInspectorOpened, setIsInspectorOpened ] = useState( false );
	const classNames = [ 'block-pattern-creator' ];
	if ( isInspectorOpened ) {
		classNames.push( 'is-inspector-opened' );
	}
	return (
		<Provider patternId={ patternId } blockEditorSettings={ settings }>
			<div className={ classNames.join( ' ' ) }>
				<Header
					onToggleInspector={ () =>
						isInspectorOpened ? setIsInspectorOpened( false ) : setIsInspectorOpened( true )
					}
				/>
				<Editor />
				{ isInspectorOpened && <Inspector /> }
			</div>
		</Provider>
	);
}
