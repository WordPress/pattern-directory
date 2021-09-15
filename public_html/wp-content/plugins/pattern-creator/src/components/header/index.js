/**
 * WordPress dependencies
 */
import { useCallback, useRef } from '@wordpress/element';
import { useViewportMatch } from '@wordpress/compose';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalPreviewOptions as PreviewOptions, ToolSelector } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { PinnedItems } from '@wordpress/interface';
import { __, _x } from '@wordpress/i18n';
import { listView, plus } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import MoreMenu from './more-menu';
import SaveButton from '../save-button';
import UndoButton from './undo-redo/undo';
import RedoButton from './undo-redo/redo';
import { POST_TYPE, store as patternStore } from '../../store';

const preventDefault = ( event ) => {
	event.preventDefault();
};

export default function Header() {
	const inserterButton = useRef();
	const { deviceType, isInserterOpen, isListViewOpen, listViewShortcut } = useSelect( ( select ) => {
		const { getPreviewDeviceType, isInserterOpened, isListViewOpened } = select( patternStore );
		const { getCurrentPostId } = select( editorStore );
		const { getEditedEntityRecord } = select( coreStore );
		const { getShortcutRepresentation } = select( keyboardShortcutsStore );

		const postId = getCurrentPostId();
		const record = getEditedEntityRecord( 'postType', POST_TYPE, postId );
		const _entityTitle = record?.slug;
		const _isLoaded = !! postId;

		return {
			deviceType: getPreviewDeviceType(),
			entityTitle: _entityTitle,
			isLoaded: _isLoaded,
			template: record,
			isInserterOpen: isInserterOpened(),
			isListViewOpen: isListViewOpened(),
			listViewShortcut: getShortcutRepresentation( 'core/edit-site/toggle-list-view' ),
		};
	}, [] );

	const { setPreviewDeviceType, setIsInserterOpened, setIsListViewOpened } = useDispatch( patternStore );

	const isLargeViewport = useViewportMatch( 'medium' );

	const openInserter = useCallback( () => {
		if ( isInserterOpen ) {
			// Focusing the inserter button closes the inserter popover
			inserterButton.current.focus();
		} else {
			setIsInserterOpened( true );
		}
	}, [ isInserterOpen, setIsInserterOpened ] );

	const toggleListView = useCallback( () => setIsListViewOpened( ! isListViewOpen ), [
		setIsListViewOpened,
		isListViewOpen,
	] );

	return (
		<div className="pattern-header">
			<div className="pattern-header_start">
				<div className="pattern-header__toolbar">
					<Button
						ref={ inserterButton }
						variant="primary"
						isPressed={ isInserterOpen }
						className="pattern-header-toolbar__inserter-toggle"
						onMouseDown={ preventDefault }
						onClick={ openInserter }
						icon={ plus }
						label={ _x(
							'Toggle block inserter',
							'Generic label for block inserter button',
							'wporg-patterns'
						) }
					/>
					{ isLargeViewport && (
						<>
							<ToolSelector />
							<UndoButton />
							<RedoButton />
							<Button
								className="pattern-header-toolbar__list-view-toggle"
								icon={ listView }
								isPressed={ isListViewOpen }
								/* translators: button label text should, if possible, be under 16 characters. */
								label={ __( 'List View', 'wporg-patterns' ) }
								onClick={ toggleListView }
								shortcut={ listViewShortcut }
							/>
						</>
					) }
				</div>
			</div>

			<div className="pattern-header_end">
				<div className="pattern-header__actions">
					<PreviewOptions deviceType={ deviceType } setDeviceType={ setPreviewDeviceType } />
					<SaveButton />
					<PinnedItems.Slot scope="wporg/pattern-creator" />
					<MoreMenu />
				</div>
			</div>
		</div>
	);
}
