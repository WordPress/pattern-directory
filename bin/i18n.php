#!/usr/bin/php
<?php

namespace WordPressdotorg\Pattern_Directory\Bin\I18n;

use Requests;

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

const ENDPOINT_BASE    = 'https://wordpress.org/patterns/wp-json/wp/v2/';
const VALID_TAXONOMIES = array(
	'wporg-pattern-category',
	'wporg-pattern-flag-reason',
);

/**
 * Get data about taxonomies from a REST API endpoint.
 *
 * @return array
 */
function get_taxonomies() {
	$endpoint = ENDPOINT_BASE . 'taxonomies';

	$response = Requests::get( $endpoint );

	if ( 200 !== $response->status_code ) {
		die( 'Could not retrieve taxonomy data.' );
	}

	$taxonomies = json_decode( $response->body, true );

	if ( ! is_array( $taxonomies ) ) {
		die( 'Taxonomies request returned unexpected data.' );
	}

	if ( defined( __NAMESPACE__ . '\VALID_TAXONOMIES' ) ) {
		$taxonomies = array_filter(
			$taxonomies,
			function( $tax ) {
				return in_array( $tax['slug'], VALID_TAXONOMIES, true );
			}
		);
	}

	return $taxonomies;
}

/**
 * Get data about a taxonomy's terms from a REST API endpoint.
 *
 * @param array $taxonomy
 *
 * @return array
 */
function get_taxonomy_terms( $taxonomy ) {
	$endpoint    = ENDPOINT_BASE . $taxonomy['rest_base'] . '?per_page=100';
	$terms       = array();
	$page        = 1;
	$total_pages = 1;

	$response = Requests::get( $endpoint );

	if ( isset( $response->headers['x-wp-totalpages'] ) ) {
		$total_pages = intval( $response->headers['x-wp-totalpages'] );
	}

	while ( $page <= $total_pages ) {
		if ( 'cli' === php_sapi_name() ) {
			echo sprintf(
				'Page %d... ',
				$page // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		if ( 200 !== $response->status_code ) {
			die( sprintf(
				'Could not retrieve terms for %s.',
				$taxonomy['slug'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			) );
		}

		$more_terms = json_decode( $response->body, true );

		if ( ! is_array( $terms ) ) {
			die( sprintf(
				'Terms request for %s returned unexpected data.',
				$taxonomy['slug'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			) );
		}

		$terms = array_merge( $terms, $more_terms );

		$links = array();
		if ( isset( $response->headers['link'] ) ) {
			$links = parse_link_header( $response->headers['link'] );
		}

		if ( ! empty( $links['next'] ) ) {
			$response = Requests::get( $links['next'] );
		}

		$page ++;
	}

	return $terms;
}

/**
 * Get data about pages from a REST API endpoint.
 *
 * @return array
 */
function get_pages() {
	$endpoint = ENDPOINT_BASE . 'pages?per_page=100';

	$response = Requests::get( $endpoint );

	if ( 200 !== $response->status_code ) {
		die( 'Could not retrieve page list.' );
	}

	$pages = json_decode( $response->body, true );

	if ( ! is_array( $pages ) ) {
		die( 'Pages request returned unexpected data.' );
	}

	return $pages;
}

/**
 * Parse a link header from a WP REST API response into an array of prev/next URLs.
 *
 * @param string $link_header
 *
 * @return array Associative array of links, with possible keys of next and prev, values are URLs.
 */
function parse_link_header( $link_header ) {
	$links = explode( ',', $link_header );

	return array_reduce(
		$links,
		function( $carry, $item ) {
			$split = explode( ';', trim( $item ) );
			preg_match( '|<([^<>]+)>|', $split[0], $url );
			preg_match( '|rel="([^"]+)"|', $split[1], $rel );

			if ( ! empty( $url[1] ) && ! empty( $rel[1] ) ) {
				$carry[ $rel[1] ] = filter_var( $url[1], FILTER_VALIDATE_URL );
			}

			return $carry;
		},
		array()
	);
}

/**
 * Run the script.
 */
function main() {
	if ( 'cli' === php_sapi_name() ) {
		echo "\n";
		echo "Retrieving taxonomies...\n";
	}

	$taxonomies = get_taxonomies();

	if ( 'cli' === php_sapi_name() ) {
		echo "Retrieving terms...\n";
	}

	$terms_by_tax = array();
	foreach ( $taxonomies as $taxonomy ) {
		if ( 'cli' === php_sapi_name() ) {
			echo sprintf(
				'%s... ',
				$taxonomy['name'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		$terms = get_taxonomy_terms( $taxonomy );

		if ( 'cli' === php_sapi_name() ) {
			echo "\n";
		}

		if ( count( $terms ) > 0 ) {
			$terms_by_tax[ $taxonomy['name'] ] = $terms;
		}

		unset( $terms );
	}

	if ( 'cli' === php_sapi_name() ) {
		echo "\n";
	}

	$file_content = '';
	foreach ( $terms_by_tax as $tax_label => $terms ) {
		$label = addcslashes( $tax_label, "'" );

		foreach ( $terms as $term ) {
			$name = addcslashes( $term['name'], "'" );
			$file_content .= "_x( '{$name}', '$label term name', 'wporg-patterns' );\n";

			if ( 'cli' === php_sapi_name() ) {
				echo "$name\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( $term['description'] ) {
				$description = addcslashes( $term['description'], "'" );
				$file_content .= "_x( '{$description}', '$label term description', 'wporg-patterns' );\n";
			}
		}
	}

	if ( 'cli' === php_sapi_name() ) {
		echo "\n";
		echo "Retrieving pages...\n";
	}

	foreach ( get_pages() as $page ) {
		$title = addcslashes( $page['title']['rendered'], "'" );
		$file_content .= "_x( '{$title}', 'Page title', 'wporg-patterns' );\n";

		if ( 'cli' === php_sapi_name() ) {
			echo "$title\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	$path = dirname( __DIR__ ) . '/extra';
	if ( ! is_writeable( $path ) ) {
		mkdir( $path );
	}

	$file_name = 'translation-strings.php';
	$file_header = <<<HEADER
<?php
/**
 * Generated file for translation strings.
 *
 * Used to import additional strings into the pattern-directory translation project.
 *
 * ⚠️ This is a generated file. Do not edit manually. See bin/i18n.php.
 * ⚠️ Do not require or include this file anywhere.
 */


HEADER;

	file_put_contents( $path . '/' . $file_name, $file_header . $file_content );

	if ( 'cli' === php_sapi_name() ) {
		echo "\n";
		echo "Done.\n";
	}
}

main();
