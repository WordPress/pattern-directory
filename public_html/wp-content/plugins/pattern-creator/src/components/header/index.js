/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { ToolSelector } from '@wordpress/block-editor';
import { Button, VisuallyHidden } from '@wordpress/components';
import { Icon, arrowLeft, listView, plus } from '@wordpress/icons';
import { PinnedItems } from '@wordpress/interface';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';
import { useCallback, useRef } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { useViewportMatch } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import MoreMenu from './more-menu';
import RedoButton from './undo-redo/redo';
import { SaveButton, SaveDraftButton } from '../save-button';
import { store as patternStore } from '../../store';
import UndoButton from './undo-redo/undo';

const preventDefault = ( event ) => {
	event.preventDefault();
};

export default function Header() {
	const inserterButton = useRef();
	const { hasReducedUI, isInserterOpen, isListViewOpen, listViewShortcut } = useSelect( ( select ) => {
		const { isFeatureActive, isInserterOpened, isListViewOpened } = select( patternStore );
		const { getShortcutRepresentation } = select( keyboardShortcutsStore );

		return {
			hasReducedUI: isFeatureActive( 'reducedUI' ),
			isInserterOpen: isInserterOpened(),
			isListViewOpen: isListViewOpened(),
			listViewShortcut: getShortcutRepresentation( 'core/edit-site/toggle-list-view' ),
		};
	}, [] );

	const { setIsInserterOpened, setIsListViewOpened } = useDispatch( patternStore );

	const isLargeViewport = useViewportMatch( 'medium' );

	const openInserter = useCallback( () => {
		if ( isInserterOpen ) {
			// Focusing the inserter button closes the inserter popover
			inserterButton.current.focus();
		} else {
			setIsInserterOpened( true );
		}
	}, [ isInserterOpen, setIsInserterOpened ] );

	const toggleListView = useCallback(
		() => setIsListViewOpened( ! isListViewOpen ),
		[ setIsListViewOpened, isListViewOpen ]
	);

	const classes = classnames( 'pattern-header', {
		'has-reduced-ui': hasReducedUI,
	} );

	return (
		<div className={ classes }>
			<div className="pattern-header_start">
				<a className="main-dashboard-button" href={ wporgBlockPattern.siteUrl }>
					<Icon icon={ arrowLeft } />
					<VisuallyHidden>{ __( 'Pattern Directory', 'wporg-patterns' ) }</VisuallyHidden>
				</a>
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
					<SaveDraftButton />
					<SaveButton />
					<PinnedItems.Slot scope="wporg/pattern-creator" />
					<MoreMenu />
				</div>
			</div>
		</div>
	);
}
