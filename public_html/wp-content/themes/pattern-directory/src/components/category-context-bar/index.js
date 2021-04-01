/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

function CategoryContextBar( { children, actionsTitle, actions, isVisible } ) {
	const [ height, setHeight ] = useState( 0 );
	const innerRef = useRef( null );

	useEffect( () => {
		if ( isVisible ) {
			setHeight( innerRef.current.offsetHeight );
		} else {
			setHeight( 0 );
		}
	}, [ children ] );

	return (
		<div className="category-context__bar" style={ { height: `${ height }px` } }>
			<div ref={ innerRef }>
				<Flex align="center">
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
			</div>
		</div>
	);
}

export default CategoryContextBar;
