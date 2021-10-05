/**
 * External dependencies
 */
import { find, get, unescape as unescapeString } from 'lodash';

/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { CheckboxControl, TextControl } from '@wordpress/components';
import { speak } from '@wordpress/a11y';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { useDebounce } from '@wordpress/compose';
import { useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CATEGORY_SLUG } from '../../store';

/**
 * Module Constants
 */
const DEFAULT_QUERY = {
	per_page: -1,
	orderby: 'name',
	order: 'asc',
	_fields: 'id,name,parent',
	context: 'view',
};

const MIN_TERMS_COUNT_FOR_FILTER = 8;

const EMPTY_ARRAY = [];

/**
 * Sort Terms by Selected.
 *
 * @param {Object[]} termsTree Array of terms in tree format.
 * @param {number[]} terms     Selected terms.
 *
 * @return {Object[]} Sorted array of terms.
 */
export function sortBySelected( termsTree, terms ) {
	const termIsSelected = ( termA, termB ) => {
		const termASelected = terms.indexOf( termA.id ) !== -1;
		const termBSelected = terms.indexOf( termB.id ) !== -1;

		if ( termASelected === termBSelected ) {
			return 0;
		}

		if ( termASelected && ! termBSelected ) {
			return -1;
		}

		if ( ! termASelected && termBSelected ) {
			return 1;
		}

		return 0;
	};
	const newTermTree = [ ...termsTree ];
	newTermTree.sort( termIsSelected );
	return newTermTree;
}

/**
 * Find term by parent id or name.
 *
 * @param {Object[]}      terms  Array of Terms.
 * @param {number|string} parent id.
 * @param {string}        name   Term name.
 * @return {Object} Term object.
 */
export function findTerm( terms, parent, name ) {
	return find( terms, ( term ) => {
		return (
			( ( ! term.parent && ! parent ) || parseInt( term.parent ) === parseInt( parent ) ) &&
			term.name.toLowerCase() === name.toLowerCase()
		);
	} );
}

/**
 * Get filter matcher function.
 *
 * @param {string} filterValue Filter value.
 * @return {(function(Object): (Object|boolean))} Matcher function.
 */
export function getFilterMatcher( filterValue ) {
	const matchTermsForFilter = ( term ) => {
		if ( '' === filterValue ) {
			return term;
		}

		// If the term's name contains the filterValue, then return it.
		if ( -1 !== term.name.toLowerCase().indexOf( filterValue.toLowerCase() ) ) {
			return term;
		}

		// Otherwise, return false. After mapping, the list of terms will need
		// to have false values filtered out.
		return false;
	};
	return matchTermsForFilter;
}

function PatternCategoriesControl( { selectedTerms = EMPTY_ARRAY, setTerms } ) {
	const [ filterValue, setFilterValue ] = useState( '' );
	const [ filteredTermsTree, setFilteredTermsTree ] = useState( [] );
	const debouncedSpeak = useDebounce( speak, 500 );

	const { hasAssignAction, availableTerms } = useSelect( ( select ) => {
		const { getCurrentPost } = select( editorStore );
		const { getTaxonomy, getEntityRecords } = select( coreStore );
		const _taxonomy = getTaxonomy( CATEGORY_SLUG );

		return {
			hasAssignAction: _taxonomy
				? get( getCurrentPost(), [ '_links', 'wp:action-assign-' + _taxonomy.rest_base ], false )
				: false,
			availableTerms: getEntityRecords( 'taxonomy', CATEGORY_SLUG, DEFAULT_QUERY ) || EMPTY_ARRAY,
		};
	}, [] );

	const availableTermsTree = useMemo(
		() => sortBySelected( availableTerms, selectedTerms ),
		// Remove `terms` from the dependency list to avoid reordering every time
		// checking or unchecking a term.
		[ availableTerms ]
	);

	if ( ! hasAssignAction ) {
		return null;
	}

	/**
	 * Handler for checking term.
	 *
	 * @param {number} termId
	 */
	const onChange = ( termId ) => {
		const hasTerm = selectedTerms.includes( termId );
		const newTerms = hasTerm
			? selectedTerms.filter( ( term ) => term !== termId )
			: [ ...selectedTerms, termId ];
		setTerms( newTerms );
	};

	const setFilter = ( value ) => {
		const newFilteredTermsTree = availableTermsTree
			.map( getFilterMatcher( value ) )
			.filter( ( term ) => term );
		const getResultCount = ( termsTree ) => {
			let count = 0;
			for ( let i = 0; i < termsTree.length; i++ ) {
				count++;
			}
			return count;
		};

		setFilterValue( value );
		setFilteredTermsTree( newFilteredTermsTree );

		const resultCount = getResultCount( newFilteredTermsTree );
		const resultsFoundMessage = sprintf(
			/* translators: %d: number of results */
			_n( '%d result found.', '%d results found.', resultCount, 'wporg-patterns' ),
			resultCount
		);

		debouncedSpeak( resultsFoundMessage, 'assertive' );
	};

	const showFilter = availableTerms.length >= MIN_TERMS_COUNT_FOR_FILTER;

	return (
		<>
			{ showFilter && (
				<TextControl
					className="editor-post-taxonomies__hierarchical-terms-filter"
					label={ __( 'Search Categories', 'wporg-patterns' ) }
					value={ filterValue }
					onChange={ setFilter }
				/>
			) }
			<div
				className="editor-post-taxonomies__hierarchical-terms-list"
				tabIndex="0"
				role="group"
				aria-label={ __( 'Categories', 'wporg-patterns' ) }
			>
				{ ( '' !== filterValue ? filteredTermsTree : availableTermsTree ).map( ( term ) => {
					return (
						<div key={ term.id } className="editor-post-taxonomies__hierarchical-terms-choice">
							<CheckboxControl
								checked={ selectedTerms.indexOf( term.id ) !== -1 }
								onChange={ () => {
									const termId = Number( term.id );
									onChange( termId );
								} }
								label={ unescapeString( term.name ) }
							/>
						</div>
					);
				} ) }
			</div>
		</>
	);
}

export default PatternCategoriesControl;
