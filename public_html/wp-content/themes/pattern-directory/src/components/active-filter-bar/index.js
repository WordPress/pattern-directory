/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { store } from '@wordpress/viewport';
import { Flex, FlexItem } from '@wordpress/components';

function ActiveFilterBar( { children, actionsTitle, actions } ) {
	const isSmall = select( store ).isViewportMatch( '< medium' );

	return (
		<Flex className="active-filter__bar" align="center">
			<FlexItem className="active-filter__bar__copy">{ children }</FlexItem>
			{ ! isSmall && actions.length > 0 && (
				<FlexItem>
					<Flex gap={ 0 }>
						<FlexItem>
							<h3 className="active-filter__bar__title">{ actionsTitle } </h3>
						</FlexItem>

						<FlexItem>
							<ul>
								{ actions.map( ( i ) => (
									<li key={ i.href }>
										<a href={ i.href }>{ i.label }</a>
									</li>
								) ) }
							</ul>
						</FlexItem>
					</Flex>
				</FlexItem>
			) }
		</Flex>
	);
}

export default ActiveFilterBar;
