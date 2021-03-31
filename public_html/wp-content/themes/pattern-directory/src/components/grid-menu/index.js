/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';

const options = [
	{
		value: '#all',
		label: 'All',
	},
	{
		value: '#header',
		label: 'Header',
	},
	{
		value: '#sidebar',
		label: 'Sidebar',
	},
	{
		value: '#footer',
		label: 'Footer',
	},
	{
		value: '#gallery',
		label: 'Gallery',
	},
];

const GridMenu = () => {
	const [ path, setPath ] = useState( '#all' ); // This should look at the url and select the right href

	return (
		<CategoryMenu
			path={ path }
			options={ options }
			onClick={ ( path ) => {
				setPath( path );
			} }
		/>
	);
};

export default GridMenu;
