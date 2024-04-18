<?php
/**
 * Set up configuration for dynamic blocks.
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Block_Config;

use WP_Block_Supports;
use function WordPressdotorg\Theme\Pattern_Directory_2024\get_patterns_count;

add_action( 'init', __NAMESPACE__ . '\register_block_bindings' );
add_filter( 'wporg_query_total_label', __NAMESPACE__ . '\update_query_total_label', 10, 2 );
add_filter( 'wporg_query_filter_options_curation', __NAMESPACE__ . '\get_curation_options' );
add_filter( 'wporg_query_filter_options_sort', __NAMESPACE__ . '\get_sort_options' );
add_action( 'wporg_query_filter_in_form', __NAMESPACE__ . '\inject_other_filters' );
add_filter( 'render_block_core/search', __NAMESPACE__ . '\inject_category_search_block' );
add_filter( 'wporg_block_navigation_menus', __NAMESPACE__ . '\add_site_navigation_menus' );
add_filter( 'render_block_core/query-title', __NAMESPACE__ . '\update_archive_title', 10, 3 );
add_filter( 'render_block_core/site-title', __NAMESPACE__ . '\update_site_title', 10, 3 );
add_filter( 'wporg_block_site_breadcrumbs', __NAMESPACE__ . '\update_site_breadcrumbs' );
add_filter( 'render_block_data', __NAMESPACE__ . '\modify_pattern_include' );

/**
 * Register block bindings.
 *
 * This registers some sources which can be used to dynamically inject content
 * into block text or attributes.
 */
function register_block_bindings() {
	register_block_bindings_source(
		'wporg-pattern/edit-label',
		array(
			'label' => __( 'Edit label', 'wporg-patterns' ),
			'uses_context' => [ 'postId' ],
			'get_value_callback' => function( $args, $block ) {
				$post_id = $block->context['postId'];
				/* translators: %s: Post title. Only visible to screen readers. */
				return sprintf(
					__( 'Edit <span class="screen-reader-text">"%s"</span>', 'wporg-patterns' ),
					get_the_title( $post_id )
				);
			},
		)
	);

	register_block_bindings_source(
		'wporg-pattern/edit-url',
		array(
			'label' => __( 'Edit link', 'wporg-patterns' ),
			'uses_context' => [ 'postId' ],
			'get_value_callback' => function( $args, $block ) {
				$post_id = $block->context['postId'];
				return site_url( "pattern/$post_id/edit/" );
			},
		)
	);
}

/**
 * Get a list of the currently-applied filters.
 *
 * @param boolean $include_extras Whether the result should include all
 *                                filters or just the human-readable ones.
 *
 * @return array
 */
function get_applied_filter_list( $include_extras = true ) {
	global $wp_query;
	$terms = [];
	$taxes = [
		'pattern-categories' => 'wporg-pattern-category',
	];
	foreach ( $taxes as $query_var => $taxonomy ) {
		if ( ! isset( $wp_query->query[ $query_var ] ) ) {
			continue;
		}
		$values = (array) $wp_query->query[ $query_var ];
		foreach ( $values as $value ) {
			$key = ( 'cat' === $query_var ) ? 'id' : 'slug';
			$term = get_term_by( $key, $value, $taxonomy );
			if ( $term ) {
				$terms[] = $term;
			}
		}
	}
	if ( $include_extras && isset( $wp_query->query['curation'] ) ) {
		$terms[] = array( 'name' => $wp_query->query['curation'] );
	}
	if ( $include_extras && isset( $wp_query->query['orderby'] ) ) {
		$terms[] = array( 'name' => $wp_query->query['orderby'] );
	}
	if ( $include_extras && isset( $wp_query->query['s'] ) ) {
		$terms[] = array( 'name' => $wp_query->query['s'] );
	}
	return $terms;
}

/**
 * Get the destination for query-filter submission based on the current page.
 *
 * @return string
 */
function get_filter_action_url() {
	global $wp;
	if ( is_page( 'favorites' ) || is_page( 'my-patterns' ) || is_author() ) {
		return home_url( $wp->request );
	}
	return home_url( '/' );
}

/**
 * Update the query total label to reflect "patterns" found.
 *
 * @param string $label       The maybe-pluralized label to use, a result of `_n()`.
 * @param int    $found_posts The number of posts to use for determining pluralization.
 * @return string Updated string with total placeholder.
 */
