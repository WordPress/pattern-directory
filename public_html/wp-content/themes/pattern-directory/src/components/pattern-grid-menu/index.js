/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';
import CategorySearch from '../category-search';
import CategoryContextBar from '../category-context-bar';
import contextMessaging from './messaging';
import { store as patternStore } from '../../store';
import { useRoute } from '../../hooks';

const PatternGridMenu = () => {
	const { path, push: updatePath } = useRoute();
	const [ categoryContext, setCategoryContext ] = useState( undefined );

	const { categories, isLoading } = useSelect( ( select ) => {
		const { getCategories, isLoadingCategories } = select( patternStore );
		return {
			categories: getCategories(),
			isLoading: isLoadingCategories(),
		};
	} );

	useEffect( () => {
		setCategoryContext( contextMessaging[ path ] );
	}, [ path ] );

	return (
		<>
			<nav className="pattern-grid-menu">
				<CategoryMenu
					path={ path }
					options={
						categories
							? categories.map( ( record ) => {
								return {
									value: record.link,
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
			<CategoryContextBar { ...categoryContext } />
		</>
	);
};

export default PatternGridMenu;
