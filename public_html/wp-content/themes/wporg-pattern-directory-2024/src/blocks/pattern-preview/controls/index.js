/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';

const Edit = () => <div { ...useBlockProps() }>Thumbnail</div>;

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
