/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import Canvas from './canvas';
import CopyPatternButton from '../copy-pattern-button';
import FavoriteButton from '../favorite-button';
import IconHeartFilled from '../icons/heart-filled';

function getStatusLabel( pattern ) {
	switch ( pattern.status ) {
		case 'pending':
			return __( 'Pending', 'wporg-patterns' );
		case 'draft':
			return __( 'Draft', 'wporg-patterns' );
		case 'declined':
			return __( 'Declined', 'wporg-patterns' );
	}
	return '';
}

function PatternThumbnail( { pattern, showAvatar } ) {
	const statusLabel = getStatusLabel( pattern );
	return (
		<div className="pattern-grid__pattern">
			<div className="pattern-grid__pattern-frame">
				<a href={ pattern.link } rel="bookmark">
					<span className="screen-reader-text">{ decodeEntities( pattern.title.rendered ) }</span>
					<Disabled>
						<Canvas className="pattern-grid__preview" html={ pattern.content.rendered } />
					</Disabled>
				</a>
				{ statusLabel ? (
					<div className={ `pattern-grid__status is-${ pattern.status }` }>
						<span>{ statusLabel }</span>
					</div>
				) : null }
				<div className="pattern-grid__actions">
					<FavoriteButton showLabel={ false } patternId={ pattern.id } />
					<CopyPatternButton isSmall={ true } content={ pattern.pattern_content } />
				</div>
			</div>

			<h2 className="pattern-grid__title">{ decodeEntities( pattern.title.rendered ) }</h2>
			<p className="pattern-grid__meta">
				{ showAvatar && pattern.author_avatar ? (
					<span className="pattern-grid__author-avatar">
						<img alt={ pattern.author_avatar.alt } src={ pattern.author_avatar.url } />
					</span>
				) : null }
				{ pattern.favorite_count > 0 ? (
					<span className="pattern-grid__favorite-count">
						<IconHeartFilled height={ 12 } width={ 12 } /> { pattern.favorite_count }
					</span>
				) : null }
			</p>
		</div>
	);
}

export default PatternThumbnail;
