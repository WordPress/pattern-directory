/**
 * External dependencies
 */
import { flow } from 'lodash';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../../store';

export default function FeatureToggle( { feature, label, info, messageActivated, messageDeactivated } ) {
	const speakMessage = () => {
		if ( isActive ) {
			speak( messageDeactivated || __( 'Feature deactivated', 'wporg-patterns' ) );
		} else {
			speak( messageActivated || __( 'Feature activated', 'wporg-patterns' ) );
		}
	};

	const isActive = useSelect( ( select ) => {
		return select( patternStore ).isFeatureActive( feature );
	}, [] );

	const { toggleFeature } = useDispatch( patternStore );

	return (
		<MenuItem
			icon={ isActive && check }
			isSelected={ isActive }
			onClick={ flow( toggleFeature.bind( null, feature ), speakMessage ) }
			role="menuitemcheckbox"
			info={ info }
		>
			{ label }
		</MenuItem>
	);
}
