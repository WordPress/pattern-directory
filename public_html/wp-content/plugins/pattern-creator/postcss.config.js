module.exports = {
	ident: 'postcss',
	plugins: [
		require( 'autoprefixer' )( { grid: true } ),
		require( 'postcss-custom-properties' )( {
			importFrom: './src/style.css',
		} ),
	],
};
