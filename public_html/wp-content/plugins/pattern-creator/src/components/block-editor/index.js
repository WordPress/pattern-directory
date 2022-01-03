/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useRef } from '@wordpress/element';
import { useEntityBlockEditor } from '@wordpress/core-data';
/* eslint-disable @wordpress/no-unsafe-wp-apis */
import {
	BlockEditorProvider,
	BlockInspector,
	BlockList,
	BlockTools,
	__unstableEditorStyles as EditorStyles,
	__unstableIframe as Iframe,
	__unstableUseMouseMoveTypingReset as useMouseMoveTypingReset,
	__experimentalUseResizeCanvas as useResizeCanvas,
	__unstableUseTypingObserver as useTypingObserver,
} from '@wordpress/block-editor';
/* eslint-enable @wordpress/no-unsafe-wp-apis */
import { PostTitle, VisualEditorGlobalKeyboardShortcuts } from '@wordpress/editor';
import { useMergeRefs } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { POST_TYPE, store as patternStore } from '../../store';
import { SidebarInspectorFill } from '../sidebar';

const LAYOUT = {
	type: 'default',
	// At the root level of the site editor, no alignments should be allowed.
	alignments: [],
};

export default function BlockEditor( { setIsInserterOpen } ) {
	const { settings, deviceType } = useSelect(
		( select ) => {
			const { getSettings, getPreviewDeviceType } = select( patternStore );
			return {
				settings: getSettings( setIsInserterOpen ),
				deviceType: getPreviewDeviceType(),
			};
		},
		[ setIsInserterOpen ]
	);
	const [ blocks, onInput, onChange ] = useEntityBlockEditor( 'postType', POST_TYPE );
	const resizedCanvasStyles = useResizeCanvas( deviceType, true );
	const ref = useMouseMoveTypingReset();
	const contentRef = useRef();
	const mergedRefs = useMergeRefs( [ contentRef, useTypingObserver() ] );

	return (
		<BlockEditorProvider
			settings={ settings }
			value={ blocks }
			onInput={ onInput }
			onChange={ onChange }
			useSubRegistry={ false }
		>
			<SidebarInspectorFill>
				<BlockInspector />
			</SidebarInspectorFill>
			<BlockTools className="pattern-visual-editor" __unstableContentRef={ contentRef }>
				<VisualEditorGlobalKeyboardShortcuts />
				<div className="pattern-visual-editor__post-title-wrapper">
					<PostTitle />
				</div>
				<Iframe
					style={ resizedCanvasStyles }
					assets={ settings.__unstableResolvedAssets }
					head={ <EditorStyles styles={ settings.styles } /> }
					ref={ ref }
					contentRef={ mergedRefs }
					name="editor-canvas"
				>
					<BlockList
						className="pattern-block-editor__block-list wp-site-blocks"
						__experimentalLayout={ LAYOUT }
					/>
				</Iframe>
			</BlockTools>
		</BlockEditorProvider>
	);
}
