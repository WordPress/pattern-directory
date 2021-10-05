/**
 * WordPress dependencies
 */
import { Button, createSlotFill } from '@wordpress/components';
import { EntitiesSavedStates, PostPublishPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

// @todo bring back pre/post publish plugin slots.

const { Fill, Slot } = createSlotFill( 'ActionsPanel' );

export const ActionsPanelFill = Fill;

export default function ActionsPanel( {
	closeEntitiesSavedStates,
	openEntitiesSavedStates,
	isEntitiesSavedStatesOpen,
} ) {
	let unmountableContent;
	if ( isEntitiesSavedStatesOpen ) {
		unmountableContent = <PostPublishPanel onClose={ closeEntitiesSavedStates } />;
	} else {
		unmountableContent = (
			<div className="pattern-editor__toggle-publish-panel">
				<Button
					variant="secondary"
					className="pattern-editor__toggle-publish-panel-button"
					onClick={ openEntitiesSavedStates }
					aria-expanded={ false }
				>
					{ __( 'Open publish panel', 'wporg-patterns' ) }
				</Button>
			</div>
		);
	}

	// Since EntitiesSavedStates controls its own panel, we can keep it
	// always mounted to retain its own component state (such as checkboxes).
	return (
		<>
			{ isEntitiesSavedStatesOpen && <EntitiesSavedStates close={ closeEntitiesSavedStates } /> }
			<Slot bubblesVirtually />
			{ ! isEntitiesSavedStatesOpen && unmountableContent }
		</>
	);
}
