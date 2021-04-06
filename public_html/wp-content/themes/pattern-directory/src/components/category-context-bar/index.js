/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

function CategoryContextBar( { message, title, links } ) {
	const [ height, setHeight ] = useState( 0 );
	const innerRef = useRef( null );

	useEffect( () => {
		if ( message ) {
			setHeight( innerRef.current.offsetHeight );
		} else {
			setHeight( 0 );
		}
	}, [ message ] );

	return (
		<div className="category-context__bar" style={ { height: `${ height }px` } }>
			<Flex align="center" ref={ innerRef }>
				<FlexItem className="category-context__bar__copy">{ message }</FlexItem>
				{ links && links.length > 0 && (
					<FlexItem className="category-context__bar__links">
						<Flex gap={ 0 }>
							<FlexItem>
								<h3 className="category-context__bar__title">{ title } </h3>
							</FlexItem>

							<FlexItem>
								<ul>
									{ links.map( ( i ) => (
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
	);
}

export default CategoryContextBar;
