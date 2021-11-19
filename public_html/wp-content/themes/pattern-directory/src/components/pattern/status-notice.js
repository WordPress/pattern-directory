/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Modal, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';

function getUnlistedReason( pattern ) {
	const reasonSlug = pattern.meta.wpop_unlisted_reason || '';
	switch ( reasonSlug ) {
		case '1-inappropriate':
			return __(
				'This pattern contains content deemed inappropriate for a general audience.',
				'wporg-patterns'
			);
		case '2-copyright':
			return __(
				'This pattern contains copyrighted material or uses a trademark without permission.',
				'wporg-patterns'
			);
		case '3-broken':
			return __( 'This pattern is broken or does not display correctly.', 'wporg-patterns' );
		default:
			return __( 'Additional review has been requested for this pattern.', 'wporg-patterns' );
	}
}

export default function ( { pattern } ) {
	const [ showModal, setShowModal ] = useState( false );
	const openModal = () => setShowModal( true );
	const closeModal = () => setShowModal( false );

	switch ( pattern.status ) {
		case 'pending':
			return (
				<>
					<Notice
						className="pattern__status-notice"
						status="warning"
						isDismissible={ false }
						actions={ [
							{
								label: __( 'Learn More', 'wporg-patterns' ),
								onClick: openModal,
								variant: 'secondary',
							},
						] }
					>
						<p>
							<strong>{ __( 'Review pending.', 'wporg-patterns' ) }</strong>
							{ __(
								'This pattern is only visible to you. Once approved it will be published to everyone.',
								'wporg-patterns'
							) }
						</p>
					</Notice>
					{ showModal && (
						<Modal
							title={ __( 'Review Pending', 'wporg-patterns' ) }
							onRequestClose={ closeModal }
							className="pattern__status-notice-modal"
						>
							<p>
								{ __(
									'All patterns submitted to WordPress.org are subject to both automated and manual approval. It might take a few days for your pattern to be approved.',
									'wporg-patterns'
								) }
							</p>
							<p>
								{ __(
									'Reviewers look for content that may be problematic (copyright or trademark issues) and whether your pattern works as intended.',
									'wporg-patterns'
								) }
							</p>
						</Modal>
					) }
				</>
			);

		case 'draft':
			return (
				<>
					<Notice
						className="pattern__status-notice"
						status="info"
						isDismissible={ false }
						actions={ [
							{
								label: __( 'Learn More', 'wporg-patterns' ),
								onClick: openModal,
								variant: 'secondary',
							},
						] }
					>
						<p>
							<strong>{ __( 'Saved as draft.', 'wporg-patterns' ) }</strong>
							{ __(
								'This pattern is only visible to you. When you’re ready, submit it to be published to everyone.',
								'wporg-patterns'
							) }
						</p>
					</Notice>
					{ showModal && (
						<Modal
							title={ __( 'Drafts', 'wporg-patterns' ) }
							onRequestClose={ closeModal }
							className="pattern__status-notice-modal"
						>
							<p>
								{ __(
									'Patterns can be saved as a draft which can be submitted for approval at any time. This allows you to save your design and come back to it later to submit.',
									'wporg-patterns'
								) }
							</p>
						</Modal>
					) }
				</>
			);

		case 'unlisted':
			return (
				<>
					<Notice
						className="pattern__status-notice"
						status="error"
						isDismissible={ false }
						actions={ [
							{
								label: __( 'Learn More', 'wporg-patterns' ),
								onClick: openModal,
								variant: 'secondary',
							},
						] }
					>
						<p>
							<strong>{ __( 'Pattern declined.', 'wporg-patterns' ) }</strong>
							{ __(
								'WordPress.org has chosen not to include this pattern in the directory.',
								'wporg-patterns'
							) }
						</p>
					</Notice>
					{ showModal && (
						<Modal
							title={ __( 'Declined', 'wporg-patterns' ) }
							onRequestClose={ closeModal }
							className="pattern__status-notice-modal"
						>
							<p>
								{ __(
									'WordPress.org has removed your pattern from the directory for the following reason:',
									'wporg-patterns'
								) }
							</p>
							<p>
								<em>{ getUnlistedReason( pattern ) }</em>
							</p>
							<p>
								{ __(
									'You can update your pattern to resubmitt it for approval at any time.',
									'wporg-patterns'
								) }
							</p>
						</Modal>
					) }
				</>
			);

		case 'publish':
			return (
				<Notice className="pattern__status-notice" status="success" isDismissible={ false }>
					<p>
						<strong>{ __( 'Pattern published!', 'wporg-patterns' ) }</strong>
						{ __( 'Your new design is now available to everyone.', 'wporg-patterns' ) }
					</p>
				</Notice>
			);
	}

	return null;
}
