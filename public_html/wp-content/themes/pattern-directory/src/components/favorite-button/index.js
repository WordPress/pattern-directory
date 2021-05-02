/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import IconHeartOutline from '../icons/heart-outline';
import IconHeartFilled from '../icons/heart-filled';

const FavoriteButton = ( { showLabel = true } ) => {
	// @todo Implement a real favoriting process.
	const [ isFavorite, setFavorite ] = useState( Math.random() < 0.3 );
	const onClick = () => setFavorite( ! isFavorite );

	const buttonClasses = classnames( 'button button-link pattern__favorite-button', {
		'is-favorited': isFavorite,
		'has-label': showLabel,
	} );
	const labelClasses = classnames( {
		'screen-reader-text': ! showLabel,
	} );

	return (
		<button className={ buttonClasses } onClick={ onClick }>
			<IconHeartFilled className="pattern__favorite-filled" />
			<IconHeartOutline className="pattern__favorite-outline" />
			<span className={ labelClasses }>
				{ isFavorite
					? __( 'Remove from favorites', 'wporg-patterns' )
					: __( 'Add to favorites', 'wporg-patterns' ) }
			</span>
		</button>
	);
};

export default FavoriteButton;
