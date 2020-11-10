/**
 * External dependencies
 */
import { BlockEditorProvider } from '@wordpress/block-editor';
import { DropZoneProvider, FocusReturnProvider, SlotFillProvider } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { useEntityBlockEditor } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { POST_TYPE } from '../../store/utils';

export default function Provider( { blockEditorSettings, patternId, ...props } ) {
	const [ blocks, onInput, onChange ] = useEntityBlockEditor( 'postType', POST_TYPE, { id: patternId } );
	const { editBlockPatternId } = useDispatch( 'wporg/block-pattern-creator' );
	useEffect( () => {
		editBlockPatternId( patternId );
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
