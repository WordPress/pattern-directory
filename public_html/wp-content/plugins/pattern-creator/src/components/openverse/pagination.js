/**
 * WordPress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getPaginationList } from './utils';

export default function OpenversePagination( { currentPage = 1, onNavigation, totalPages } ) {
	if ( ! totalPages || totalPages <= 1 ) {
		return null;
	}

	const hasPrevious = currentPage > 1;
	const hasNext = currentPage < totalPages;
	const pages = getPaginationList( totalPages, currentPage );

	return (
		<nav className="pattern-openverse__pagination" aria-label={ __( 'Pagination', 'wporg-patterns' ) }>
			<ul className="pattern-openverse__pagination-list">
				<li className="pattern-openverse__pagination-item pattern-openverse__pagination-item-previous-page">
					{ hasPrevious && (
						<Button
							variant="secondary"
							className="pattern-openverse__pagination-link"
							onClick={ () => onNavigation( currentPage - 1 ) }
						>
							<span className="screen-reader-text">{ __( 'Previous page', 'wporg-patterns' ) }</span>
							<span aria-hidden>
								{ _x( 'Previous', 'previous page link label', 'wporg-patterns' ) }
							</span>
						</Button>
					) }
				</li>
				{ pages.map( ( page, index ) => {
					if ( '…' === page ) {
						return (
							<li className="pattern-openverse__pagination-item" key={ `${ index }-${ page }` }>
								{ page }
							</li>
						);
					}
					return (
						<li className="pattern-openverse__pagination-item" key={ page }>
							<Button
								variant="secondary"
								className="pattern-openverse__pagination-link"
								onClick={ () => onNavigation( page ) }
								aria-current={ page === currentPage ? 'page' : undefined }
							>
								<span className="screen-reader-text">
									{ sprintf(
										// translators: %s is the page number.
										__( 'Page %s', 'wporg-patterns' ),
										page
									) }
								</span>
								<span aria-hidden>{ page }</span>
							</Button>
						</li>
					);
				} ) }
				<li className="pattern-openverse__pagination-item pattern-openverse__pagination-item-next-page">
					{ hasNext && (
						<Button
							variant="secondary"
							className="pattern-openverse__pagination-link"
							onClick={ () => onNavigation( currentPage + 1 ) }
						>
							<span className="screen-reader-text">{ __( 'Next page', 'wporg-patterns' ) }</span>
							<span aria-hidden>{ _x( 'Next', 'next page link label', 'wporg-patterns' ) }</span>
						</Button>
					) }
				</li>
			</ul>
		</nav>
	);
}
