/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import MediaPlaceholder from '../components/media-placeholder';
import OpenverseGallery from '../components/openverse';

addFilter( 'editor.MediaPlaceholder', 'wporg/patterns/components/media-upload', () => MediaPlaceholder, 100 );
addFilter( 'editor.MediaUpload', 'wporg-patterns/openverse-media-upload', () => OpenverseGallery, 100 );
