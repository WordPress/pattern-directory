/**
 * External dependencies
 */
import { BlockEditorProvider } from '@wordpress/block-editor';
import { DropZoneProvider, FocusReturnProvider, SlotFillProvider } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { useEntityBlockEditor } from '@wordpress/core-data';

export default function Provider( { blockEditorSettings, patternId, ...props } ) {
	const [ blocks, onInput, onChange ] = useEntityBlockEditor( 'postType', 'wp-pattern', { id: patternId } );
	const { editBlockPattern } = useDispatch( 'wporg/block-pattern-creator' );
	useEffect( () => {
		editBlockPattern( patternId );
	}, [ patternId ] );

	return (
		<SlotFillProvider>
			<DropZoneProvider>
				<FocusReturnProvider>
					<BlockEditorProvider
						value={ blocks }
						onInput={ onInput }
						onChange={ onChange }
						settings={ blockEditorSettings }
						{ ...props }
					/>
				</FocusReturnProvider>
			</DropZoneProvider>
		</SlotFillProvider>
	);
}
