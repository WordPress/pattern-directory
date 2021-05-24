/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Button, CheckboxControl, Modal, TextControl, TextareaControl } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import usePostMeta from '../../store/hooks/use-post-meta';
import './style.css';

const ForwardButton = ( { children, disabled, onClick } ) => (
	<Button className="pattern-modal-publish__button" isPrimary disabled={ disabled } onClick={ onClick }>
		{ children }
	</Button>
);

export default function SubmitModal( { onSuccess, onClose } ) {
	// Get Document default
	const { postTitle } = useSelect( ( select ) => {
		return {
			postTitle: select( editorStore ).getEditedPostAttribute( 'title' ),
		};
	} );

	const [ postDescription ] = usePostMeta( 'wpop_description', '' );

	const [ currentPage, setCurrentPage ] = useState( 0 );
	const [ title, setTitle ] = useState( postTitle );
	const [ description, setDescription ] = useState( postDescription );
	const [ selectedCategories, setSelectedCategories ] = useState( [] );
	const [ categories, setCategories ] = useState( [] );
	const container = useRef();

	const goBack = () => {
		setCurrentPage( currentPage - 1 );
	};

	const goForward = () => {
		setCurrentPage( currentPage + 1 );
		container.current.closest( '[role="dialog"]' ).focus();
	};

	const handleSave = () => {
        // TO DO: Implement save functionality.
        onSuccess( true );
        console.log( onSuccess );
	};

	useEffect( () => {
		const getCategories = () => {
			apiFetch( {
				path: addQueryArgs( '/wp/v2/pattern-categories' ),
			} ).then( ( res ) => {
				setCategories(
					res.map( ( i ) => {
						return {
							value: i.slug,
							label: i.name,
						};
					} )
				);
			} );
		};

		getCategories();
	}, [] );

	const pages = [
		{
			content: (
				<>
					<TextControl
						className="pattern-modal__control"
						label={ __( 'Title (Required)', 'wporg-patterns' ) }
						value={ title }
						placeholder={ __( 'Name your pattern', 'wporg-patterns' ) }
						onChange={ setTitle }
						required={ true }
					/>
					<TextareaControl
						className="pattern-modal__control"
						label={ __( 'Description', 'wporg-patterns' ) }
						value={ description }
						placeholder={ __( 'Describe the output of pattern', 'wporg-patterns' ) }
						help={ __(
							'The description is used to help users of assistive technology better understand the contents of your pattern.',
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
					<fieldset>
						<legend>{ __( 'Category (Required)', 'wporg-patterns' ) }</legend>
						<p>
							{ __(
								'Categories help people find patterns and determines where your pattern will appear in the WordPress Editor.',
								'wporg-patterns'
							) }
						</p>
						<div className="pattern-modal__checkbox-list">
							{ categories.map( ( i ) => (
								<CheckboxControl
									key={ i.value }
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
							) ) }
						</div>
					</fieldset>
				</>
			),
			footer: (
				<>
					<Button onClick={ goBack }>{ __( 'Previous', 'wporg-patterns' ) }</Button>
					<ForwardButton disabled={ ! selectedCategories.length } onClick={ handleSave }>
						{ __( 'Finish', 'wporg-patterns' ) }
					</ForwardButton>
				</>
			),
		},
	];

	return (
		<Modal className="pattern-modal pattern-modal-publish" onRequestClose={ onClose }>
			<div className="pattern-modal-publish__page" ref={ container }>
				<div className="pattern-modal-publish__sidebar">
					<h3 className="pattern-modal__title pattern-modal__title-sidebar">
						{ __( 'Publish your pattern', 'wporg-patterns' ) }
					</h3>
				</div>
				<div className="pattern-modal-publish__content">
					<div>{ pages[ currentPage ].content }</div>
					<div className="pattern-modal-publish__footer">{ pages[ currentPage ].footer }</div>
				</div>
			</div>
		</Modal>
	);
}
