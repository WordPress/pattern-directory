/**
 * Openverse replacement for core's media replace menu.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { Dropdown, MenuItem, NavigableMenu, ToolbarButton } from '@wordpress/components';
import { MediaUpload, store as blockEditorStore, useBlockEditContext } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { DOWN } from '@wordpress/keycodes';
import { media as mediaIcon } from '@wordpress/icons';

/**
 * Gallery settings the gallery gives images added to it, as core's `buildImageAttributes()` does.
 *
 * Core only applies them to images it can look up by ID, which Openverse media lack.
 *
 * @param {Object} gallery The gallery's attributes.
 * @param {string} url     The image's URL.
 * @return {Object} Image block attributes.
 */
function getGalleryImageAttributes( gallery, url ) {
	const { linkTo, linkTarget, sizeSlug, aspectRatio } = gallery;

	// Openverse media have no attachment page, so "attachment" links are left out.
	const link = {
		file: { href: url, linkDestination: 'media' },
		media: { href: url, linkDestination: 'media' },
		lightbox: { linkDestination: 'none', lightbox: { enabled: true } },
		none: { linkDestination: 'none' },
	}[ linkTo ];

	return {
		...link,
		linkTarget: linkTarget ? '_blank' : undefined,
		sizeSlug,
		aspectRatio: 'auto' === aspectRatio ? undefined : aspectRatio,
	};
}

/**
 * Replace or add media from Openverse, in place of core's menu, which offers only uploads and the media library.
 *
 * Patterns can't hold uploads, and authors can't upload, so core's menu is empty for them. Blocks' extra items,
 * like the cover's video URL, are left out too, since patterns only take Openverse media. Openverse only offers
 * images, so blocks that take no images only get "Reset".
 *
 * @param {Object}   props
 * @param {string}   props.name                  Toggle label.
 * @param {string[]} props.allowedTypes          Allowed media types.
 * @param {boolean}  props.multiple              Whether several items can be picked.
 * @param {number[]} props.mediaIds              IDs of the gallery's current media.
 * @param {number}   props.mediaId               ID of the current media.
 * @param {string}   props.mediaURL              URL of the current media.
 * @param {Function} props.onSelect              Called with the picked media.
 * @param {Function} props.onReset               Clears the media, when the block supports it.
 * @param {boolean}  props.useFeaturedImage      Whether the block shows the post's featured image.
 * @param {Function} props.onToggleFeaturedImage Toggles showing the featured image.
 * @param {string}   props.variant               Popover variant.
 * @param {Object}   props.popoverProps          Popover props.
 * @param {Function} props.renderToggle          Custom toggle renderer.
 * @param {string}   props.className             Dropdown class name.
 * @return {?Element} The replace menu, or nothing when it would be empty.
 */
export default function MediaReplaceFlow( {
	name = __( 'Replace', 'wporg-patterns' ),
	allowedTypes,
	multiple = false,
	mediaIds,
	mediaId,
	mediaURL,
	onSelect,
	onReset,
	useFeaturedImage,
	onToggleFeaturedImage,
	variant,
	popoverProps,
	renderToggle,
	className,
} ) {
	const { name: blockName, clientId } = useBlockEditContext();
	const { insertBlocks } = useDispatch( blockEditorStore );
	const { getBlockAttributes } = useSelect( blockEditorStore );
	const allowsImages = allowedTypes?.some( ( type ) => 'image' === type || type.startsWith( 'image/' ) );
	const canReset = !! mediaURL && !! onReset;

	if ( ! allowsImages && ! canReset ) {
		return null;
	}

	/**
	 * Hands the picked media to the block, or appends it to a gallery.
	 *
	 * @param {Object|Object[]} media The picked media.
	 */
	const selectMedia = ( media ) => {
		/*
		 * The gallery tells new images from the ones it holds by ID, and Openverse media have none, so it would add
		 * nothing, or swap out images saved without one. Append them instead, as the gallery does with new images.
		 */
		if ( multiple && 'core/gallery' === blockName ) {
			const gallery = getBlockAttributes( clientId );
			insertBlocks(
				media.map( ( { url, alt, caption } ) =>
					createBlock( 'core/image', {
						...getGalleryImageAttributes( gallery, url ),
						url,
						alt,
						caption,
					} )
				),
				undefined,
				clientId
			);
			return;
		}

		if ( useFeaturedImage && onToggleFeaturedImage ) {
			onToggleFeaturedImage();
		}
		onSelect( media );
		speak( __( 'The media file has been replaced', 'wporg-patterns' ) );
	};

	return (
		<Dropdown
			popoverProps={ { ...popoverProps, variant } }
			className={ className }
			contentClassName="block-editor-media-replace-flow__options"
			renderToggle={ ( { isOpen, onToggle } ) => {
				const toggleProps = {
					'aria-expanded': isOpen,
					'aria-haspopup': 'true',
					onClick: onToggle,
					onKeyDown: ( event ) => {
						if ( DOWN === event.keyCode ) {
							event.preventDefault();
							event.target.click();
						}
					},
				};
				return renderToggle ? (
					renderToggle( { ...toggleProps, children: name } )
				) : (
					<ToolbarButton { ...toggleProps }>{ name }</ToolbarButton>
				);
			} }
			renderContent={ ( { onClose } ) => (
				<NavigableMenu className="block-editor-media-replace-flow__media-upload-menu">
					{ allowsImages && (
						<MediaUpload
							gallery={ multiple }
							multiple={ multiple }
							value={ multiple ? mediaIds : mediaId }
							allowedTypes={ allowedTypes }
							onSelect={ ( media ) => {
								onClose();
								selectMedia( media );
							} }
							render={ ( { open } ) => (
								<MenuItem icon={ mediaIcon } onClick={ open }>
									{ __( 'Open Media Library', 'wporg-patterns' ) }
								</MenuItem>
							) }
						/>
					) }
					{ canReset && (
						<MenuItem
							onClick={ () => {
								onReset();
								onClose();
							} }
						>
							{ __( 'Reset', 'wporg-patterns' ) }
						</MenuItem>
					) }
				</NavigableMenu>
			) }
		/>
	);
}
