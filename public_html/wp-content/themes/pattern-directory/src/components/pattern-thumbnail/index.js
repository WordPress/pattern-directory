/**
 * WordPress dependencies
 */
import { Disabled } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import Canvas from './canvas';
import CopyPatternButton from '../copy-pattern-button';
import FavoriteButton from '../favorite-button';
import IconHeartFilled from '../icons/heart-filled';

function PatternThumbnail( { pattern } ) {
	return (
		<div className="pattern-grid__pattern">
			<div className="pattern-grid__pattern-frame">
				<a href={ pattern.link } rel="bookmark">
					<span className="screen-reader-text">{ decodeEntities( pattern.title.rendered ) }</span>
					<Disabled>
						<Canvas className="pattern-grid__preview" html={ pattern.content.rendered } />
					</Disabled>
				</a>
				<div className="pattern-grid__actions">
					<FavoriteButton showLabel={ false } patternId={ pattern.id } />
					<CopyPatternButton isSmall={ true } content={ pattern.pattern_content } />
				</div>
			</div>

			<h2 className="pattern-grid__title">{ decodeEntities( pattern.title.rendered ) }</h2>
			<p className="pattern-grid__meta">
				{ pattern.author_avatar ? (
					<span className="pattern-grid__author-avatar">
						<img alt={ pattern.author_avatar.alt } src={ pattern.author_avatar.url } />
					</span>
				) : null }
				<span className="pattern-grid__favorite-count">
					<IconHeartFilled height={ 12 } width={ 12 } /> 123
				</span>
			</p>
		</div>
	);
}

export default PatternThumbnail;
