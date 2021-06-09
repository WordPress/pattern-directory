/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { store as coreStore } from '@wordpress/core-data';
import { store as patternStore } from '../../store';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Menu from './menu';
import PatternGrid from '../pattern-grid';
import QueryMonitor from '../query-monitor';
import { RouteProvider } from '../../hooks';

const MyPatterns = () => {
	const query = useSelect( ( select ) => select( patternStore ).getCurrentQuery() );
	const author = useSelect( ( select ) => select( coreStore ).getCurrentUser()?.id );

	if ( ! author ) {
		const loginUrl = addQueryArgs( wporgLoginUrl, { redirect_to: window.location } );
		return (
			<div className="entry-content">
				<p>{ __( 'Please log in to view your patterns.', 'wporg-patterns' ) }</p>
				<a className="button button-primary" href={ loginUrl }>
					{ __( 'Log in', 'wporg-patterns' ) }
				</a>
			</div>
		);
	}

	return (
		<RouteProvider>
			<QueryMonitor />
			<Menu />
			<PatternGrid query={ { ...query, author } } />
		</RouteProvider>
	);
};

export default MyPatterns;
