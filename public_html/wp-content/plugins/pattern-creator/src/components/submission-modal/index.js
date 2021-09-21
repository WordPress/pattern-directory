/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Button, CheckboxControl, Modal, TextControl, TextareaControl } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';

const ForwardButton = ( { children, disabled, onClick } ) => (
	<Button className="pattern-modal-publish__button" isPrimary disabled={ disabled } onClick={ onClick }>
		{ children }
	</Button>
);

export default function SubmissionModal( { isPublished, onSubmit, onClose } ) {
	// Get current post defaults
	const { meta, postCategories, postTitle } = useSelect( ( select ) => {
		return {
			meta: select( editorStore ).getEditedPostAttribute( 'meta' ),
			postTitle: select( editorStore ).getEditedPostAttribute( 'title' ),
			postCategories: select( editorStore ).getEditedPostAttribute( 'pattern-categories' ),
		};
	} );
	const { editPost } = useDispatch( editorStore );
	const [ title, setTitle ] = useState( postTitle );
	const [ description, setDescription ] = useState( meta.wpop_description );
	const [ selectedCategories, setSelectedCategories ] = useState( postCategories );
	const [ categories, setCategories ] = useState( [] );
	const [ currentPage, setCurrentPage ] = useState( 0 );
	const container = useRef();

	useEffect( () => {
		const getCategories = () => {
			apiFetch( {
				path: addQueryArgs( '/wp/v2/pattern-categories' ),
			} ).then( ( res ) => {
				setCategories(
					res.map( ( i ) => {
						return {
							value: i.id,
							label: i.name,
						};
					} )
				);
			} );
		};

		getCategories();
	}, [] );

	useEffect( () => {
		editPost( {
			meta: {
				...meta,
				wpop_description: description,
			},
			title: title,
			'pattern-categories': selectedCategories,
		} );
	}, [ title, description, selectedCategories ] );

	const goBack = () => {
		setCurrentPage( currentPage - 1 );
	};

	const goForward = () => {
		setCurrentPage( currentPage + 1 );
		container.current.closest( '[role="dialog"]' ).focus();
	};

	const pages = [
		{
			content: (
				<>
					<TextControl
						className="submission-modal__control"
						label={ __( 'Title (Required)', 'wporg-patterns' ) }
						value={ title }
						placeholder={ __( 'Name your pattern', 'wporg-patterns' ) }
						onChange={ setTitle }
						required={ true }
					/>
					<TextareaControl
						className="submission-modal__control"
						label={ __( 'Description', 'wporg-patterns' ) }
						value={ description }
						placeholder={ __( 'Describe the output of pattern', 'wporg-patterns' ) }
						help={ __(
							'The description is used to help users of assistive technology understand the content of your pattern.',
							'wporg-patterns'
						) }
						onChange={ setDescription }
					/>
				</>
			),
			footer: (
				<>
					<span />
					<ForwardButton disabled={ ! title.length } onClick={ goForward }>
						{ __( 'Next', 'wporg-patterns' ) }
					</ForwardButton>
				</>
			),
		},
		{
			content: (
				<>
					<h3 className="submission-modal__subtitle">
						{ __( 'Category (Required)', 'wporg-patterns' ) }
					</h3>
					<p>
						{ __(
							'Categories help people find patterns and determines where your pattern will appear in the WordPress Editor.',
							'wporg-patterns'
						) }
					</p>
					<ul
						role="listbox"
						className="submission-modal__checkbox-list"
						aria-label={ __( 'Pattern categories', 'wporg-patterns' ) }
					>
						{ categories.map( ( i ) => (
							<li key={ i.value }>
								<CheckboxControl
									label={ i.label }
									value={ i.value }
									checked={ selectedCategories.includes( i.value ) }
									onChange={ ( checked ) => {
										if ( checked ) {
											setSelectedCategories( [ ...selectedCategories, i.value ] );
										} else {
											setSelectedCategories(
												selectedCategories.filter( ( cat ) => cat !== i.value )
											);
										}
									} }
								/>
							</li>
						) ) }
					</ul>
				</>
			),
			footer: (
				<>
					<Button onClick={ goBack }>{ __( 'Previous', 'wporg-patterns' ) }</Button>
					<ForwardButton disabled={ ! selectedCategories.length } onClick={ onSubmit }>
						{ __( 'Finish', 'wporg-patterns' ) }
					</ForwardButton>
				</>
			),
		},
	];

	const modalClass = classnames( {
		'submission-modal': true,
		'is-published': isPublished,
	} );

	return (
		<Modal className={ modalClass } onRequestClose={ onClose }>
			{ isPublished ? (
				<div className="submission-modal__page">
					<h3 className="submission-modal__title">
						{ __( 'Thank you for sharing your pattern!', 'wporg-patterns' ) }
					</h3>
					<p className="submission-modal__copy">
						{ __(
							"Your pattern is pending review. We'll email you when its been published in the public directory.",
							'wporg-patterns'
						) }
					</p>
					<div className="submission-modal__content">
						<Button isPrimary onClick={ onClose }>
							{ __( 'Close', 'wporg-patterns' ) }
						</Button>
						<Button
							className="submission-modal__link"
							isLink
							href={ `${ wporgBlockPattern.siteUrl }/new-pattern` }
						>
							{ __( 'Create another pattern', 'wporg-patterns' ) }
						</Button>
						<Button
							className="submission-modal__link"
							isLink
							href={ `${ wporgBlockPattern.siteUrl }/my-patterns` }
						>
							{ __( 'View my patterns', 'wporg-patterns' ) }
						</Button>
					</div>
				</div>
			) : (
				<div className="submission-modal__page" ref={ container }>
					<div className="submission-modal__sidebar">
						<h3 className="submission-modal__title submission-modal__title-sidebar">
							{ __( 'Publish your pattern', 'wporg-patterns' ) }
						</h3>
					</div>
					<div className="submission-modal__content">
						<div>{ pages[ currentPage ].content }</div>
						<div className="submission-modal__footer">{ pages[ currentPage ].footer }</div>
					</div>
				</div>
			) }
		</Modal>
	);
}
