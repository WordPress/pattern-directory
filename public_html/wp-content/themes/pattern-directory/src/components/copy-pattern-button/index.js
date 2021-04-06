/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const copyToClipboard = ( value ) => {
	const element = document.createElement( 'textarea' );
	element.setAttribute( 'readonly', '' );
	element.style = { position: 'absolute', left: '-9999px' };
	element.value = value;

	document.body.appendChild( element );
	element.select();

	const success = document.execCommand( 'copy' );
	document.body.removeChild( element );

	return success;
};

const CopyPatternButton = ( { onSuccess } ) => {
	const handleClick = () => {
		const blockData = document.getElementById( 'block-data' );
		const blockPattern = JSON.parse( decodeURIComponent( blockData.value ) );

		const success = copyToClipboard( blockPattern );

		if ( success ) {
			onSuccess();
		} else {
			// TODO Handle error case
		}
	};

	return (
		<button className="button button-primary" onClick={ handleClick }>
			{ __( 'Copy Pattern', 'wporg-patterns' ) }
		</button>
	);
};

export default CopyPatternButton;
