/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import MediaPlaceholder from '../components/media-placeholder';

addFilter( 'editor.MediaPlaceholder', 'wporg/patterns/components/media-upload', () => MediaPlaceholder, 100 );
