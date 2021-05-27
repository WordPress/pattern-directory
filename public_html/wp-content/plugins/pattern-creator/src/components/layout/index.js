/**
 * External dependencies
 */
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK.
import { BlockBreadcrumb, __experimentalLibrary as Library } from '@wordpress/block-editor';
import { Button, ScrollLock } from '@wordpress/components';
import classnames from 'classnames';
import { close } from '@wordpress/icons';
import { InterfaceSkeleton, store as interfaceStore } from '@wordpress/interface';
import { EditorNotices } from '@wordpress/editor';
import { store as editPostStore } from '@wordpress/edit-post';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK.
import { __experimentalUseDialog as useDialog, useViewportMatch } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import Header from '../header';
import Settings from '../settings';
import Sidebar from './sidebar';
import VisualEditor from '../visual-editor';
import './style.css';

export default function Layout() {
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const isHugeViewport = useViewportMatch( 'huge', '>=' );
	const {
		openGeneralSidebar,
		closeGeneralSidebar,
		setIsInserterOpened,
		closePublishSidebar,
		openPublishSidebar,
	} = useDispatch( editPostStore );
	const [ inserterDialogRef, inserterDialogProps ] = useDialog( {
		onClose: () => setIsInserterOpened( false ),
	} );
	const [ publishSidebarDialogRef, publishSidebarDialogProps ] = useDialog( {
		onClose: () => closePublishSidebar(),
	} );
	const { defaultEditorStyles, isGeneralSidebarOpened, isPublishSidebarOpened, isInserterOpened } = useSelect(
		( select ) => {
			return {
				defaultEditorStyles: select( 'core/editor' ).getEditorSettings().defaultEditorStyles,
				isGeneralSidebarOpened: !! select( interfaceStore ).getActiveComplementaryArea(
					editPostStore.name
				),
				isPublishSidebarOpened: select( editPostStore ).isPublishSidebarOpened(),
				isInserterOpened: select( editPostStore ).isInserterOpened(),
			};
		},
		[]
	);
	const isSidebarOpened = isGeneralSidebarOpened || isPublishSidebarOpened;
	const className = classnames( 'edit-wporg-pattern-layout', 'edit-post-layout', 'is-mode-visual', {
		'is-sidebar-opened': isSidebarOpened,
	} );
	const openSidebarPanel = () => openGeneralSidebar( 'edit-post/block' );

	// Inserter and Sidebars are mutually exclusive, except in "huge" screens.
	useEffect( () => {
		if ( isSidebarOpened && ! isHugeViewport ) {
			setIsInserterOpened( false );
		}
	}, [ isSidebarOpened, isHugeViewport ] );
	useEffect( () => {
		if ( isInserterOpened && ! isHugeViewport ) {
			closeGeneralSidebar();
			closePublishSidebar();
		}
	}, [ isInserterOpened, isHugeViewport ] );
	// General & Publish sidebars are also mutually exclusive, regardless of viewport size.
	useEffect( () => {
		if ( isGeneralSidebarOpened ) {
			closePublishSidebar();
		}
	}, [ isGeneralSidebarOpened ] );
	useEffect( () => {
		if ( isPublishSidebarOpened ) {
			closeGeneralSidebar();
		}
	}, [ isPublishSidebarOpened ] );

	return (
		<>
			<InterfaceSkeleton
				className={ className }
				header={
					<Header
						isSidebarOpened={ isGeneralSidebarOpened }
						openSidebar={ openSidebarPanel }
						closeSidebar={ closeGeneralSidebar }
						isPublishSidebarOpened={ isPublishSidebarOpened }
						openPublishSidebar={ openPublishSidebar }
						closePublishSidebar={ closePublishSidebar }
					/>
				}
				secondarySidebar={
					isInserterOpened && (
						<div
							ref={ inserterDialogRef }
							{ ...inserterDialogProps }
							className="edit-post-layout__inserter-panel-popover-wrapper"
						>
							<div className="edit-post-layout__inserter-panel">
								<div className="edit-post-layout__inserter-panel-header">
									<Button icon={ close } onClick={ () => setIsInserterOpened( false ) } />
								</div>
								<div className="edit-post-layout__inserter-panel-content">
									<Library
										onSelect={ () => {
											if ( isMobileViewport ) {
												setIsInserterOpened( false );
											}
										} }
									/>
								</div>
							</div>
						</div>
					)
				}
				sidebar={
					isGeneralSidebarOpened && (
						<div className="block-pattern-creator__sidebar interface-complementary-area">
							<Sidebar onClose={ closeGeneralSidebar } />
						</div>
					)
				}
				actions={
					isPublishSidebarOpened && (
						<div
							ref={ publishSidebarDialogRef }
							{ ...publishSidebarDialogProps }
							className="block-pattern-creator__sidebar interface-complementary-area"
						>
							<Settings closeSidebar={ closePublishSidebar } />
						</div>
					)
				}
				content={
					<>
						<EditorNotices />
						<VisualEditor styles={ defaultEditorStyles } />
						{ isMobileViewport && isSidebarOpened && <ScrollLock /> }
					</>
				}
				footer={
					! isMobileViewport && (
						<div className="edit-post-layout__footer">
							<BlockBreadcrumb />
						</div>
					)
				}
			/>
		</>
	);
}
