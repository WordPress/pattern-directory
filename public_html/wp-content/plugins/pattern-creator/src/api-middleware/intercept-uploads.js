// Add a middleware provider which intercepts all uploads and stores them within the browser
export default function( options, next ) {
	if ( options.method === 'POST' && options.path === '/wp/v2/media' ) {
		const file = options.body.get( 'file' );

		window.fakeUploadedMedia = window.fakeUploadedMedia || [];
		if ( ! window.fakeUploadedMedia.length ) {
			window.fakeUploadedMedia[ 9999000 ] = {};
		}

		const id = window.fakeUploadedMedia.length;
		window.fakeUploadedMedia[ id ] = {
			id: id,
			date: '',
			date_gmt: '',
			modified: '',
			modified_gmt: '',
			guid: {},
			title: { rendered: file.name, raw: file.name },
			description: {},
			caption: {},
			alt_text: '',
			slug: file.name,
			status: 'inherit',
			type: 'attachment',
			link: '',
			author: 0,
			comment_status: 'open',
			ping_status: 'closed',
			media_details: {},
			media_type: file.type.split( '/' )[ 0 ],
			mime_type: file.type,
			source_url: '', // This gets filled below with a data uri
			_links: {},
		};

		return new Promise( function( resolve ) {
			const a = new FileReader(); // eslint-disable-line
			a.onload = function( event ) {
				window.fakeUploadedMedia[ id ].source_url = event.target.result;
				resolve( window.fakeUploadedMedia[ id ] );
			};
			a.readAsDataURL( file );
		} );
	}

	// Drag droped media of ID 9999xxx is stored within the Browser
	const path_id_match = options.path.match( '^/wp/v2/media/(9999\\\\d+)' );
	if ( path_id_match ) {
		return Promise.resolve( window.fakeUploadedMedia[ path_id_match[ 1 ] ] );
	}

	return next( options );
}
