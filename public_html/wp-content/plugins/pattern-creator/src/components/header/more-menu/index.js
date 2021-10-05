/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { DropdownMenu, MenuGroup } from '@wordpress/components';
import { ActionItem } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import FeatureToggle from '../feature-toggle';
import ToolsMoreMenuGroup from '../tools-more-menu-group';

const POPOVER_PROPS = {
	className: 'pattern-more-menu__content',
	position: 'bottom left',
};
const TOGGLE_PROPS = {
	tooltipPosition: 'bottom',
};

const MoreMenu = () => (
	<DropdownMenu
		className="pattern-more-menu"
		icon={ moreVertical }
		label={ __( 'More tools & options', 'wporg-patterns' ) }
		popoverProps={ POPOVER_PROPS }
		toggleProps={ TOGGLE_PROPS }
	>
		{ ( { onClose } ) => (
			<>
				<MenuGroup label={ _x( 'View', 'noun', 'wporg-patterns' ) }>
					<FeatureToggle
						feature="fixedToolbar"
						label={ __( 'Top toolbar', 'wporg-patterns' ) }
						info={ __( 'Access all block and document tools in a single place', 'wporg-patterns' ) }
						messageActivated={ __( 'Top toolbar activated', 'wporg-patterns' ) }
						messageDeactivated={ __( 'Top toolbar deactivated', 'wporg-patterns' ) }
					/>
					<FeatureToggle
						feature="focusMode"
						label={ __( 'Spotlight mode', 'wporg-patterns' ) }
						info={ __( 'Focus on one block at a time', 'wporg-patterns' ) }
						messageActivated={ __( 'Spotlight mode activated', 'wporg-patterns' ) }
						messageDeactivated={ __( 'Spotlight mode deactivated', 'wporg-patterns' ) }
					/>
					<FeatureToggle
						feature="reducedUI"
						label={ __( 'Reduced the interface', 'wporg-patterns' ) }
						info={ __( 'Compacts options and outlines in the toolbar.', 'wporg-patterns' ) }
					/>
					<FeatureToggle
						feature="patternWelcomeGuide"
						label={ __( 'Welcome guide', 'wporg-patterns' ) }
					/>
					<ActionItem.Slot
						name="wporg/pattern-creator/plugin-more-menu"
						label={ __( 'Plugins', 'wporg-patterns' ) }
						as={ MenuGroup }
						fillProps={ { onClick: onClose } }
					/>
				</MenuGroup>
				<ToolsMoreMenuGroup.Slot fillProps={ { onClose } } />
			</>
		) }
	</DropdownMenu>
);

export default MoreMenu;
