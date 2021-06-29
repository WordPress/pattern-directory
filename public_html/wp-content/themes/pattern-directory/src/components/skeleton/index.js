const SkeletonWrapper = ( { styles, children } ) => {
	return (
		<span className="pattern-skeleton__container" style={ styles }>
			{ children }
		</span>
	);
};

const Skeleton = ( styles ) => {
	return <span className="pattern-skeleton" style={ styles }></span>;
};

export { SkeletonWrapper, Skeleton };
