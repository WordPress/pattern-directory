/**
 * WordPress dependencies
 */
import { Path, SVG } from '@wordpress/primitives';

export default function ( props ) {
	return (
		<SVG xmlns="http://www.w3.org/2000/svg" width="24" height="24" { ...props }>
			<Path d="M12 4.915c1.09-1.28 2.76-2.09 4.5-2.09 3.08 0 5.5 2.42 5.5 5.5 0 3.777-3.394 6.855-8.537 11.518l-.013.012-1.45 1.32-1.45-1.31-.04-.036C5.384 15.17 2 12.095 2 8.325c0-3.08 2.42-5.5 5.5-5.5 1.74 0 3.41.81 4.5 2.09zm0 13.56l.1-.1c4.76-4.31 7.9-7.16 7.9-10.05 0-2-1.5-3.5-3.5-3.5-1.54 0-3.04.99-3.56 2.36h-1.87c-.53-1.37-2.03-2.36-3.57-2.36-2 0-3.5 1.5-3.5 3.5 0 2.89 3.14 5.74 7.9 10.05l.1.1z" />
		</SVG>
	);
}
