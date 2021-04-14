/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { getPath } from '@wordpress/url';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';
import CategorySearch from '../category-search';
import CategoryContextBar from '../category-context-bar';
import { store as patternStore } from '../../store';
import { useRoute } from '../../hooks';

const PatternGridMenu = () => {
	const { path, push: updatePath } = useRoute();

	const { categories, isLoading } = useSelect( ( select ) => {
		const { getCategories, isLoadingCategories } = select( patternStore );
		return {
			categories: getCategories(),
			isLoading: isLoadingCategories(),
		};
	} );

	return (
		<>
			<nav className="pattern-grid-menu">
				<CategoryMenu
					path={ path }
					options={
						categories
							? categories.map( ( record ) => {
								return {
									value: `/${ getPath( record.link ) || '' }`,
									label: record.name,
								};
							} )
							: []
					}
					onClick={ ( event ) => {
						event.preventDefault();
						updatePath( event.target.pathname );
					} }
					isLoading={ isLoading }
				/>
				<CategorySearch isLoading={ isLoading } />
			</nav>
			<CategoryContextBar />
		</>
	);
};

export default PatternGridMenu;