function update_query_total_label( $label, $found_posts ) {
	global $wp_query;

	if ( is_front_page() ) {
		// Override the current query count, instead display the total number of patterns.
		$count = get_patterns_count();

		/* translators: %s: the result count. */
		return sprintf( _n( '%s pattern', '%s patterns', $count, 'wporg-patterns' ), number_format_i18n( $count ) );
	}

	$current = is_string( $wp_query->get( 'curation' ) ) ? strtolower( $wp_query->get( 'curation' ) ) : '';
	if ( $wp_query->is_archive() ) {
		if ( 'core' === $current ) {
			/* translators: %s: the result count. */
			return _n( '%s curated pattern', '%s curated patterns', $found_posts, 'wporg-patterns' );
		} else if ( 'community' === $current ) {
			/* translators: %s: the result count. */
			return _n( '%s community pattern', '%s community patterns', $found_posts, 'wporg-patterns' );
		}
	}

	/* translators: %s: the result count. */
	return _n( '%s pattern', '%s patterns', $found_posts, 'wporg-patterns' );
}

/**
 * Provide a list of curation options.
 *
 * @param array $options The options for this filter.
 * @return array New list of curation options.
 */
function get_curation_options( $options ) {
	global $wp_query;
	$current = is_string( $wp_query->get( 'curation' ) ) ? strtolower( $wp_query->get( 'curation' ) ) : '';

	$label = __( 'Filter by', 'wporg-patterns' );
	switch ( $current ) {
		case 'community':
			$label = _x( 'Community', 'filter option label', 'wporg-patterns' );
			break;
		case 'core':
			$label = _x( 'Curated', 'filter option label', 'wporg-patterns' );
			break;
		default:
			$label = _x( 'All', 'filter option label', 'wporg-patterns' );
			break;
	}

	return array(
		'label' => $label,
		'title' => __( 'Filter', 'wporg-patterns' ),
		'key' => 'curation',
		'action' => get_filter_action_url(),
		'options' => array(
			'all' => _x( 'All', 'filter option label', 'wporg-patterns' ),
			'community' => _x( 'Community', 'filter option label', 'wporg-patterns' ),
			'core' => _x( 'Curated', 'filter option label', 'wporg-patterns' ),
		),
		'selected' => [ $current ],
	);
}

/**
 * Provide a list of sort options.
 *
 * @param array $options The options for this filter.
 * @return array New list of sort options.
 */
function get_sort_options( $options ) {
	global $wp_query;
	$orderby = strtolower( $wp_query->get( 'orderby', 'date' ) );
	$order = strtolower( $wp_query->get( 'order', 'desc' ) );
	$sort = $orderby . '_' . $order;

	// Popular is a special case since it's not a true "order" value.
	if ( 'meta_value_num' === $orderby && 'wporg-pattern-favorites' === $wp_query->get( 'meta_key' ) ) {
		$sort = 'favorite_count_desc';
	}

	$label = __( 'Sort', 'wporg-patterns' );
	switch ( $sort ) {
		case 'date_desc':
			$label = __( 'Newest', 'wporg-patterns' );
			break;
		case 'date_asc':
			$label = __( 'Oldest', 'wporg-patterns' );
			break;
		case 'favorite_count_desc':
			$label = __( 'Popular', 'wporg-patterns' );
			break;
	}

	$options = array(
		'date_desc' => __( 'Newest', 'wporg-patterns' ),
		'date_asc' => __( 'Oldest', 'wporg-patterns' ),
	);

	// These pages don't support sorting by favorite count.
	if ( ! is_page( [ 'my-patterns', 'favorites' ] ) ) {
		$options = array_merge(
			array(
				'favorite_count_desc' => __( 'Popular', 'wporg-patterns' ),
			),
			$options
		);
	}

	return array(
		'label' => $label,
		'title' => __( 'Sort', 'wporg-patterns' ),
		'key' => 'orderby',
		'action' => get_filter_action_url(),
		'options' => $options,
		'selected' => [ $sort ],
	);
}

/**
 * Add in the other existing filters as hidden inputs in the filter form.
 *
 * Enables combining filters by building up the correct URL on submit,
 * for example patterns using a tag, a category, and matching a search term:
 *   ?tag[]=cuisine&cat[]=3&s=wordpress`
 *
 * @param string $key The key for the current filter.
 */
function inject_other_filters( $key ) {
	global $wp_query;

	// Single-select query parameters.
	$query_vars = [ 'pattern-categories', 'order', 'orderby', 'curation' ];
	foreach ( $query_vars as $query_var ) {
		if ( ! isset( $wp_query->query[ $query_var ] ) ) {
			continue;
		}
		if ( $key === $query_var ) {
			continue;
		}
		$values = (array) $wp_query->query[ $query_var ];
		foreach ( $values as $value ) {
			printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $query_var ), esc_attr( $value ) );
		}
	}

	// Pass through search query.
	if ( isset( $wp_query->query['s'] ) ) {
		printf( '<input type="hidden" name="s" value="%s" />', esc_attr( $wp_query->query['s'] ) );
	}
}

/**
 * Inject the current category into the search form.
 *
 * @param string $block_content
 *
 * @return string
 */
