/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PublishHeader from './publish-header';
import SaveButton from './save-button';
import SettingPanels from './panels';
import { MODULE_KEY } from '../../store/utils';
import './style.css';

export default function Settings( { closeSidebar } ) {
	const { hasEdits, post } = useSelect( ( select ) => {
		const { getEditingBlockPatternId, getEditedBlockPattern, hasEditsBlockPattern } = select( MODULE_KEY );
		const patternId = getEditingBlockPatternId();
		return {
			hasEdits: hasEditsBlockPattern( patternId ),
			post: getEditedBlockPattern( patternId ),
		};
	} );
	// @todo Maybe change these statii depending on any custom statuses in the process.
	// See https://github.com/WordPress/pattern-directory/issues/16#issuecomment-725845843
	const isUnpublished = [ 'publish', 'private' ].indexOf( post.status ) === -1;

	return (
		<>
			<div className="block-pattern-creator__settings-header">
				<SaveButton isUnpublished={ isUnpublished } />
				<Button isSecondary onClick={ closeSidebar }>
					{ __( 'Cancel', 'wporg-patterns' ) }
				</Button>
			</div>
			<div className="block-pattern-creator__settings-details">
				<PublishHeader hasEdits={ hasEdits } />
			</div>
			<SettingPanels />
		</>
	);
}
