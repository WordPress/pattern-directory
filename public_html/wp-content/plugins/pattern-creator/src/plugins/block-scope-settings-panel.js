/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import DocumentSettingPanel from '../components/document-setting-panel';
import usePostData from '../hooks/use-post-data';

/**
 * Module constants
 */
const META_KEY = 'wpop_block_types';

const BLOCK_TYPES = [
	{
		label: 'Header',
		value: 'core/header',
	},
	{
		label: 'Footer',
		value: 'core/footer',
	},
	{
		label: 'Sidebar',
		value: 'core/sidebar',
	},
];

const BlockScopeSettingsPanel = () => {
	const [ meta, setMeta ] = usePostData( 'meta' );
	const metaBlockTypes = meta[ META_KEY ] || [];

	return (
		<DocumentSettingPanel
			name="wporg-block-scope"
			title={ __( 'Block scope', 'wporg-patterns' ) }
			summary={ __(
				'Some block display relevant patterns when inserted and edited. If your pattern is meant to work with a specific block, choose it below.',
				'wporg-patterns'
			) }
		>
			<ul>
				{ BLOCK_TYPES.map( ( i ) => (
					<li key={ i.value }>
						<CheckboxControl
							label={ i.label }
							value={ i.value }
							checked={ metaBlockTypes.includes( i.value ) }
							onChange={ ( checked ) => {
								if ( checked ) {
									setMeta( {
										...meta,
										[ META_KEY ]: [ ...metaBlockTypes, i.value ],
									} );
								} else {
									setMeta( {
										[ META_KEY ]: metaBlockTypes.filter( ( term ) => term !== i.value ),
									} );
								}
							} }
						/>
					</li>
				) ) }
			</ul>
		</DocumentSettingPanel>
	);
};
registerPlugin( 'plugin-document-setting-block-scope-panel', {
	render: BlockScopeSettingsPanel,
	icon: null,
} );
