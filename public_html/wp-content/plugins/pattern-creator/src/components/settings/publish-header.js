/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { MODULE_KEY } from '../../store/utils';

export default function PublishHeader( { hasEdits } ) {
	const isUnpublished = useSelect( ( select ) => {
		const { getEditingBlockPatternId, getBlockPattern } = select( MODULE_KEY );
		const post = getBlockPattern( getEditingBlockPatternId() );
		return [ 'publish', 'private' ].indexOf( post.status ) === -1;
	} );

	if ( isUnpublished ) {
		return (
			<>
				<h2>{ __( 'Are you ready to publish?', 'wporg-patterns' ) }</h2>
				<p>
					{ __(
						'Name your pattern and write a short description before submitting.',
						'wporg-patterns'
					) }
				</p>
			</>
		);
	} else if ( hasEdits ) {
		return (
			<>
				<h2>{ __( 'Update your published pattern.', 'wporg-patterns' ) }</h2>
				<p>{ __( 'Change your pattern details [copy tbd].', 'wporg-patterns' ) }</p>
			</>
		);
	}
	return (
		<>
			<h2>{ __( 'Pattern Updated!', 'wporg-patterns' ) }</h2>
			<p>{ __( 'Find your pattern on wp.org…', 'wporg-patterns' ) }</p>
		</>
	);
}
