/**
 * WordPress dependencies
 */
import { ComplementaryArea, ComplementaryAreaMoreMenuItem } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../store/constants';

export default function DefaultSidebar( {
	className,
	identifier,
	title,
	icon,
	children,
	closeLabel,
	header,
	headerClassName,
} ) {
	return (
		<>
			<ComplementaryArea
				className={ className }
				scope={ STORE_NAME }
				identifier={ identifier }
				title={ title }
				icon={ icon }
				closeLabel={ closeLabel }
				header={ header }
				headerClassName={ headerClassName }
			>
				{ children }
			</ComplementaryArea>
			<ComplementaryAreaMoreMenuItem scope={ STORE_NAME } identifier={ identifier } icon={ icon }>
				{ title }
			</ComplementaryAreaMoreMenuItem>
		</>
	);
}
