/**
 * External dependencies
 */
import {
	AutosaveMonitor,
	EditorProvider,
	ErrorBoundary,
	LocalAutosaveMonitor,
	UnsavedChangesWarning,
} from '@wordpress/editor';
import { DropZoneProvider, FocusReturnProvider, Popover, SlotFillProvider } from '@wordpress/components';
import { StrictMode, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Edit-Post dependencies
 */
import EditorInitialization from '@wordpress/edit-post/build/components/editor-initialization';
import SettingsSidebar from '@wordpress/edit-post/build/components/sidebar/settings-sidebar';

/**
 * Internal dependencies
 */
import Layout from '../layout';

export default function Provider( { settings, onError, postId, postType, initialEdits, ...props } ) {
	const post = useSelect( ( select ) => select( 'core' ).getEntityRecord( 'postType', postType, postId ) );
	const { editBlockPatternId } = useDispatch( 'wporg/block-pattern-creator' );
	// Track the currently-editing pattern ID.
	useEffect( () => {
		editBlockPatternId( postId );
	}, [ postId ] );

	// Get editor settings.
	const { keepCaretInsideBlock } = useSelect( ( select ) => {
		const { isFeatureActive } = select( 'core/edit-post' );
		return {
			keepCaretInsideBlock: isFeatureActive( 'keepCaretInsideBlock' ),
		};
	} );
	const { setIsInserterOpened } = useDispatch( 'core/edit-post' );

	// Bail early if no post yet, eventually this could be a loading state.
	if ( ! post ) {
		return null;
	}

	const editorSettings = {
		...settings,
		hasFixedToolbar: false,
		focusMode: false,
		hasReducedUI: false,
		__experimentalLocalAutosaveInterval: 30,

		// This is marked as experimental to give time for the quick inserter to mature.
		__experimentalSetIsInserterOpened: setIsInserterOpened,
		keepCaretInsideBlock: keepCaretInsideBlock,
	};

	return (
		<StrictMode>
			<SlotFillProvider>
				<DropZoneProvider>
					<EditorProvider
						settings={ editorSettings }
						post={ post }
						initialEdits={ initialEdits }
						useSubRegistry={ false }
						{ ...props }
					>
						<ErrorBoundary onError={ onError }>
							<EditorInitialization postId={ postId } />
							<UnsavedChangesWarning />
							<AutosaveMonitor />
							<LocalAutosaveMonitor />
							<SettingsSidebar />
							<FocusReturnProvider>
								<Layout />
								<Popover.Slot />
							</FocusReturnProvider>
						</ErrorBoundary>
					</EditorProvider>
				</DropZoneProvider>
			</SlotFillProvider>
		</StrictMode>
	);
}
