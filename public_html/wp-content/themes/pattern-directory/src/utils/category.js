
const removeTrailingSlash = ( str ) => {
	return str.replace( /\/$/, '' );
};

export const getCategoryFromPath = ( path ) => {
	const _path = removeTrailingSlash( path );

	return _path.substring( _path.lastIndexOf( '/' ) + 1 );
};
