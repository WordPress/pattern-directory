/**
 * WordPress dependencies
 */
import {
	/* eslint-disable @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
	BlockList,
	__unstableEditorStyles as EditorStyles,
	WritingFlow,
	__experimentalBlockSettingsMenuFirstItem,
	__unstableUseBlockSelectionClearer as useBlockSelectionClearer,
	__unstableUseCanvasClickRedirect as useCanvasClickRedirect,
	__unstableUseClipboardHandler as useClipboardHandler,
	__experimentalUseResizeCanvas as useResizeCanvas,
	__unstableUseTypewriter as useTypewriter,
	__unstableUseTypingObserver as useTypingObserver,
	/* eslint-enable */
} from '@wordpress/block-editor';
import { Popover } from '@wordpress/components';
import { store as editPostStore } from '@wordpress/edit-post';
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useMergeRefs } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import BlockInspectorButton from './block-inspector-button';
import './style.css';

/**
 * This is a copy of packages/edit-post/src/components/visual-editor/index.js
 * Copied from Gutenberg@10.2.0
 */

export default function VisualEditor( { styles } ) {
	const ref = useRef();
	const { deviceType } = useSelect( ( select ) => {
		const { __experimentalGetPreviewDeviceType } = select( editPostStore );
		return {
			deviceType: __experimentalGetPreviewDeviceType(),
		};
	}, [] );
	const desktopCanvasStyles = {
		height: '100%',
		// Add a constant padding for the typewritter effect. When typing at the
		// bottom, there needs to be room to scroll up.
		paddingBottom: '40vh',
	};
	const resizedCanvasStyles = useResizeCanvas( deviceType );

	const mergedRefs = useMergeRefs( [
		ref,
		useClipboardHandler(),
		useCanvasClickRedirect(),
		useTypewriter(),
		useBlockSelectionClearer(),
		useTypingObserver(),
	] );

	return (
		<div className="block-pattern-creator__editor">
			<EditorStyles styles={ styles } />
			<Popover.Slot name="block-toolbar" />
			<div
				ref={ mergedRefs }
				className="editor-styles-wrapper"
				style={ resizedCanvasStyles || desktopCanvasStyles }
			>
				<WritingFlow>
					<BlockList />
				</WritingFlow>
			</div>
			<__experimentalBlockSettingsMenuFirstItem>
				{ ( { onClose } ) => <BlockInspectorButton onClick={ onClose } /> }
			</__experimentalBlockSettingsMenuFirstItem>
		</div>
	);
}
