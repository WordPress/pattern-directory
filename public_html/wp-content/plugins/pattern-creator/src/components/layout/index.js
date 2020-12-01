/**
 * External dependencies
 */
import { BlockBreadcrumb, __experimentalLibrary as Library } from '@wordpress/block-editor';
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
import Inspector from '../inspector';

export default function Layout() {
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const isHugeViewport = useViewportMatch( 'huge', '>=' );
	const { openGeneralSidebar, closeGeneralSidebar, setIsInserterOpened } = useDispatch( 'core/edit-post' );
	const { sidebarIsOpened, hasBlockSelected, isInserterOpened } = useSelect( ( select ) => {
		return {
			sidebarIsOpened: !! (
				select( 'core/interface' ).getActiveComplementaryArea( 'core/edit-post' ) ||
				select( 'core/edit-post' ).isPublishSidebarOpened()
			),
			isFullscreenActive: select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ),
			isInserterOpened: select( 'core/edit-post' ).isInserterOpened(),
		};
	}, [] );
	const className = classnames( 'edit-wporg-pattern-layout', 'edit-post-layout', 'is-mode-visual', {
		'is-sidebar-opened': sidebarIsOpened,
	} );
	const openSidebarPanel = () =>
		openGeneralSidebar( hasBlockSelected ? 'edit-post/block' : 'edit-post/document' );

	// Inserter and Sidebars are mutually exclusive
	useEffect( () => {
		if ( sidebarIsOpened && ! isHugeViewport ) {
			setIsInserterOpened( false );
		}
	}, [ sidebarIsOpened, isHugeViewport ] );
	useEffect( () => {
		if ( isInserterOpened && ! isHugeViewport ) {
			closeGeneralSidebar();
		}
	}, [ isInserterOpened, isHugeViewport ] );

	return (
		<>
			<InterfaceSkeleton
				className={ className }
				header={
					<Header
						isInserterOpened={ isInserterOpened }
						setIsInserterOpened={ setIsInserterOpened }
						isInspectorOpened={ sidebarIsOpened }
						openInspector={ openSidebarPanel }
						closeInspector={ closeGeneralSidebar }
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
				sidebar={ sidebarIsOpened && <Inspector /> }
				content={
					<div className="block-pattern-creator__editor editor-styles-wrapper">
						<EditorNotices />
						<VisualEditor />
						{ isMobileViewport && sidebarIsOpened && <ScrollLock /> }
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
