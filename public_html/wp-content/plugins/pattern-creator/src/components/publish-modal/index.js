/**
 * Internal dependencies
 */
import SubmitModal from './submit';
import SuccessModal from './success';

export default function PublishModal( { isSubmitted, onSubmit, onClose } ) {
	return (
		<>
			{ ! isSubmitted ? (
				<SubmitModal onSubmit={ onSubmit } onClose={ onClose } />
			) : (
				<SuccessModal onClose={ onClose } />
			) }
		</>
	);
}
