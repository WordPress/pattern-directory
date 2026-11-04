/**
 * WordPress dependencies
 */
import { ExternalLink, Guide } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GuidelinesImage, ImageCollectionImage, PatternEditorImage, PatternsImage } from './images';
import { store as patternStore } from '../../store';

/**
 * Module constants
 */
export const GUIDE_ID = 'patternWelcomeGuide';

const GUIDELINES_URL = 'https://make.wordpress.org/handbook/pathways/design/submit-block-patterns/';

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
						<div className="pattern-creator-welcome-guide__image grid-background">
							<GuidelinesImage />
						</div>
					),
					content: (
						<>
							<h1 className="pattern-creator-welcome-guide__title">
								{ __( 'Follow the submission guidelines', 'wporg-patterns' ) }
							</h1>
							<p>
								{ __(
									'Every pattern is reviewed by hand. To be approved, yours should:',
									'wporg-patterns'
								) }
							</p>
							<ul className="pattern-creator-welcome-guide__list">
								<li>
									{ __(
										'Be unique, not a near-copy of an existing pattern.',
										'wporg-patterns'
									) }
								</li>
								<li>
									{ __(
										'Be more than a single block, but less than a full page.',
										'wporg-patterns'
									) }
								</li>
								<li>
									{ __(
										'Have a descriptive title and work with any theme’s colors.',
										'wporg-patterns'
									) }
								</li>
								<li>{ __( 'Use only content you have the rights to.', 'wporg-patterns' ) }</li>
							</ul>
							<ExternalLink className="pattern-creator-welcome-guide__link" href={ GUIDELINES_URL }>
								{ __( 'Read the full guidelines', 'wporg-patterns' ) }
							</ExternalLink>
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
