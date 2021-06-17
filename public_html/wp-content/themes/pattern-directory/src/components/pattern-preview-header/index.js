const PatternPreviewHeader = ( { title, categories } ) => {
	return (
		<header className="entry-header">
			<h1 className="entry-title">{ title }</h1>
			<div className="pattern__categories">
				{ categories.map( ( cat ) => (
					<a key={ cat.slug } href={ `${ wporgSiteUrl }/pattern-categories/${ cat.slug }` }>
						{ cat.name }
					</a>
				) ) }
			</div>
		</header>
	);
};

export default PatternPreviewHeader;
