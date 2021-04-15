/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { addQueryArgs, getPath } from '@wordpress/url';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';
import CategorySearch from '../category-search';
import CategoryContextBar from '../category-context-bar';
import { store as patternStore } from '../../store';
import { useRoute } from '../../hooks';
import { removeQueryString } from '../../utils';

const PatternGridMenu = () => {
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const { path, update: updatePath } = useRoute();

	const { categories, isLoading, hasLoaded } = useSelect( ( select ) => {
		const { getCategories, isLoadingCategories, hasLoadedCategories } = select( patternStore );
		return {
			categories: getCategories(),
			isLoading: isLoadingCategories(),
			hasLoaded: hasLoadedCategories(),
		};
	} );

	const onSearchChange = ( event ) => {
		event.preventDefault();
		setSearchTerm( event.target.value );
	};

	const onSearchSubmit = ( event ) => {
		event.preventDefault();

		const updatedPath = addQueryArgs( path, {
			search: searchTerm,
		} );

		updatePath( updatedPath );
	};

	return (
		<>
			<nav className="pattern-grid-menu">
				<CategoryMenu
					path={ removeQueryString( path ) }
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
				<CategorySearch
					isLoading={ isLoading }
					isVisible={ hasLoaded }
					value={ searchTerm }
					onChange={ onSearchChange }
					onSubmit={ onSearchSubmit }
				/>
			</nav>
			<CategoryContextBar />
		</>
	);
};

export default PatternGridMenu;
