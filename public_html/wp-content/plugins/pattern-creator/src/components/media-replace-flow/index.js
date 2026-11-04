/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dropdown, MenuItem, NavigableMenu, ToolbarButton } from '@wordpress/components';
import { MediaUpload, store as blockEditorStore, useBlockEditContext } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { media as mediaIcon } from '@wordpress/icons';

/**
 * Replace or add media from Openverse, in place of core's menu, which offers only uploads and the media library.
 *
 * Patterns can't hold uploads, and authors can't upload, so core's menu is empty for them. Blocks' extra items,
 * like the cover's video URL, are left out too, since patterns only take Openverse media.
 *
 * @param {Object}   props
 * @param {string}   props.name         Toggle label.
 * @param {string[]} props.allowedTypes Allowed media types.
 * @param {boolean}  props.multiple     Whether several items can be picked.
 * @param {number[]} props.mediaIds     IDs of the gallery's current media.
 * @param {number}   props.mediaId      ID of the current media.
 * @param {string}   props.mediaURL     URL of the current media.
 * @param {Function} props.onSelect     Called with the picked media.
 * @param {Function} props.onReset      Clears the media, when the block supports it.
 * @param {string}   props.variant      Popover variant.
 * @param {Object}   props.popoverProps Popover props.
 * @param {Function} props.renderToggle Custom toggle renderer.
 * @param {string}   props.className    Dropdown class name.
 * @return {Element} The replace menu.
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
	variant,
	popoverProps,
	renderToggle,
	className,
} ) {
	const { name: blockName, clientId } = useBlockEditContext();
	const { insertBlocks } = useDispatch( blockEditorStore );

	const selectMedia = ( media ) => {
		/*
		 * The gallery tells new images from the ones it holds by ID, and Openverse media have none, so it would add
		 * nothing, or swap out images saved without one. Append them instead, as the gallery does with new images.
		 */
		if ( 'core/gallery' === blockName ) {
			insertBlocks(
				media.map( ( { url, alt, caption } ) => createBlock( 'core/image', { url, alt, caption } ) ),
				undefined,
				clientId
			);
			return;
		}

		onSelect( media );
	};

	return (
		<Dropdown
			popoverProps={ { ...popoverProps, variant } }
			className={ className }
			contentClassName="block-editor-media-replace-flow__options"
			renderToggle={ ( { isOpen, onToggle } ) => {
				const toggleProps = { 'aria-expanded': isOpen, 'aria-haspopup': 'true', onClick: onToggle };
				return renderToggle ? (
					renderToggle( { ...toggleProps, children: name } )
				) : (
					<ToolbarButton { ...toggleProps }>{ name }</ToolbarButton>
				);
			} }
			renderContent={ ( { onClose } ) => (
				<NavigableMenu className="block-editor-media-replace-flow__media-upload-menu">
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
					{ mediaURL && onReset && (
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
