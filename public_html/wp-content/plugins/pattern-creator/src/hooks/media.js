/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import MediaPlaceholder from '../components/media-placeholder';
import MediaReplaceFlow from '../components/media-replace-flow';
import OpenverseGallery from '../components/openverse';

addFilter( 'editor.MediaPlaceholder', 'wporg/patterns/components/media-upload', () => MediaPlaceholder, 100 );
addFilter( 'editor.MediaUpload', 'wporg-patterns/openverse-media-upload', () => OpenverseGallery, 100 );
addFilter( 'editor.MediaReplaceFlow', 'wporg-patterns/openverse-media-replace-flow', () => MediaReplaceFlow, 100 );
