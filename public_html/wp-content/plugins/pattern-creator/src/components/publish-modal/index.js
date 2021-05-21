/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SubmitModal from './submit';
import SuccessModal from './success';

export default function PublishModal( { onClose } ) {
	const [ submitted, setSubmitted ] = useState( false );
	return (
		<>
			{ ! submitted ? <SubmitModal onSuccess={ setSubmitted } /> : <SuccessModal onClose={ onClose } /> }
		</>
	);
}
