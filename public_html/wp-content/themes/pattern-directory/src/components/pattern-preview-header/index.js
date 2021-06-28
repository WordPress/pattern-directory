const PatternPreviewHeader = ( { title, categories } ) => {
	return (
		<header className="entry-header">
			<h1 className="entry-title">{ title }</h1>
			<div className="pattern__categories">
				{ categories.map( ( cat ) => (
					<a key={ cat.slug } href={ cat.link }>
						{ cat.name }
					</a>
				) ) }
			</div>
		</header>
	);
};

export default PatternPreviewHeader;
