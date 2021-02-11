/**
 * External dependencies
 */
import { render } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import '@wordpress/format-library';

/**
 * Internal dependencies
 */
import Editor from './components/editor';
import { POST_TYPE } from './store/utils';
import './store';
import './style.css';

registerCoreBlocks();

const reboot = () => {
	console.error( 'todo— Reboot editor on error' ); // eslint-disable-line no-console
};

render(
	<Editor
		settings={ wporgBlockPattern.settings }
		onError={ reboot }
		postId={ wporgBlockPattern.postId }
		postType={ POST_TYPE }
		initialEdits={ {} }
	/>,
	document.getElementById( 'block-pattern-creator' )
);
