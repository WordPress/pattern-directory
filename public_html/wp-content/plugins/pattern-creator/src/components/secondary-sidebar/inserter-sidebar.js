/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalLibrary as Library } from '@wordpress/block-editor';
import { close } from '@wordpress/icons';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalUseDialog as useDialog, useViewportMatch } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';

export default function InserterSidebar() {
	const { setIsInserterOpened } = useDispatch( patternStore );
	const insertionPoint = useSelect( ( select ) => select( patternStore ).__experimentalGetInsertionPoint(), [] );

	const isMobile = useViewportMatch( 'medium', '<' );
	const [ inserterDialogRef, inserterDialogProps ] = useDialog( {
		onClose: () => setIsInserterOpened( false ),
	} );

	return (
		<div ref={ inserterDialogRef } { ...inserterDialogProps } className="pattern__inserter-panel">
			<div className="pattern__inserter-panel-header">
				<Button icon={ close } onClick={ () => setIsInserterOpened( false ) } />
			</div>
			<div className="pattern__inserter-panel-content">
				<Library
					showInserterHelpPanel={ false }
					showMostUsedBlocks={ false }
					shouldFocusBlock={ isMobile }
					rootClientId={ insertionPoint.rootClientId }
					__experimentalInsertionIndex={ insertionPoint.insertionIndex }
				/>
			</div>
		</div>
	);
}
