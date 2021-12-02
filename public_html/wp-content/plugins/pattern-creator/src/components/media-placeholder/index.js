/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Placeholder } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

export default function MediaPlaceholder( {
	addToGallery,
	allowedTypes = [],
	className,
	children,
	disableMediaButtons,
	icon,
	isAppender,
	labels = {},
	mediaPreview,
	multiple = false,
	notices,
	onSelect,
	placeholder,
	style,
	value = {},
} ) {
	if ( disableMediaButtons ) {
		return null;
	}

	const onlyAllowsImages = allowedTypes?.every(
		( allowedType ) => allowedType === 'image' || allowedType.startsWith( 'image/' )
	);
	const allowsImages = allowedTypes?.some(
		( allowedType ) => allowedType === 'image' || allowedType.startsWith( 'image/' )
	);
	const [ firstAllowedType ] = allowedTypes;
	const isOneType = 1 === allowedTypes.length;
	const isAudio = isOneType && 'audio' === firstAllowedType;
	const isImage = isOneType && 'image' === firstAllowedType;
	const isVideo = isOneType && 'video' === firstAllowedType;

	const defaultRenderPlaceholder = ( content ) => {
		let title = labels.title;
		if ( title === undefined ) {
			if ( isImage ) {
				title = __( 'Image', 'wporg-patterns' );
			} else if ( isAudio ) {
				title = __( 'Audio', 'wporg-patterns' );
			} else if ( isVideo ) {
				title = __( 'Video', 'wporg-patterns' );
			} else {
				title = __( 'Media', 'wporg-patterns' );
			}
		}

		let instructions = __(
			"Patterns are required to use our collection of license-free media. You won't be able to upload or link to any other media in your patterns.",
			'wporg-patterns'
		);
		if ( ! allowsImages ) {
			instructions = __( 'The pattern directory does not support this media type yet.', 'wporg-patterns' );
		}

		const placeholderClassName = classnames( 'block-editor-media-placeholder', className, {
			'is-appender': isAppender,
		} );

		return (
			<Placeholder
				icon={ icon }
				label={ title }
				instructions={ instructions }
				className={ placeholderClassName }
				notices={ notices }
				preview={ mediaPreview }
				style={ style }
			>
				{ allowsImages && content }
				{ children }
			</Placeholder>
		);
	};
	const renderPlaceholder = placeholder ?? defaultRenderPlaceholder;

	const content = renderPlaceholder(
		<MediaUpload
			addToGallery={ addToGallery }
			gallery={ multiple && onlyAllowsImages }
			multiple={ multiple }
			onSelect={ onSelect }
			allowedTypes={ allowedTypes }
			value={ Array.isArray( value ) ? value.map( ( { id } ) => id ) : value.id }
			render={ ( { open } ) => (
				<Button
					variant="primary"
					onClick={ () => {
						open();
					} }
				>
					{ __( 'Media Library', 'wporg-patterns' ) }
				</Button>
			) }
		/>
	);
	return <MediaUploadCheck fallback={ content }>{ content }</MediaUploadCheck>;
}
