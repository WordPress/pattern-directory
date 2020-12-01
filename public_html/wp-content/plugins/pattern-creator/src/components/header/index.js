/**
 * WordPress dependencies
 */
import { Button, ToolbarItem } from '@wordpress/components';
import { cog } from '@wordpress/icons';
import { NavigableToolbar } from '@wordpress/block-editor';

/**
 * Edit-Post dependencies
 */
import HeaderToolbar from '@wordpress/edit-post/build/components/header/header-toolbar';

/**
 * Internal dependencies
 */
import SaveButton from './save-button';
import './style.css';

export default function Header( { isInspectorOpened, openInspector, closeInspector } ) {
	return (
		<div className="block-pattern-creator__header">
			<div className="edit-post-header__toolbar">
				<HeaderToolbar />
			</div>
			<NavigableToolbar label="Test" className="block-pattern-creator__header-actions">
				<ToolbarItem as={ Button }>Preview</ToolbarItem>
				<SaveButton />
				<ToolbarItem
					as={ Button }
					isPressed={ isInspectorOpened }
					onMouseDown={ ( event ) => {
						event.preventDefault();
					} }
					onClick={ () => {
						if ( isInspectorOpened ) {
							closeInspector();
						} else {
							openInspector();
						}
					} }
					icon={ cog }
					label="Open Inspector"
				/>
			</NavigableToolbar>
		</div>
	);
}
