/**
 * External dependencies
 */
import { BlockList, ObserveTyping, WritingFlow } from '@wordpress/block-editor';
import { Popover, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.css';

export default function Editor() {
	const isRequesting = useSelect( ( select ) => {
		const { isResolving } = select( 'core/data' );
		const { getEditingBlockPatternId } = select( 'wporg/block-pattern-creator' );
		const patternId = getEditingBlockPatternId();
		return isResolving( 'core', 'getEntityRecord', [ 'postType', 'wp-pattern', patternId ] );
	} );

	return (
		<div className="block-pattern-creator__editor editor-styles-wrapper">
			{ isRequesting ? (
				<div className="block-pattern-creator__editor-loading">
					<Spinner />
				</div>
			) : (
				<>
					<Popover.Slot name="block-toolbar" />
					<WritingFlow>
						<ObserveTyping>
							<BlockList />
						</ObserveTyping>
					</WritingFlow>
					<Popover.Slot />
				</>
			) }
		</div>
	);
}
