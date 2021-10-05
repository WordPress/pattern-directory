/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createSlotFill } from '@wordpress/components';
import { cog } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import DefaultSidebar from './default-sidebar';
import PatternSettings from './pattern-settings';
import SettingsHeader from './settings-header';
import { SIDEBAR_BLOCK, SIDEBAR_PATTERN } from './constants';
import { STORE_NAME } from '../../store/constants';

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill( 'PatternSidebarInspector' );
export const SidebarInspectorFill = InspectorFill;

export function SidebarComplementaryAreaFills() {
	const { sidebar, isEditorSidebarOpened, hasBlockSelection } = useSelect( ( select ) => {
		const _sidebar = select( interfaceStore ).getActiveComplementaryArea( STORE_NAME );
		const _isEditorSidebarOpened = [ SIDEBAR_BLOCK, SIDEBAR_PATTERN ].includes( _sidebar );
		return {
			sidebar: _sidebar,
			isEditorSidebarOpened: _isEditorSidebarOpened,
			hasBlockSelection: !! select( blockEditorStore ).getBlockSelectionStart(),
		};
	}, [] );
	const { enableComplementaryArea } = useDispatch( interfaceStore );
	useEffect( () => {
		if ( ! isEditorSidebarOpened ) return;
		if ( hasBlockSelection ) {
			enableComplementaryArea( STORE_NAME, SIDEBAR_BLOCK );
		} else {
			enableComplementaryArea( STORE_NAME, SIDEBAR_PATTERN );
		}
	}, [ hasBlockSelection, isEditorSidebarOpened ] );
	let sidebarName = sidebar;
	if ( ! isEditorSidebarOpened ) {
		sidebarName = hasBlockSelection ? SIDEBAR_BLOCK : SIDEBAR_PATTERN;
	}
	return (
		<>
			<DefaultSidebar
				identifier={ sidebarName }
				title={ __( 'Settings', 'wporg-patterns' ) }
				icon={ cog }
				closeLabel={ __( 'Close settings sidebar', 'wporg-patterns' ) }
				header={ <SettingsHeader sidebarName={ sidebarName } /> }
				headerClassName="pattern-sidebar__panel-tabs"
			>
				{ sidebarName === SIDEBAR_PATTERN && <PatternSettings /> }
				{ sidebarName === SIDEBAR_BLOCK && <InspectorSlot bubblesVirtually /> }
			</DefaultSidebar>
		</>
	);
}
