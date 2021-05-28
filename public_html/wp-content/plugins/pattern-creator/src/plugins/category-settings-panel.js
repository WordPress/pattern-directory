/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { CheckboxControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import DocumentPanel from '../components/document-panel';

/**
 * Module constants
 */
const PATTERN_CAT_KEY = 'pattern-categories';

const CategorySettingsPanel = () => {
	const categories = useSelect( ( select ) =>
		select( coreStore ).getEntityRecords( 'taxonomy', 'wporg-pattern-category', { per_page: 20 } )
	);

	const patternCategories = useSelect(
		( select ) => select( store ).getEditedPostAttribute( PATTERN_CAT_KEY ) || []
	);

	const { editPost } = useDispatch( store );

	const updateCategoryList = ( newCategories ) => {
		editPost( {
			[ PATTERN_CAT_KEY ]: newCategories,
		} );
	};

	if ( ! categories || ! categories.length ) {
		return null;
	}

	return (
		<DocumentPanel
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
								checked={ patternCategories.includes( i.id ) }
								onChange={ ( checked ) => {
									if ( checked ) {
										updateCategoryList( [ ...patternCategories, i.id ] );
									} else {
										updateCategoryList(
											patternCategories.filter( ( catId ) => catId !== i.id )
										);
									}
								} }
							/>
						</li>
					) ) }
			</ul>
		</DocumentPanel>
	);
};
registerPlugin( 'plugin-document-setting-category-panel', {
	render: CategorySettingsPanel,
	icon: null,
} );
