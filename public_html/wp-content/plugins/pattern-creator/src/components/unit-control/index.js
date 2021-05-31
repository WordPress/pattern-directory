/**
 * WordPress dependencies
 */
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalUnitControl as Control } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Modules constants
 */
const UNITS = [
	{ value: 'px', label: 'px' },
	{ value: '%', label: '%' },
	{ value: 'em', label: 'em' },
	{ value: 'rem', label: 'rem' },
	{ value: 'vw', label: 'vw' },
];

const UnitControl = ( { label, onChange } ) => {
	const [ value, setValue ] = useState();

	return (
		<Control
			type="number"
			label={ label }
			units={ UNITS }
			onChange={ ( val ) => {
				onChange( val );
				setValue( val );
			} }
			value={ value }
			isPressEnterToChange={ true }
		/>
	);
};

export default UnitControl;
