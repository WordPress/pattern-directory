/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { getPath } from '@wordpress/url';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';
import CategorySearch from '../category-search';
import CategoryContextBar from '../category-context-bar';
import contextMessaging from './messaging';
import { store as patternStore } from '../../store';

const PatternGridMenu = () => {
	const [ path, setPath ] = useState();
	const [ categoryContext, setCategoryContext ] = useState( undefined );

	const { categories, isLoading } = useSelect( ( select ) => {
		const { getCategories, isLoadingCategories } = select( patternStore );
		return {
			categories: getCategories(),
			isLoading: isLoadingCategories(),
		};
	} );

	useEffect( () => {
		const pathOnLoad = getPath( window.location.href );
		setPath( pathOnLoad );
	}, [] );

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
					onClick={ ( _path ) => setPath( _path ) }
					isLoading={ isLoading }
				/>
				<CategorySearch isLoading={ isLoading } />
			</nav>
			<CategoryContextBar { ...categoryContext } />
		</>
	);
};

export default PatternGridMenu;
