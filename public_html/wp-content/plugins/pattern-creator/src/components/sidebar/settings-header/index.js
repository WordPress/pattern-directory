/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../store/constants';

import { SIDEBAR_BLOCK, SIDEBAR_PATTERN } from '../constants';

const SettingsHeader = ( { sidebarName } ) => {
	const { enableComplementaryArea } = useDispatch( interfaceStore );
	const openPatternSettings = () => enableComplementaryArea( STORE_NAME, SIDEBAR_PATTERN );
	const openBlockSettings = () => enableComplementaryArea( STORE_NAME, SIDEBAR_BLOCK );

	const [ patternAriaLabel, patternActiveClass ] =
		sidebarName === SIDEBAR_PATTERN
			? // translators: ARIA label for the Template sidebar tab, selected.
			  [ __( 'Pattern (selected)', 'wporg-patterns' ), 'is-active' ]
			: // translators: ARIA label for the Template Settings Sidebar tab, not selected.
			  [ __( 'Pattern', 'wporg-patterns' ), '' ];

	const [ blockAriaLabel, blockActiveClass ] =
		sidebarName === SIDEBAR_BLOCK
			? // translators: ARIA label for the Block Settings Sidebar tab, selected.
			  [ __( 'Block (selected)', 'wporg-patterns' ), 'is-active' ]
			: // translators: ARIA label for the Block Settings Sidebar tab, not selected.
			  [ __( 'Block', 'wporg-patterns' ), '' ];

	/* Use a list so screen readers will announce how many tabs there are. */
	return (
		<ul>
			<li>
				<Button
					onClick={ openPatternSettings }
					className={ `pattern-sidebar__panel-tab ${ patternActiveClass }` }
					aria-label={ patternAriaLabel }
					// translators: Data label for the Template Settings Sidebar tab.
					data-label={ __( 'Pattern', 'wporg-patterns' ) }
				>
					{
						// translators: Text label for the Template Settings Sidebar tab.
						__( 'Pattern', 'wporg-patterns' )
					}
				</Button>
			</li>
			<li>
				<Button
					onClick={ openBlockSettings }
					className={ `pattern-sidebar__panel-tab ${ blockActiveClass }` }
					aria-label={ blockAriaLabel }
					// translators: Data label for the Block Settings Sidebar tab.
					data-label={ __( 'Block', 'wporg-patterns' ) }
				>
					{
						// translators: Text label for the Block Settings Sidebar tab.
						__( 'Block', 'wporg-patterns' )
					}
				</Button>
			</li>
		</ul>
	);
};

export default SettingsHeader;
