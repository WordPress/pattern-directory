/**
 * External dependencies
 */
import { BlockBreadcrumb, BlockInspector, __experimentalLibrary as Library } from '@wordpress/block-editor';
import { Button, ScrollLock } from '@wordpress/components';
import classnames from 'classnames';
import { close } from '@wordpress/icons';
import { InterfaceSkeleton } from '@wordpress/interface';
import { EditorNotices } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { useViewportMatch } from '@wordpress/compose';

/**
 * Edit-Post dependencies
 */
import PopoverWrapper from '@wordpress/edit-post/build/components/layout/popover-wrapper';
import VisualEditor from '@wordpress/edit-post/build/components/visual-editor';

/**
 * Internal dependencies
 */
import Header from '../header';
import Settings from '../settings';
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
	} = useDispatch( 'core/edit-post' );
	const { isGeneralSidebarOpened, isPublishSidebarOpened, isInserterOpened } = useSelect( ( select ) => {
		return {
			isGeneralSidebarOpened: !! select( 'core/interface' ).getActiveComplementaryArea( 'core/edit-post' ),
			isPublishSidebarOpened: select( 'core/edit-post' ).isPublishSidebarOpened(),
			isInserterOpened: select( 'core/edit-post' ).isInserterOpened(),
		};
	}, [] );
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
				leftSidebar={
					isInserterOpened && (
						<PopoverWrapper
							className="edit-post-layout__inserter-panel-popover-wrapper"
							onClose={ () => setIsInserterOpened( false ) }
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
						</PopoverWrapper>
					)
				}
				sidebar={
					isGeneralSidebarOpened && (
						<div className="block-pattern-creator__sidebar interface-complementary-area">
							<div className="block-pattern-creator__sidebar-header">
								<Button
									icon={ close }
									onClick={ closeGeneralSidebar }
									label="Close block settings"
								/>
							</div>
							<BlockInspector />
						</div>
					)
				}
				actions={
					isPublishSidebarOpened && (
						<div className="block-pattern-creator__sidebar interface-complementary-area">
							<Settings closeSidebar={ closePublishSidebar } />
						</div>
					)
				}
				content={
					<div className="block-pattern-creator__editor editor-styles-wrapper">
						<EditorNotices />
						<VisualEditor />
						{ isMobileViewport && isSidebarOpened && <ScrollLock /> }
					</div>
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
