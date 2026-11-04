/* global wporgLocale */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { BlockBreadcrumb } from '@wordpress/block-editor';
import { useCallback, useEffect, useState } from '@wordpress/element';
import {
	ComplementaryArea,
	FullscreenMode,
	InterfaceSkeleton,
	store as interfaceStore,
} from '@wordpress/interface';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';
import {
	EditorNotices,
	EditorProvider,
	EditorSnackbars,
	ErrorBoundary,
	store as editorStore,
	UnsavedChangesWarning,
} from '@wordpress/editor';
import { Notice, Popover, SlotFillProvider } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import BlockEditor from '../block-editor';
import EntitiesSavedStates from './save-sidebar';
import Header from '../header';
import InserterSidebar from '../secondary-sidebar/inserter-sidebar';
import KeyboardShortcuts from '../keyboard-shortcuts';
import ListViewSidebar from '../secondary-sidebar/list-view-sidebar';
import { POST_TYPE, store as patternStore } from '../../store';
import { SidebarComplementaryAreaFills } from '../sidebar';
import { STORE_NAME } from '../../store/constants';
import UrlController from '../url-controller';
import WelcomeGuide from '../welcome-guide';

const interfaceLabels = {
	secondarySidebar: __( 'Block Library', 'wporg-patterns' ),
	actions: __( 'Editor publish', 'wporg-patterns' ),
};

/**
 * The editor chrome, rendered inside `EditorProvider`.
 *
 * `EditorProvider` gives its children a sub-registry with their own `core/editor` store, so the inserter and
 * List View state have to be read here, where the header and block editor change it.
 *
 * @param {Object}  props
 * @param {?number} props.postId The pattern being edited.
 * @return {Element} The editor interface.
 */
function EditorLayout( { postId } ) {
	const { isInserterOpen, isListViewOpen, sidebarIsOpened } = useSelect(
		( select ) => ( {
			isInserterOpen: select( editorStore ).isInserterOpened(),
			isListViewOpen: select( editorStore ).isListViewOpened(),
			sidebarIsOpened: !! select( interfaceStore ).getActiveComplementaryArea( STORE_NAME ),
		} ),
		[]
	);
	const { setIsInserterOpened } = useDispatch( editorStore );
	const [ isEntitiesSavedStatesOpen, setIsEntitiesSavedStatesOpen ] = useState( false );
	const closeEntitiesSavedStates = useCallback( () => {
		setIsEntitiesSavedStatesOpen( false );
	}, [] );
	const openEntitiesSavedStates = useCallback( () => {
		setIsEntitiesSavedStatesOpen( true );
	}, [] );

	let secondarySidebar = null;
	if ( isInserterOpen ) {
		secondarySidebar = <InserterSidebar />;
	} else if ( isListViewOpen ) {
		secondarySidebar = <ListViewSidebar />;
	}

	return (
		<InterfaceSkeleton
			className="pattern-interface"
			labels={ interfaceLabels }
			secondarySidebar={ secondarySidebar }
			sidebar={ sidebarIsOpened && <ComplementaryArea.Slot scope={ STORE_NAME } /> }
			header={ <Header /> }
			notices={ <EditorSnackbars /> }
			content={
				<>
					<EditorNotices />
					<BlockEditor setIsInserterOpen={ setIsInserterOpened } />
					{ ! postId && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								"You attempted to edit an item that doesn't exist. Perhaps it was deleted?",
								'wporg-patterns'
							) }
						</Notice>
					) }
					<KeyboardShortcuts />
				</>
			}
			actions={
				<EntitiesSavedStates
					closeEntitiesSavedStates={ closeEntitiesSavedStates }
					openEntitiesSavedStates={ openEntitiesSavedStates }
					isEntitiesSavedStatesOpen={ isEntitiesSavedStatesOpen }
				/>
			}
			footer={ <BlockBreadcrumb /> }
		/>
	);
}

/**
 * Load the pattern and settings, then mount the editor providers around the layout.
 *
 * @param {Object}   props
 * @param {Function} props.onError Reboots the editor after an unhandled error.
 * @param {?number}  props.postId  The pattern being edited.
 * @return {?Element} The editor, or nothing while the pattern loads.
 */
function Editor( { onError, postId } ) {
	const { post, settings } = useSelect(
		( select ) => ( {
			post: select( coreStore ).getEntityRecord( 'postType', POST_TYPE, postId ),
			settings: select( patternStore ).getSettings(),
		} ),
		[ postId ]
	);
	const { createInfoNotice } = useDispatch( noticesStore );

	useEffect( () => {
		if ( ! wporgLocale.id.startsWith( 'en_' ) ) {
			createInfoNotice( __( 'Patterns should be submitted in English.', 'wporg-patterns' ), {
				isDismissible: false,
			} );
		}
	}, [ createInfoNotice ] );

	// Don't render the Editor until the settings are set and loaded
	if ( ! settings?.siteUrl || ! post ) {
		return null;
	}

	return (
		<ShortcutProvider>
			<SlotFillProvider>
				<EditorProvider settings={ settings } post={ post }>
					<ErrorBoundary onError={ onError }>
						<FullscreenMode isActive />
						<UrlController postId={ postId } />
						<UnsavedChangesWarning />
						<KeyboardShortcuts.Register />
						<SidebarComplementaryAreaFills />
						<EditorLayout postId={ postId } />
						<WelcomeGuide />
						<Popover.Slot />
					</ErrorBoundary>
				</EditorProvider>
			</SlotFillProvider>
		</ShortcutProvider>
	);
}
export default Editor;
