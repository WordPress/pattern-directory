/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import getPaginationList from '../../utils/get-pagination-list';
import { useRoute } from '../../hooks';

export default function Pagination( { currentPage, totalPages } ) {
	const { path, update: updatePath } = useRoute();
	if ( ! totalPages ) {
		return null;
	}

	const hasPrevious = currentPage > 1;
	const hasNext = currentPage < totalPages;
	const basePath = path.replace( /page\/\d+\/?$/, '' );
	const pages = getPaginationList( totalPages, currentPage );

	const onClick = ( event, page ) => {
		event.preventDefault();
		if ( page === 1 ) {
			updatePath( `${ basePath }` );
		}
		updatePath( `${ basePath }page/${ page }/` );
	};

	return (
		<nav className="pagination" aria-label={ __( 'Pagination', 'wporg-patterns' ) }>
			<ul className="pagination__list">
				<li className="pagination__item pagination__item--previous-page">
					<a
						className={ classnames( {
							pagination__link: true,
							'pagination__link--is-disabled': ! hasPrevious,
						} ) }
						href={ `${ basePath }page/${ currentPage - 1 }` }
						aria-disabled={ ! hasPrevious ? 'disabled' : undefined }
						onClick={ ( event ) => onClick( event, currentPage - 1 ) }
					>
						<span className="screen-reader-text">{ __( 'Previous page', 'wporg-patterns' ) }</span>
						<span aria-hidden>{ _x( 'Previous', 'previous page link label', 'wporg-patterns' ) }</span>
					</a>
				</li>
				{ pages.map( ( page, index ) => {
					if ( '…' === page ) {
						return (
							<li className="pagination__item" key={ `${ index }-${ page }` }>
								{ page }
							</li>
						);
					}
					return (
						<li className="pagination__item" key={ page }>
							<a
								className="pagination__link"
								href={ `${ basePath }page/${ page }` }
								aria-current={ page === currentPage ? 'page' : undefined }
								onClick={ ( event ) => onClick( event, page ) }
							>
								<span className="screen-reader-text">
									{ sprintf(
										// translators: %s is the page number.
										__( 'Page %s', 'wporg-patterns' ),
										page
									) }
								</span>
								<span aria-hidden>{ page }</span>
							</a>
						</li>
					);
				} ) }
				<li className="pagination__item pagination__item--next-page">
					<a
						className={ classnames( {
							pagination__link: true,
							'pagination__link--is-disabled': ! hasNext,
						} ) }
						href={ `${ basePath }page/${ currentPage + 1 }` }
						aria-disabled={ ! hasNext ? 'disabled' : undefined }
						onClick={ ( event ) => onClick( event, currentPage + 1 ) }
					>
						<span className="screen-reader-text">{ __( 'Next page', 'wporg-patterns' ) }</span>
						<span aria-hidden>{ _x( 'Next', 'next page link label', 'wporg-patterns' ) }</span>
					</a>
				</li>
			</ul>
		</nav>
	);
}
