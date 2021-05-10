/**
 * WordPress dependencies
 */
import { ExternalLink, Guide } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ImageCollectionImage, PatternDesignImage, PatternEditorImage, PatternsImage } from './images';

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
			contentLabel={ __( 'Welcome to the pattern creator', 'wporg-patterns' ) }
			finishButtonText={ __( 'Get started', 'wporg-patterns' ) }
			onFinish={ () => toggleFeature( GUIDE_ID ) }
			pages={ [
				{
					image: (
						<div className="pattern-creator-welcome-guide__image circle-background">
							<PatternsImage />
						</div>
					),
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Welcome to the pattern editor', 'wporg-patterns' ) }
							</h1>
							<p>
								{ __(
									'Mix and match WordPress blocks together to create unique and compelling designs.',
									'wporg-patterns'
								) }
							</p>
						</>
					),
				},
				{
					image: (
						<div className="pattern-creator-welcome-guide__image diamond-background">
							<ImageCollectionImage />
						</div>
					),
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Use our collection of license-free images', 'wporg-patterns' ) }
							</h1>
							<p>
								{ __(
									'Don’t worry about licensing. We’ve provided a collection of worry-free images and media for you to use.',
									'wporg-patterns'
								) }
							</p>
						</>
					),
				},
				{
					image: (
						<div className="pattern-creator-welcome-guide__image pattern-creator-welcome-guide__image--is-bottom-aligned triangles-background">
							<PatternEditorImage />
						</div>
					),
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Find your patterns in the editor', 'wporg-patterns' ) }
							</h1>
							<p>
								{ __(
									'Patterns in the directory are available from any WordPress editor. Patterns you’ve created and favorited will be highlight for easy access.',
									'wporg-patterns'
								) }
							</p>
						</>
					),
				},
				{
					image: (
						<div className="pattern-creator-welcome-guide__image pattern-creator-welcome-guide__image--is-bottom-aligned square-background">
							<PatternDesignImage />
						</div>
					),
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Learn more about designing patterns', 'wporg-patterns' ) }
							</h1>
							<p>
								{ __( 'New to patterns? Want to learn more about building them?', 'wporg-patterns' ) }
								<ExternalLink
									className="pattern-creator-welcome-guide__link"
									href={ __( 'https://wordpress.org/support/article/wordpress-editor/', 'wporg-patterns' ) }
								>
									{ __( "Here's a detailed guide.", 'wporg-patterns' ) }
								</ExternalLink>
							</p>
						</>
					),
				},
			] }
		/>
	);
}
