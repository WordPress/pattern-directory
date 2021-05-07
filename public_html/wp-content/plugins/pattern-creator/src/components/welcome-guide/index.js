/**
 * WordPress dependencies
 */
import { ExternalLink, Guide } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CanvasImage, EditorImage, BlockLibraryImage, DocumentationImage, InserterIconImage } from './images';

/**
 * Module constants
 */
const GUIDE_ID = 'welcomeGuide';

export default function WelcomeGuide() {
	const isActive = useSelect( ( select ) => select( editPostStore ).isFeatureActive( GUIDE_ID ), [] );

	const { toggleFeature } = useDispatch( editPostStore );

	if ( ! isActive ) {
		return null;
	}

	return (
		<Guide
			className="pattern-creator-welcome-guide"
			contentLabel={ __( 'Welcome to the pattern creator' ) }
			finishButtonText={ __( 'Get started' ) }
			onFinish={ () => toggleFeature( GUIDE_ID ) }
			pages={ [
				{
					image: <CanvasImage />,
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Welcome to the pattern editor' ) }
							</h1>
							<p>
								{ __(
									'Mix and match WordPress blocks together to create unique and compelling designs.'
								) }
							</p>
						</>
					),
				},
				{
					image: <EditorImage />,
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Use our collection of license-free images' ) }
							</h1>
							<p>
								{ __(
									'Don’t worry about licensing. We’ve provided a collection of worry-free images and media for you to use.'
								) }
							</p>
						</>
					),
				},
				{
					image: <EditorImage />,
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Find your patterns in the editor' ) }
							</h1>
							<p>
								{ __(
									'Patterns in the directory are availabe from any WordPress editor. Patterns you’ve created and favorited will be highlight for easy access.'
								) }
							</p>
						</>
					),
				},
				{
					image: <EditorImage />,
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Learn more about designing patterns' ) }
							</h1>
							<p>
								{ __( 'New to patterns? Want to learn more about building them?' ) }
								<ExternalLink
									className="pattern-creator-welcome-guide__link"
									href={ __( 'https://wordpress.org/support/article/wordpress-editor/' ) }
								>
									{ __( "Here's a detailed guide." ) }
								</ExternalLink>
							</p>
						</>
					),
				},
			] }
		/>
	);
}
