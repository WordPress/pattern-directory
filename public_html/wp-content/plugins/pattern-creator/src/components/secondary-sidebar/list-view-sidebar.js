/**
 * WordPress dependencies
 */
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalListView as ListView } from '@wordpress/block-editor';
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

// Forked from https://raw.githubusercontent.com/WordPress/gutenberg/1b9157577fce4133e9be89c1d89cdd02918f6ba3/packages/edit-site/src/components/secondary-sidebar/list-view-sidebar.js

export default function ListViewSidebar() {
	const { setIsListViewOpened } = useDispatch( patternStore );

	const focusOnMountRef = useFocusOnMount( 'firstElement' );
	const headerFocusReturnRef = useFocusReturn();
	const contentFocusReturnRef = useFocusReturn();
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
			<div className="pattern__list-view-panel-header" ref={ headerFocusReturnRef }>
				<strong id={ labelId }>{ __( 'List View', 'wporg-patterns' ) }</strong>
				<Button
					icon={ closeSmall }
					label={ __( 'Close List View Sidebar', 'wporg-patterns' ) }
					onClick={ () => setIsListViewOpened( false ) }
				/>
			</div>
			<div
				className="pattern__list-view-panel-content"
				ref={ useMergeRefs( [ contentFocusReturnRef, focusOnMountRef ] ) }
			>
				<ListView />
			</div>
		</div>
	);
}
