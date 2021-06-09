/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import DocumentSettingPanel from '../components/document-setting-panel';
import usePostData from '../hooks/use-post-data';

/**
 * Module constants
 */
const PATTERN_KEYWORD_KEY = 'pattern-keywords';

const KeywordSettingPanel = () => {
	const suggestions = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'wporg-pattern-keyword', { per_page: 20 } ) || []
	);

	const [ terms, setTerms ] = usePostData( PATTERN_KEYWORD_KEY );

	return (
		<DocumentSettingPanel
			name="wporg-keywords"
			title={ __( 'Keywords', 'wporg-patterns' ) }
			summary={ __(
				'Adding keywords will help people find your pattern when searching and browsing.',
				'wporg-patterns'
			) }
		>
			<FormTokenField
				value={ terms }
				label={ __( 'Add a keyword', 'wporg-patterns' ) }
				suggestions={ suggestions.map( ( i ) => i.name ) }
				onChange={ setTerms }
				__experimentalShowHowTo={ false }
			/>
		</DocumentSettingPanel>
	);
};
registerPlugin( 'plugin-document-setting-keyword-panel', {
	render: KeywordSettingPanel,
	icon: null,
} );
