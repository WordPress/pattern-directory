/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CategoryMenu from '../category-menu';
import CategorySearch from '../category-search';
import CategoryContextBar from '../category-context-bar';

const options = [
	{
		value: '#/pattern-categories',
		label: 'All',
	},
	{
		value: '#/pattern-categories/header',
		label: 'Header',
	},
	{
		value: '#/pattern-categories/sidebar',
		label: 'Sidebar',
	},
	{
		value: '#/pattern-categories/footer',
		label: 'Footer',
	},
	{
		value: '#/pattern-categories/gallery',
		label: 'Gallery',
	},
	{
		value: '#/pattern-categories/blog',
		label: 'Blog',
	},
	{
		value: '#/pattern-categories/media',
		label: 'Media',
	},
];

const contextMessages = {
	[ options[ 1 ].value ]: {
		message: (
			<p>
				45 <b>Header</b> patterns
			</p>
		),
		title: 'Related Categories',
		links: [
			{
				href: '/pattern-categories/media',
				label: 'Media',
			},
			{
				href: '/pattern-categories/blog',
				label: 'blog',
			},
		],
	},
	[ options[ 3 ].value ]: {
		message: (
			<p>
				23 <b>Footer</b> patterns that is a very long sentence to check for something.
			</p>
		),
		title: 'Related Categories',
		links: [
			{
				href: '/pattern-categories/media',
				label: 'Media',
			},
			{
				href: '/pattern-categories/blog',
				label: 'blog',
			},
		],
	},
};

const GridMenu = () => {
	const [ path, setPath ] = useState( options[ 1 ].value ); // This should look at the url and select the right href
	const [ categoryContext, setCategoryContext ] = useState( undefined );

	useEffect( () => {
		setCategoryContext( contextMessages[ path ] );
	}, [ path ] );

	return (
		<div className="grid-menu">
			<Flex>
				<FlexItem>
					<CategoryMenu
						path={ path }
						options={ options }
						onClick={ ( _path ) => {
							setPath( _path );
						} }
					/>
				</FlexItem>
				<FlexItem>
					<CategorySearch />
				</FlexItem>
			</Flex>
			<CategoryContextBar
				{ ...categoryContext }
			/>
		</div>
	);
};

export default GridMenu;
