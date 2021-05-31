/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { CheckboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import DocumentSettingPanel from '../components/document-setting-panel';
import usePostData from '../hooks/use-post-data';

/**
 * Module constants
 */
const PATTERN_CAT_KEY = 'pattern-categories';

const CategorySettingsPanel = () => {
	const categories = useSelect( ( select ) =>
		select( coreStore ).getEntityRecords( 'taxonomy', 'wporg-pattern-category', { per_page: 20 } ) || []
	);

	const [ terms, setTerms ] = usePostData( PATTERN_CAT_KEY );

	return (
		<DocumentSettingPanel
			name="wporg-categories"
			title={ __( 'Categories', 'wporg-patterns' ) }
			summary={ __(
				'Patterns are grouped into defined categories to help people browse.',
				'wporg-patterns'
			) }
		>
			<ul>
				{ categories
					.sort( ( a, b ) => a.name.localeCompare( b.name ) )
					.map( ( i ) => (
						<li key={ i.id }>
							<CheckboxControl
								label={ i.name }
								value={ i.id }
								checked={ terms.includes( i.id ) }
								onChange={ ( checked ) => {
									if ( checked ) {
										setTerms( [ ...terms, i.id ] );
									} else {
										setTerms(
											terms.filter( ( catId ) => catId !== i.id )
										);
									}
								} }
							/>
						</li>
					) ) }
			</ul>
		</DocumentSettingPanel>
	);
};
registerPlugin( 'plugin-document-setting-category-panel', {
	render: CategorySettingsPanel,
	icon: null,
} );