function inject_category_search_block( $block_content ) {
	global $wp_query;
	$category_inputs = '';
	$query_var = 'pattern-categories';
	if ( isset( $wp_query->query[ $query_var ] ) ) {
		$values = (array) $wp_query->query[ $query_var ];
		foreach ( $values as $value ) {
			$category_inputs .= sprintf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $query_var ), esc_attr( $value ) );
		}
	}

	return str_replace( '</form>', $category_inputs . '</form>', $block_content );
}

/**
 * Provide a list of local navigation menus.
 */
function add_site_navigation_menus( $menus ) {
	global $wp_query, $wp;

	$menu = array();
	$categories = array();
	$statuses = array();

	$menu[] = array(
		'label' => __( 'Favorites', 'wporg-patterns' ),
		'url' => '/favorites/',
	);
	if ( is_user_logged_in() ) {
		$menu[] = array(
			'label' => __( 'My Patterns', 'wporg-patterns' ),
			'url' => '/my-patterns/',
		);
	}
	$menu[] = array(
		'label' => __( 'New Pattern', 'wporg-patterns' ),
		'url' => '/new-pattern/',
	);

	$current_status = isset( $wp_query->query['status'] ) ? $wp_query->query['status'] : false;
	$statuses = array(
		array(
			'label' => __( 'All', 'wporg-patterns' ),
			'url' => get_permalink(),
			'className' => ! $current_status ? 'current-menu-item' : '',
		),
		array(
			'label' => __( 'Draft', 'wporg-patterns' ),
			'url' => add_query_arg( 'status', 'draft', get_permalink() ),
			'className' => 'draft' === $current_status ? 'current-menu-item' : '',
		),
		array(
			'label' => __( 'Pending Review', 'wporg-patterns' ),
			'url' => add_query_arg( 'status', 'pending', get_permalink() ),
			'className' => 'pending' === $current_status ? 'current-menu-item' : '',
		),
		array(
			'label' => __( 'Published', 'wporg-patterns' ),
			'url' => add_query_arg( 'status', 'publish', get_permalink() ),
			'className' => 'publish' === $current_status ? 'current-menu-item' : '',
		),
	);

	// Build category list, given a specific list/order of terms to display.
	$terms = get_terms(
		array(
			'taxonomy' => 'wporg-pattern-category',
			'slug' => array(
				// `query` is "Posts".
				'featured', 'query', 'text', 'gallery', 'call-to-action',
				'banner', 'header', 'footer', 'wireframe',
			),
			'orderby' => 'slug__in',
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		$current_cats = isset( $wp_query->query['pattern-categories'] ) ? (array) $wp_query->query['pattern-categories'] : array();
		foreach ( $terms as $term ) {
			$cat = array(
				'label' => $term->name,
				'url' => get_term_link( $term ),
			);
			if ( in_array( $term->slug, $current_cats ) ) {
				$cat['className'] = 'current-menu-item';
			}
			if ( is_page( 'favorites' ) || is_author() ) {
				$cat['url'] = add_query_arg( 'pattern-categories', $term->slug, home_url( $wp->request ) );
			}

			$categories[] = $cat;
		}
		$all_link = array(
			'label' => __( 'All', 'wporg-patterns' ),
			'url' => is_page( 'favorites' ) || is_author() ? home_url( $wp->request ) : home_url( '/' ),
			'className' => empty( $current_cats ) ? 'current-menu-item' : '',
		);
		array_unshift( $categories, $all_link );
	}

	return array(
		'main' => $menu,
		'categories' => $categories,
		'statuses' => $statuses,
	);
}

/**
 * Update the archive title for all filter views.
 *
 * @param string   $block_content The block content.
 * @param array    $block         The full block, including name and attributes.
 * @param WP_Block $instance      The block instance.
 */
function update_archive_title( $block_content, $block, $instance ) {
	global $wp_query;
	$attributes = $block['attrs'];

	if ( isset( $attributes['type'] ) && 'filter' === $attributes['type'] ) {
		// Skip output if there are no results. The `query-no-results` has an h1.
		if ( ! $wp_query->found_posts ) {
			return '';
		}

		$term_names = get_applied_filter_list( false );
		if ( ! empty( $term_names ) ) {
			$term_names = wp_list_pluck( $term_names, 'name' );
			// translators: %s list of terms used for filtering.
			$title = sprintf( __( 'Patterns: %s', 'wporg-patterns' ), implode( ', ', $term_names ) );
		} else {
			$author = isset( $wp_query->query['author_name'] ) ? get_user_by( 'slug', $wp_query->query['author_name'] ) : false;
			if ( $author ) {
				$title = sprintf( __( 'Author: %s', 'wporg-patterns' ), $author->display_name );
			} else {
				$title = __( 'All patterns', 'wporg-patterns' );
			}
		}

		if ( is_search() ) {
			$title = __( 'Search results', 'wporg-patterns' );
		}

		$tag_name           = isset( $attributes['level'] ) ? 'h' . (int) $attributes['level'] : 'h1';
		$align_class_name   = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";

		// Required to prevent `block_to_render` from being null in `get_block_wrapper_attributes`.
		$parent = WP_Block_Supports::$block_to_render;
		WP_Block_Supports::$block_to_render = $block;
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );
		WP_Block_Supports::$block_to_render = $parent;

		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			$title
		);
	}
	return $block_content;
}

