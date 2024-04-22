<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2024;

use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorites, get_favorite_count};
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\POST_TYPE as FLAG_POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\PENDING_STATUS;
use function WordPressdotorg\Theme\Pattern_Directory_2024\Block_Config\get_applied_filter_list;

// Block files
require_once( __DIR__ . '/src/blocks/copy-button/index.php' );
require_once( __DIR__ . '/src/blocks/delete-button/index.php' );
require_once( __DIR__ . '/src/blocks/favorite-button/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-preview/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-thumbnail/index.php' );
require_once( __DIR__ . '/src/blocks/post-status/index.php' );
require_once( __DIR__ . '/src/blocks/report-pattern/index.php' );
require_once( __DIR__ . '/src/blocks/status-notice/index.php' );

require_once( __DIR__ . '/inc/block-config.php' );
require_once( __DIR__ . '/inc/shortcodes.php' );

/**
 * Actions and filters.
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_action( 'template_redirect', __NAMESPACE__ . '\do_pattern_actions' );
add_action( 'query_vars', __NAMESPACE__ . '\add_patterns_query_vars' );
add_action( 'pre_get_posts', __NAMESPACE__ . '\modify_patterns_query' );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\modify_query_loop_block_query_vars', 10, 3 );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\custom_query_loop_by_id', 20, 2 );
add_action( 'template_redirect', __NAMESPACE__ . '\redirect_term_archives' );
add_action( 'wp_head', __NAMESPACE__ . '\add_social_meta_tags', 5 );
add_filter( 'document_title_parts', __NAMESPACE__ . '\set_document_title' );
add_filter( 'body_class', __NAMESPACE__ . '\add_status_body_class' );
add_filter( 'frontpage_template_hierarchy', __NAMESPACE__ . '\use_archive_template_paged' );

add_action(
	'init',
	function() {
		// Don't swap author link with w.org profile link.
		remove_all_filters( 'author_link' );

		// Remove the "By…" from the author name block.
		remove_filter( 'render_block_core/post-author-name', 'WordPressdotorg\Theme\Parent_2021\Gutenberg_Tweaks\render_author_prefix', 10, 2 );
	}
);

/**
 * Enqueue scripts and styles.
 */
function enqueue_assets() {
	// The parent style is registered as `wporg-parent-2021-style`, and will be loaded unless
	// explicitly unregistered. We can load any child-theme overrides by declaring the parent
	// stylesheet as a dependency.
	wp_enqueue_style(
		'wporg-pattern-directory-2024-style',
		get_stylesheet_uri(),
		array( 'wporg-parent-2021-style', 'wporg-global-fonts' ),
		filemtime( __DIR__ . '/style.css' )
	);
}

/**
 * Check if the current request needs an action, and run that action.
 *
 * Available actions:
 * - draft: Update the current post to a draft.
 */
