/**
 * Internal dependencies
 */
import PatternHeader from '../pattern-header';
import PatternPreview from '../pattern-preview';
import PatternPreviewActions from '../pattern-preview-actions';
import PatternPreviewHeader from '../pattern-preview-header';
import ReportPatternButton from '../report-pattern-button';
import { RouteProvider } from '../../hooks';

const Pattern = ( { content, postId, userHasReported, loggedIn, postTitle, categories } ) => {
	// postId as passed from the HTML dataset is a string.
	postId = Number( postId ) || 0;
	categories = JSON.parse( decodeURIComponent( categories ) );

	return (
		<RouteProvider>
			<PatternHeader isLoggedIn={ loggedIn === 'true' } />
			<PatternPreviewHeader title={ postTitle } categories={ categories } />
			<PatternPreviewActions postId={ postId } />
			<div className="pattern-preview__container">
				<PatternPreview blockContent={ content } />
				<div className="pattern__meta">
					<ReportPatternButton
						userHasReported={ userHasReported === 'true' }
						loggedIn={ loggedIn === 'true' }
						postId={ postId }
					/>
				</div>
			</div>
		</RouteProvider>
	);
};

export default Pattern;