/**
 * Update the archive title for all filter views.
 *
 * @param string $block_content The block content.
 */
function update_site_title( $block_content, $block, $instance ) {
	return str_replace(
		get_bloginfo( 'name' ),
		__( 'Patterns', 'wporg-patterns' ),
		$block_content
	);
}

/**
 * Update the breadcrumbs to the current page.
 */
function update_site_breadcrumbs( $breadcrumbs ) {
	global $wp_query;
	// Get the current applied filters (except search, handled separately).
	$term_names = get_applied_filter_list( false );

	// Build up the breadcrumbs from scratch.
	$breadcrumbs = array();

	if ( is_page() || is_single() ) {
		$breadcrumbs[] = array(
			'url' => $term_names || isset( $wp_query->query['status'] ) ? get_permalink() : false,
			'title' => get_the_title(),
		);
		if ( $term_names ) {
			$term_names = wp_list_pluck( $term_names, 'name' );
			$breadcrumbs[] = array(
				'url' => false,
				// translators: %s list of terms used for filtering.
				'title' => implode( ', ', $term_names ),
			);
		}
		// For the "My patterns" page, add status.
		if ( isset( $wp_query->query['status'] ) ) {
			$breadcrumbs[] = array(
				'url' => false,
				'title' => get_post_status_object( $wp_query->query['status'] )->label,
			);
		}
		return $breadcrumbs;
	}

	if ( is_search() ) {
		$breadcrumbs[] = array(
			'url' => home_url( '/' ),
			'title' => __( 'All patterns', 'wporg-patterns' ),
		);
		$breadcrumbs[] = array(
			'url' => false,
			'title' => __( 'Search results', 'wporg-patterns' ),
		);
		return $breadcrumbs;
	}

	// `is_home` matches the "posts page", the All Patterns page.
	// `is_archive` matches any core archive (category, date, etc).
	if ( is_home() || is_archive() ) {
		$author = isset( $wp_query->query['author_name'] ) ? get_user_by( 'slug', $wp_query->query['author_name'] ) : false;

		$breadcrumbs[] = array(
			'url' => home_url( '/' ),
			'title' => __( 'All patterns', 'wporg-patterns' ),
		);

		if ( $author ) {
			$breadcrumbs[] = array(
				'url' => get_author_posts_url( $author->ID ),
				'title' => sprintf( __( 'Author: %s', 'wporg-patterns' ), $author->display_name ),
			);
		}

		if ( $term_names ) {
			$term_names = wp_list_pluck( $term_names, 'name' );
			$breadcrumbs[] = array(
				'url' => false,
				// translators: %s list of terms used for filtering.
				'title' => implode( ', ', $term_names ),
			);
		}
	}

	// Last item should be "current", no URL.
	$breadcrumbs[ count( $breadcrumbs ) - 1 ]['url'] = false;

	return $breadcrumbs;
}

/**
 * Update header template based on current query.
 *
 * @param array $parsed_block The block being rendered.
 *
 * @return array The updated block.
 */
function modify_pattern_include( $parsed_block ) {
	if ( 'core/pattern' !== $parsed_block['blockName'] || empty( $parsed_block['attrs']['slug'] ) ) {
		return $parsed_block;
	}

	if (
		'wporg-pattern-directory-2024/single-pattern' === $parsed_block['attrs']['slug'] &&
		get_current_user_id() === get_the_author_meta( 'ID' )
	) {
			$parsed_block['attrs']['slug'] = 'wporg-pattern-directory-2024/single-my-pattern';
	}

	if (
		'wporg-pattern-directory-2024/grid-mine' === $parsed_block['attrs']['slug'] &&
		! get_current_user_id()
	) {
			$parsed_block['attrs']['slug'] = 'wporg-pattern-directory-2024/logged-out-patterns';
	}

	if (
		'wporg-pattern-directory-2024/grid-favorites' === $parsed_block['attrs']['slug'] &&
		! get_current_user_id()
	) {
			$parsed_block['attrs']['slug'] = 'wporg-pattern-directory-2024/logged-out-favorites';
	}

	return $parsed_block;
}