function do_pattern_actions() {
	if ( ! is_singular( POST_TYPE ) ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : false;
	$post_id = get_the_ID();

	if ( 'draft' === $action ) {
		if ( wp_verify_nonce( $nonce, 'draft-' . $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			// Draft the post.
			$success = wp_update_post(
				array(
					'ID' => $post_id,
					'post_status' => 'draft',
				)
			);
			if ( $success ) {
				// Reload the page without the action.
				wp_safe_redirect( get_the_permalink() );
			} else {
				// Reload the page with an error flag.
				$url = add_query_arg(
					array(
						'status' => 'draft-failed',
					),
					get_the_permalink()
				);
				wp_safe_redirect( $url );
			}
		}
	} else if ( 'report' === $action ) {
		if ( wp_verify_nonce( $nonce, 'report-' . $post_id ) && current_user_can( 'read' ) ) {
			$success = wp_insert_post(
				array(
					'post_type'   => FLAG_POST_TYPE,
					'post_parent' => $post_id,
					'post_excerpt'  => sanitize_text_field( $_POST['report-details'] ),
					'post_status' => PENDING_STATUS,
					'tax_input'  => array(
						'wporg-pattern-flag-reason' => intval( $_POST['report-reason'] ),
					),
				)
			);
			if ( $success ) {
				$args = array(
					'status' => 'reported',
				);
			} else {
				$args = array(
					'status' => 'report-failed',
				);
			}
		} else {
			$args = array(
				'status' => 'logged-out',
			);
		}

		wp_safe_redirect( add_query_arg( $args, get_the_permalink() ) );
	}
}

/**
 * Add custom query parameters.
 *
 * @param array $query_vars
 *
 * @return array
 */
function add_patterns_query_vars( $query_vars ) {
	$query_vars[] = 'curation';
	$query_vars[] = 'status';
	return $query_vars;
}

/**
 * Update the query to show patters according to the "curation" &
 * sort order filters.
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
function modify_patterns_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// If `curation` is passed and either `core` or `community`, we should
	// filter the result. If `curation=all`, no filtering is needed.
	$curation = $query->get( 'curation' );
	if ( ! $curation && ! is_author() ) {
		$query->set( 'curation', 'core' );
		$curation = 'core';
	}

	if ( $curation ) {
		$tax_query = isset( $query->tax_query->queries ) ? $query->tax_query->queries : [];
		if ( 'core' === $curation ) {
			// Patterns with the core keyword.
			$tax_query['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => [ 'core' ],
				'operator' => 'IN',
			);
		} else if ( 'community' === $curation ) {
			// Patterns without the core keyword.
			$tax_query['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => [ 'core' ],
				'operator' => 'NOT IN',
			);
		}
		$query->set( 'tax_query', $tax_query );
	}

	if ( str_ends_with( $query->get( 'orderby' ), '_desc' ) ) {
		$orderby = str_replace( '_desc', '', $query->get( 'orderby' ) );
		$query->set( 'orderby', $orderby );
		$query->set( 'order', 'desc' );
	} else if ( str_ends_with( $query->get( 'orderby' ), '_asc' ) ) {
		$orderby = str_replace( '_asc', '', $query->get( 'orderby' ) );
		$query->set( 'orderby', $orderby );
		$query->set( 'order', 'asc' );
	}

	if ( $query->get( 'orderby' ) === 'favorite_count' ) {
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', 'wporg-pattern-favorites' );
	}

	if ( ! $query->is_singular() ) {
		$query->set( 'post_type', array( POST_TYPE ) );

		// The `orderby_locale` meta_query will be transformed into a query orderby by Pattern_Post_Type\filter_orderby_locale().
		$query->set( 'meta_query', array(
			'orderby_locale' => array(
				'key'     => 'wpop_locale',
				'compare' => 'IN',
				// Order in value determines result order
				'value'   => array( get_locale(), 'en_US' ),
			),
		) );
	}
}

/**
 * Set up query customizations for the Query Loop block.
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 *
 * @return array
 */
function modify_query_loop_block_query_vars( $query, $block, $page ) {
	global $wp_query;

	// Return early if this is a pattern view page.
	if ( isset( $wp_query->query_vars['view'] ) ) {
		return $query;
	}

	if ( ! isset( $query['posts_per_page'] ) ) {
		$query['posts_per_page'] = 24;
	}

	if ( isset( $page ) && ! isset( $query['offset'] ) ) {
		$query['paged'] = $page;
	}

	if ( isset( $block->context['query']['curation'] ) ) {
		if ( 'core' === $block->context['query']['curation'] ) {
			// Patterns with the core keyword.
			$query['tax_query']['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => 'core',
				'operator' => 'IN',
			);
		} else if ( 'community' === $block->context['query']['curation'] ) {
			// Patterns without the core keyword.
			$query['tax_query']['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => [ 'core' ],
				'operator' => 'NOT IN',
			);
		}
	}

	if ( isset( $block->context['query']['orderBy'] ) && 'favorite_count' === $block->context['query']['orderBy'] ) {
		$query['orderby'] = 'meta_value_num';
		$query['meta_key'] = 'wporg-pattern-favorites';
	}

	// Query Loops on My Patterns & Favorites pages
	if ( is_page( [ 'my-patterns', 'favorites' ] ) ) {
		// Get these values from the global wp_query, they're passed via the URL.
		if ( isset( $wp_query->query['pattern-categories'] ) ) {
			if ( ! isset( $query['tax_query'] ) || ! is_array( $query['tax_query'] ) ) {
				$query['tax_query'] = array();
			}
			$query['tax_query'][] = array(
				'taxonomy'         => 'wporg-pattern-category',
				'field'            => 'slug',
				'terms'            => $wp_query->query['pattern-categories'],
				'include_children' => false,
			);
		}

		if ( isset( $wp_query->query['orderby'] ) ) {
			if ( str_ends_with( $wp_query->query['orderby'], '_desc' ) ) {
				$orderby = str_replace( '_desc', '', $wp_query->query['orderby'] );
				$query['orderby'] = $orderby;
				$query['order'] = 'desc';
			} else if ( str_ends_with( $wp_query->query['orderby'], '_asc' ) ) {
				$orderby = str_replace( '_asc', '', $wp_query->query['orderby'] );
				$query['orderby'] = $orderby;
				$query['order'] = 'asc';
			}
		}

		if ( is_page( 'my-patterns' ) ) {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$query['post_type'] = 'wporg-pattern';
				$query['post_status'] = 'any';
				$query['author'] = get_current_user_id();
			} else {
				$query['post__in'] = [ -1 ];
			}

			if ( isset( $wp_query->query['status'] ) ) {
				$query['post_status'] = $wp_query->query['status'];
			}
		}

		if ( is_page( 'favorites' ) ) {
			$favorites = get_favorites();
			if ( ! empty( $favorites ) ) {
				$query['post__in'] = get_favorites();
			} else {
				$query['post__in'] = [ -1 ];
			}
		}
	}

	// The `orderby_locale` meta_query will be transformed into a query orderby by Pattern_Post_Type\filter_orderby_locale().
	$query['meta_query'] = array(
		'orderby_locale' => array(
			'key'     => 'wpop_locale',
			'compare' => 'IN',
			// Order in value determines result order
			'value'   => array( get_locale(), 'en_US' ),
		),
	);

	return $query;
}

/**
 * Override Query Loop parameters if an `_id` property is found.
 *
 * This is a workaround to allow setting more complicated queries. For example,
 * using the current author & excluding the current post.
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 *
 * @return array
 */
function custom_query_loop_by_id( $query, $block ) {
	if ( ! isset( $block->context['query']['_id'] ) ) {
		return $query;
	}

	$current_post = get_post();
	if ( 'more-by-author' === $block->context['query']['_id'] && $current_post && $current_post->post_author ) {
		$query['author'] = $current_post->post_author;
		$query['post__not_in'] = [ $current_post->ID ];
		$query['post_type'] = 'wporg-pattern';
	}

	if ( 'empty-favorites' === $block->context['query']['_id'] ) {
		unset( $query['post__in'] );
		$query['post_type'] = 'wporg-pattern';
		$query['orderby'] = 'meta_value_num';
		$query['meta_key'] = 'wporg-pattern-favorites';
	}

	return $query;
}

/**
 * Get the preview URL for the current pattern.
 *
 * @param int|WP_Post $post Post ID or post object.
 *
 * @return string The pattern `view` URL.
 */
function get_pattern_preview_url( $post = 0 ) {
	$view_url = add_query_arg( 'view', true, get_permalink( $post ) );
	return apply_filters( 'wporg_pattern_preview_url', $view_url, $post );
}

/**
 * Get the count of all patterns on the site (for the current locale).
 *
 * @return int
 */
function get_patterns_count() {
	global $wpdb;
	$locale = get_locale();

	// Cache for an hour to avoid extra DB lookup.
	$cache_key = 'wporg-patterns-count-' . $locale;
	$ttl = HOUR_IN_SECONDS;

	$count = get_transient( $cache_key );
	if ( ! $count ) {
		$sql = "SELECT COUNT(*) FROM $wpdb->posts
			INNER JOIN $wpdb->postmeta
			ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			WHERE {$wpdb->posts}.post_status = 'publish'
			AND {$wpdb->postmeta}.meta_key = 'wpop_locale'
			AND {$wpdb->postmeta}.meta_value = '%s'";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $locale ) );
		set_transient( $cache_key, $count, $ttl );
	}
	return $count;
}

/**
 * Checks whether the user has a pending flag for a specific pattern.
 *
 * @return bool
 */
function user_has_flagged_pattern() {
	$args = array(
		'author' => get_current_user_id(),
		'post_parent' => get_the_ID(),
		'post_type' => FLAG_POST_TYPE,
		'post_status' => PENDING_STATUS,
	);

	$items = new \WP_Query( $args );

	return $items->have_posts();
}

/**
 * Redirect category and tag archives to their canonical URLs.
 *
 * This prevents double URLs for every category/tag, e.g.,
 * `/?pattern-categories=footer` and `/categories/footer/`.
 */
function redirect_term_archives() {
	global $wp_query;
	$terms = get_applied_filter_list( false );
	// True on the `/tag/…` URLs, and false on `/?tag…` URLs.
	$is_term_archive = is_tag() || is_category() || is_tax();

	// Don't redirect on favorites or author archives.
	$bypass_redirect = is_page( 'favorites' ) || is_author();
	if ( $bypass_redirect ) {
		return;
	}

	// If there is only one term applied, and we're not already on a term
	// archive, redirect to the main term archive URL.
	if ( count( $terms ) === 1 && ! $is_term_archive ) {
		$url = get_term_link( $terms[0] );
		// Pass through search query, curation, sorting values.
		$query_vars = [ 's', 'curation', 'order', 'orderby' ];
		foreach ( $query_vars as $query_var ) {
			if ( isset( $wp_query->query[ $query_var ] ) ) {
				$url = add_query_arg( $query_var, $wp_query->query[ $query_var ], $url );
			}
		}
		wp_safe_redirect( $url );
		exit;
	}
}

/**
 * Add meta tags for richer social media integrations.
 */
function add_social_meta_tags() {
	$og_fields     = [];
	$default_image = 'https://s.w.org/patterns/files/2024/04/patterns-ogimage.png';
	$site_title    = function_exists( '\WordPressdotorg\site_brand' ) ? \WordPressdotorg\site_brand() : 'WordPress.org';

	if ( is_front_page() || is_home() ) {
		$og_fields = [
			'og:title'       => __( 'Block Pattern Directory', 'wporg-patterns' ),
			'og:description' => __( 'Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste.', 'wporg-patterns' ),
			'og:site_name'   => $site_title,
			'og:type'        => 'website',
			'og:url'         => home_url(),
			'og:image'       => esc_url( $default_image ),
		];
	} else if ( is_tax() && get_queried_object() ) {
		$og_fields = [
			'og:title'       => sprintf( __( 'Block Patterns: %s', 'wporg-patterns' ), esc_attr( single_term_title( '', false ) ) ),
			'og:description' => __( 'Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste.', 'wporg-patterns' ),
			'og:site_name'   => $site_title,
			'og:type'        => 'website',
			'og:url'         => esc_url( get_term_link( get_queried_object_id() ) ),
			'og:image'       => esc_url( $default_image ),
		];
	} else if ( is_singular( POST_TYPE ) ) {
		$og_fields = [
			'og:title'       => the_title_attribute( array( 'echo' => false ) ),
			'og:description' => strip_tags( get_post_meta( get_the_ID(), 'wpop_description', true ) ),
			'og:site_name'   => $site_title,
			'og:type'        => 'website',
			'og:url'         => esc_url( get_permalink() ),
			'og:image'       => esc_url( $default_image ),
		];
		printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
		printf( '<meta name="twitter:site" content="@WordPress">' . "\n" );
		printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $default_image ) );
	}

	foreach ( $og_fields as $property => $content ) {
		printf(
			'<meta property="%1$s" content="%2$s" />' . "\n",
			esc_attr( $property ),
			esc_attr( $content )
		);
	}

	if ( isset( $og_fields['og:description'] ) ) {
		printf(
			'<meta name="description" content="%1$s" />' . "\n",
			esc_attr( $og_fields['og:description'] )
		);
	}
}

