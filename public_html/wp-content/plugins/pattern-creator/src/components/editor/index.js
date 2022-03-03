/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { AsyncModeProvider, useDispatch, useSelect } from '@wordpress/data';
import { BlockBreadcrumb } from '@wordpress/block-editor';
import { createInterpolateElement, useCallback, useEffect, useState } from '@wordpress/element';
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
import UrlController from '../url-controller';
import WelcomeGuide from '../welcome-guide';

const interfaceLabels = {
	secondarySidebar: __( 'Block Library', 'wporg-patterns' ),
	actions: __( 'Editor publish', 'wporg-patterns' ),
};

function Editor( { onError, postId } ) {
	const { isInserterOpen, isListViewOpen, post, sidebarIsOpened, settings } = useSelect( ( select ) => {
		const { isInserterOpened, isListViewOpened, getSettings } = select( patternStore );
		const { getEntityRecord } = select( coreStore );

		return {
			isInserterOpen: isInserterOpened(),
			isListViewOpen: isListViewOpened(),
			post: getEntityRecord( 'postType', POST_TYPE, postId ),
			sidebarIsOpened: !! select( interfaceStore ).getActiveComplementaryArea( patternStore.name ),
			settings: getSettings(),
		};
	}, [] );
	const { setIsInserterOpened } = useDispatch( patternStore );
	const { createInfoNotice } = useDispatch( noticesStore );
	const [ isEntitiesSavedStatesOpen, setIsEntitiesSavedStatesOpen ] = useState( false );
	const closeEntitiesSavedStates = useCallback( () => {
		setIsEntitiesSavedStatesOpen( false );
	}, [] );
	const openEntitiesSavedStates = useCallback( () => {
		setIsEntitiesSavedStatesOpen( true );
	}, [] );

	useEffect( () => {
		if ( ! wporgLocale.id.startsWith( 'en_' ) ) {
			createInfoNotice( __( 'Patterns should be submitted in English.', 'wporg-patterns' ), {
				isDismissible: false,
			} );
		}
	}, [] );

	// Don't render the Editor until the settings are set and loaded
	if ( ! settings?.siteUrl || ! post ) {
		return null;
	}

	const secondarySidebar = () => {
		if ( isInserterOpen ) {
			return <InserterSidebar />;
		}
		if ( isListViewOpen ) {
			return (
				<AsyncModeProvider value="true">
					<ListViewSidebar />
				</AsyncModeProvider>
			);
		}
		return null;
	};

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
						<InterfaceSkeleton
							className="pattern-interface"
							labels={ interfaceLabels }
							secondarySidebar={ secondarySidebar() }
							sidebar={ sidebarIsOpened && <ComplementaryArea.Slot scope="wporg/pattern-creator" /> }
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
						<WelcomeGuide />
						<Popover.Slot />
					</ErrorBoundary>
				</EditorProvider>
			</SlotFillProvider>
		</ShortcutProvider>
	);
}
export default Editor;
