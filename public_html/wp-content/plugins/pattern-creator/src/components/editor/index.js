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
import { DropZoneProvider, Popover, SlotFillProvider } from '@wordpress/components';
import { store as editPostStore } from '@wordpress/edit-post';
import { StrictMode, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Edit-Post dependencies
 */
import EditorInitialization from '@wordpress/edit-post/build/components/editor-initialization';

/**
 * Internal dependencies
 */
import Layout from '../layout';
import WelcomeGuide from '../welcome-guide';

const AUTOSAVE_INTERVAL = 30;

export default function Provider( { settings, onError, postId, postType, initialEdits, ...props } ) {
	const post = useSelect( ( select ) => select( 'core' ).getEntityRecord( 'postType', postType, postId ) );
	const { editBlockPatternId } = useDispatch( 'wporg/block-pattern-creator' );
	// Track the currently-editing pattern ID.
	useEffect( () => {
		editBlockPatternId( postId );
	}, [ postId ] );

	// Get editor settings.
	const { keepCaretInsideBlock } = useSelect( ( select ) => {
		const { isFeatureActive } = select( editPostStore );
		return {
			keepCaretInsideBlock: isFeatureActive( 'keepCaretInsideBlock' ),
		};
	} );
	const { setIsInserterOpened } = useDispatch( editPostStore );

	// Bail early if no post yet, eventually this could be a loading state.
	if ( ! post ) {
		return null;
	}

	const editorSettings = {
		...settings,
		hasFixedToolbar: false,
		focusMode: false,
		hasReducedUI: false,
		__experimentalLocalAutosaveInterval: AUTOSAVE_INTERVAL,

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
							<AutosaveMonitor interval={ AUTOSAVE_INTERVAL } />
							<LocalAutosaveMonitor />
							<WelcomeGuide />
							<Layout />
							<Popover.Slot />
						</ErrorBoundary>
					</EditorProvider>
				</DropZoneProvider>
			</SlotFillProvider>
		</StrictMode>
	);
}