/**
 * Append an optimized site name.
 *
 * @param array $title {
 *     The document title parts.
 *
 *     @type string $title   Title of the viewed page.
 *     @type string $page    Optional. Page number if paginated.
 *     @type string $tagline Optional. Site description when on home page.
 *     @type string $site    Optional. Site title when not on home page.
 * }
 * @return array Filtered title parts.
 */
function set_document_title( $title ) {
	global $wp_query;

	if ( is_front_page() ) {
		$title['title']   = __( 'Block Pattern Directory', 'wporg-patterns' );
		$title['tagline'] = __( 'WordPress.org', 'wporg-patterns' );
	} else {
		if ( is_singular( POST_TYPE ) ) {
			$title['title'] .= ' - ' . __( 'Block Pattern', 'wporg-patterns' );
		} elseif ( is_tax() ) {
			/* translators: Taxonomy term name */
			$title['title'] = sprintf( __( 'Block Patterns: %s', 'wporg-patterns' ), $title['title'] );
		} elseif ( is_author() ) {
			/* translators: Author name */
			$title['title'] = sprintf( __( 'Block Patterns by %s', 'wporg-patterns' ), $title['title'] );
		}

		// If results are paged and the max number of pages is known.
		if ( is_paged() && $wp_query->max_num_pages ) {
			// translators: 1: current page number, 2: total number of pages
			$title['page'] = sprintf(
				__( 'Page %1$s of %2$s', 'wporg-patterns' ),
				get_query_var( 'paged' ),
				$wp_query->max_num_pages
			);
		}

		$title['site'] = __( 'WordPress.org', 'wporg-patterns' );
	}

	return $title;
}

/**
 * Filter the body class on single "owned" patterns.
 *
 * This allows for hiding some status-specific buttons.
 *
 * @param string[] $classes An array of body class names.
 *
 * @return string[] Filtered classes.
 */
function add_status_body_class( $classes ) {
	if ( ! is_singular( POST_TYPE ) || get_current_user_id() !== get_the_author_meta( 'ID' ) ) {
		return $classes;
	}
	$status = get_post_status();
	if ( $status ) {
		$classes[] = 'is-status-' . $status;
	}
	return $classes;
}

/**
 * Switch to the archive.html template on paged requests.
 *
 * @param string[] $templates A list of template candidates, in descending order of priority.
 */
function use_archive_template_paged( $templates ) {
	if ( is_paged() ) {
		array_unshift( $templates, 'archive.html' );
	}
	return $templates;
}
