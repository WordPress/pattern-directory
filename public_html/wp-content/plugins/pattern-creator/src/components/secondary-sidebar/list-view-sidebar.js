/**
 * WordPress dependencies
 */
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalListView as ListView, store as blockEditorStore } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useFocusOnMount, useFocusReturn, useInstanceId, useMergeRefs } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { ESCAPE } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';

export default function ListViewSidebar() {
	const { setIsListViewOpened } = useDispatch( patternStore );

	const { clearSelectedBlock, selectBlock } = useDispatch( blockEditorStore );
	async function selectEditorBlock( clientId ) {
		await clearSelectedBlock();
		selectBlock( clientId, -1 );
	}

	const focusOnMountRef = useFocusOnMount( 'firstElement' );
	const focusReturnRef = useFocusReturn();
	function closeOnEscape( event ) {
		if ( event.keyCode === ESCAPE && ! event.defaultPrevented ) {
			setIsListViewOpened( false );
		}
	}

	const instanceId = useInstanceId( ListViewSidebar );
	const labelId = `pattern__list-view-panel-label-${ instanceId }`;

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div aria-labelledby={ labelId } className="pattern__list-view-panel" onKeyDown={ closeOnEscape }>
			<div className="pattern__list-view-panel-header">
				<strong id={ labelId }>{ __( 'List view', 'wporg-patterns' ) }</strong>
				<Button
					icon={ closeSmall }
					label={ __( 'Close list view sidebar', 'wporg-patterns' ) }
					onClick={ () => setIsListViewOpened( false ) }
				/>
			</div>
			<div
				className="pattern__list-view-panel-content"
				ref={ useMergeRefs( [ focusReturnRef, focusOnMountRef ] ) }
			>
				<ListView
					onSelect={ selectEditorBlock }
					showNestedBlocks
					__experimentalPersistentListViewFeatures
				/>
			</div>
		</div>
	);
}
