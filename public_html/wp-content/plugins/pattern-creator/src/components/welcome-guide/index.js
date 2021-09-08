/**
 * WordPress dependencies
 */
import { Guide } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ImageCollectionImage, PatternEditorImage, PatternsImage } from './images';
import { store as patternStore } from '../../store';

/**
 * Module constants
 */
export const GUIDE_ID = 'patternWelcomeGuide';

export default function WelcomeGuide() {
	const isActive = useSelect( ( select ) => select( patternStore ).isFeatureActive( GUIDE_ID ), [] );

	const { toggleFeature } = useDispatch( patternStore );

	if ( ! isActive ) {
		return null;
	}

	return (
		<Guide
			className="pattern-creator-welcome-guide"
			contentLabel={ __( 'Welcome to the pattern creator', 'wporg-patterns' ) }
			finishButtonText={ __( 'Done', 'wporg-patterns' ) }
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
						<div className="pattern-creator-welcome-guide__image triangles-background">
							<PatternEditorImage />
						</div>
					),
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Submit your pattern to the directory', 'wporg-patterns' ) }
							</h1>
							<p>
								{ __(
									'Choose a category and share your pattern with the world. All patterns in the directory are available from any WordPress site.',
									'wporg-patterns'
								) }
							</p>
						</>
					),
				},
			] }
		/>
	);
}
