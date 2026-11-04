/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalLibrary as Library } from '@wordpress/block-editor';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalUseDialog as useDialog, useViewportMatch } from '@wordpress/compose';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';

export default function InserterSidebar() {
	const { setIsInserterOpened } = useDispatch( editorStore );
	const inserter = useSelect( ( select ) => unlock( select( editorStore ) ).getInserter(), [] );

	const isMobile = useViewportMatch( 'medium', '<' );
	const closeInserter = () => setIsInserterOpened( false );
	const [ inserterDialogRef, inserterDialogProps ] = useDialog( {
		onClose: closeInserter,
	} );

	return (
		<div ref={ inserterDialogRef } { ...inserterDialogProps } className="pattern__inserter-panel">
			<div className="pattern__inserter-panel-content">
				<Library
					showInserterHelpPanel={ false }
					showMostUsedBlocks={ false }
					shouldFocusBlock={ isMobile }
					rootClientId={ inserter.rootClientId }
					__experimentalInsertionIndex={ inserter.insertionIndex }
					__experimentalFilterValue={ inserter.filterValue }
					onSelect={ inserter.onSelect }
					onClose={ closeInserter }
				/>
			</div>
		</div>
	);
}
