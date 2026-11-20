/**
 * Show an Openverse thumbnail, falling back to the full image if it fails to load.
 *
 * Openverse can return thumbnail URLs that error, which would otherwise show as empty gray boxes.
 *
 * @param {Object} props
 * @param {Object} props.item An Openverse image result.
 * @return {Element} The image element.
 */
export default function OpenverseThumbnail( { item } ) {
	const onError = ( event ) => {
		const image = event.currentTarget;
		// Only fall back once, so a broken full image can't trigger an error loop.
		if ( item.url && image.dataset.fallback !== 'true' ) {
			image.dataset.fallback = 'true';
			image.src = item.url;
		}
	};

	return <img src={ item.thumbnail || item.url } alt={ item.title } onError={ onError } />;
}
