/**
 * WordPress dependencies
 */
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalUnitControl as Control } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';

const UnitControl = ( { label, onChange } ) => {
	const [ value, setValue ] = useState();
	const container = useRef();

	useEffect( () => {
		if ( ! container.current ) {
			return;
		}

		try {
			// Move up to the parent to find the label.
			const outerParent = container.current.parentElement.parentElement;
			outerParent.querySelector( 'label' ).classList.add( 'screen-reader-text' );
		} catch ( error ) {}
	}, [ container ] );

	return (
		<Control
			ref={ container }
			type="number"
			label={ label }
			units={ [ { value: 'px', label: 'px' } ] }
			onChange={ ( val ) => {
				const parsed = parseInt( val );

				// We want to remove the unit
				if ( parsed >= 0 ) {
					onChange( parsed );
					setValue( parsed );
				}
			} }
			value={ value }
			isUnitSelectTabbable={ false }
			isPressEnterToChange={ true }
		/>
	);
};

export default UnitControl;
