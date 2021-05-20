/**
 * WordPress dependencies
 */
import { Button, CheckboxControl, Modal, TextControl, TextareaControl } from '@wordpress/components';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const Checkboxes = [
	{
		value: 'text',
		label: 'Text',
	},
	{
		value: 'gallery',
		label: 'Gallery',
	},
	{
		value: 'buttons',
		label: 'Buttons',
	},
	{
		value: 'headers',
		label: 'Headers',
	},
	{
		value: 'columns',
		label: 'Columns',
	},
	{
		value: 'query',
		label: 'Query',
	},
];

const ForwardButton = ( { children, disabled, onClick } ) => (
	<Button className="publish-modal__button" isPrimary disabled={ disabled } onClick={ onClick }>
		{ children }
	</Button>
);

export default function PublishModal( { onFinish } ) {
	const [ currentPage, setCurrentPage ] = useState( 0 );
	const [ title, setTitle ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ categories, setCategories ] = useState( [] );
	const container = useRef();

	const goBack = () => {
		setCurrentPage( currentPage - 1 );
	};

	const goForward = () => {
		setCurrentPage( currentPage + 1 );
		container.current.closest( '[role="dialog"]' ).focus();
	};

	const pageIsComplete = () => {
		const hasTitleAndDescription = title.length > 0 && description.length > 0;

		if ( currentPage === 0 ) {
			return hasTitleAndDescription;
		}

		return hasTitleAndDescription && categories.length > 0;
	};

	const pages = [
		{
			content: (
				<>
					<TextControl
						className="publish-modal__control"
						label={ __( 'Title', 'wporg-patterns' ) }
						value={ title }
						placeholder={ __( 'Name your pattern', 'wporg-patterns' ) }
						onChange={ setTitle }
						required={ true }
					/>
					<TextareaControl
						className="publish-modal__control"
						label={ __( 'Description', 'wporg-patterns' ) }
						value={ description }
						placeholder={ __( 'Describe the output of pattern', 'wporg-patterns' ) }
						help={ __(
							'The description is used to help users of assistive technology better understand the contents of your pattern.',
							'wporg-patterns'
						) }
						onChange={ setDescription }
						required={ true }
					/>
				</>
			),
			footer: (
				<>
					<span />
					<ForwardButton disabled={ ! pageIsComplete() } onClick={ goForward }>
						{ __( 'Next', 'wporg-patterns' ) }
					</ForwardButton>
				</>
			),
		},
		{
			content: (
				<>
					<fieldset>
						<legend>{ __( 'Category', 'wporg-patterns' ) }</legend>
						<p>
							{ __(
								'Categories help people find patterns and determines where your pattern will appear in the WordPress Editor.',
								'wporg-patterns'
							) }
						</p>
						<div className="publish-modal__checkbox-list">
							{ Checkboxes.map( ( i ) => (
								<CheckboxControl
									key={ i.value }
									label={ i.label }
									value={ i.value }
									checked={ categories.includes( i.value ) }
									onChange={ ( checked ) => {
										if ( checked ) {
											setCategories( [ ...categories, i.value ] );
										} else {
											setCategories( categories.filter( ( cat ) => cat !== i.value ) );
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
					<ForwardButton disabled={ ! pageIsComplete() } onClick={ onFinish }>
						{ __( 'Finish', 'wporg-patterns' ) }
					</ForwardButton>
				</>
			),
		},
	];

	return (
		<Modal className="publish-modal" onRequestClose={ onFinish }>
			<div className="publish-modal__page" ref={ container }>
				<div className="publish-modal__sidebar">
					<h3 className="publish-modal__sidebar-title">
						{ __( 'Publish your pattern', 'wporg-patterns' ) }
					</h3>
				</div>
				<div className="publish-modal__content">
					<div>{ pages[ currentPage ].content }</div>
					<div className="publish-modal__footer">{ pages[ currentPage ].footer }</div>
				</div>
			</div>
		</Modal>
	);
}
