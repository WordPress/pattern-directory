/**
 * WordPress dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function PublishedModal( { onClose } ) {
	return (
		<Modal className="pattern-modal pattern-modal-published" onRequestClose={ onClose }>
			<div className="pattern-modal-published__page">
				<h3 className="pattern-modal__title">
					{ __( 'Thank you for sharing your pattern!', 'wporg-patterns' ) }
				</h3>
				<p className="pattern-modal__copy">
					{ __(
						"Your pattern is pending review. We'll email you when its been published in the public directory.",
						'wporg-patterns'
					) }
				</p>
				<div className="pattern-modal-published__content">
					<Button isPrimary>{ __( 'Close', 'wporg-patterns' ) }</Button>
					<Button className="pattern-modal__link" isLink>{ __( 'Create another pattern', 'wporg-patterns' ) }</Button>
					<Button className="pattern-modal__link" isLink>{ __( 'View my patterns', 'wporg-patterns' ) }</Button>
				</div>
			</div>
		</Modal>
	);
}
