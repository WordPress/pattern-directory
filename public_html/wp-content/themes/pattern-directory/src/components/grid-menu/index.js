/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';
import CategorySearch from '../category-search';

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
	{
		value: '#blog',
		label: 'Blog',
	},
	{
		value: '#media',
		label: 'Media',
	},
];

const GridMenu = () => {
	const [ path, setPath ] = useState( '#all' ); // This should look at the url and select the right href

	return (
		<Flex>
			<FlexItem>
				<CategoryMenu
					path={ path }
					options={ options }
					onClick={ ( path ) => {
						setPath( path );
					} }
				/>
			</FlexItem>
			<FlexItem>
				<CategorySearch />
			</FlexItem>
		</Flex>
	);
};

export default GridMenu;
