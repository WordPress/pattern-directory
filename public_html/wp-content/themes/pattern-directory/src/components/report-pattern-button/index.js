/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ReportPatternModal from '../report-pattern-modal';

const ReportPatternButton = ( { postId, loggedIn, hasReview } ) => {
	const [ showModal, setShowModal ] = useState( false );
	const [ hasSubmitted, setHasSubmitted ] = useState( false );

	const getBtnText = () => {
		let btnText = __( 'Report this pattern', 'wporg-patterns' );

		if ( ! loggedIn ) {
			btnText = __( 'Login to report this pattern', 'wporg-patterns' );
		}

		if ( hasReview || hasSubmitted ) {
			btnText = __( "You've reported this pattern", 'wporg-patterns' );
		}

		return btnText;
	};

	return (
		<>
			<Button
				disabled={ ! loggedIn || hasReview || hasSubmitted }
				icon={ hasReview || hasSubmitted ? check : null }
				onClick={ () => setShowModal( true ) }
			>
				{ getBtnText() }
			</Button>
			{ showModal && (
				<ReportPatternModal
					postId={ postId }
					onClose={ ( submitted ) => {
						setShowModal( false );
						setHasSubmitted( submitted );
					} }
				/>
			) }
		</>
	);
};

export default ReportPatternButton;
