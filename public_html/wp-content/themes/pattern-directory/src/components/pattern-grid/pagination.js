/* eslint-disable jsx-a11y/anchor-is-valid */
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

export default function Pagination( { currentPage = 1, totalPages } ) {
	if ( ! totalPages ) {
		return null;
	}

	const hasPrevious = currentPage > 1;
	const hasNext = currentPage < totalPages;
	const pages = Array.from( { length: totalPages }, ( val, i ) => i + 1 );

	return (
		<nav aria-label={ __( 'Pagination', 'wporg-patterns' ) }>
			<ul className="pagination">
				<li className="pagination__item pagination__item--previous-page">
					<a
						className={ classnames( {
							pagination__link: true,
							'pagination__link--is-disabled': ! hasPrevious,
						} ) }
						href="#"
						aria-disabled={ ! hasPrevious ? 'disabled' : undefined }
					>
						<span className="screen-reader-text">{ __( 'Previous page', 'wporg-patterns' ) }</span>
					</a>
				</li>
				{ pages.map( ( page ) => (
					<li className="pagination__item" key={ page }>
						<a
							className="pagination__link"
							href="#"
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
						</a>
					</li>
				) ) }
				<li className="pagination__item pagination__item--next-page">
					<a
						className={ classnames( {
							pagination__link: true,
							'pagination__link--is-disabled': ! hasNext,
						} ) }
						href="#"
						aria-disabled={ ! hasNext ? 'disabled' : undefined }
					>
						<span className="screen-reader-text">{ __( 'Next page', 'wporg-patterns' ) }</span>
					</a>
				</li>
			</ul>
		</nav>
	);
}
