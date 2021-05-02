/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CopyPatternButton from '../copy-pattern-button';
import FavoriteButton from '../favorite-button';
import CopySuccessMessage from './copy-success-message';
import CopyGuide from './copy-guide';

const PatternPreviewActions = () => {
	const [ showSuccess, setShowSuccess ] = useState( false );
	const [ showGuide, setShowGuide ] = useState( false );

	return (
		<>
			<CopyPatternButton onSuccess={ () => setShowSuccess( true ) } />
			<FavoriteButton />
			{ showSuccess && <CopySuccessMessage onClick={ () => setShowGuide( true ) } /> }
			{ showGuide && <CopyGuide onFinish={ () => setShowGuide( false ) } /> }
		</>
	);
};

export default PatternPreviewActions;
