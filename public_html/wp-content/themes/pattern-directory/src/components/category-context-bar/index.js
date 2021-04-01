/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';

function CategoryContextBar( { children, actionsTitle, actions } ) {
	return (
		<Flex className="category-context__bar" align="center">
			<FlexItem className="category-context__bar__copy">{ children }</FlexItem>
			{ actions && actions.length > 0 && (
				<FlexItem className="category-context__bar__actions">
					<Flex gap={ 0 }>
						<FlexItem>
							<h3 className="category-context__bar__title">{ actionsTitle } </h3>
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

export default CategoryContextBar;
