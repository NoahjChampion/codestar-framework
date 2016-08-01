<?php
/**
 * Extended custom taxonomies for WordPress.
 *
 * @package   ExtendedTaxos
 * @version   2.0.3
 * @author    John Blackbourn <https://johnblackbourn.com>
 * @link      https://github.com/johnbillion/extended-taxos
 * @copyright 2012-2016 John Blackbourn
 * @license   GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

if ( ! function_exists( 'cs_taxonomy' ) ) {
/**
 * Register an extended custom taxonomy.
 *
 * The `$args` parameter accepts all the standard arguments for `register_taxonomy()` in addition to several custom
 * arguments that provide extended functionality. Some of the default arguments differ from the defaults in
 * `register_taxonomy()`.
 *
 * The `$taxonomy` parameter is used as the taxonomy name and to build the taxonomy labels. This means you can create
 * a taxonomy with just two parameters and all labels and term updated messages will be generated for you. Example:
 *
 *     cs_taxonomy( 'location', 'post' );
 *
 * The singular name, plural name, and slug are generated from the taxonomy name. These can be overridden with the
 * `$names` parameter if necessary. Example:
 *
 *     cs_taxonomy( 'story', 'post' array(), array(
 *         'plural' => 'Stories',
 *         'slug'   => 'tales'
 *     ) );
 *
 * @see register_taxonomy() for default arguments.
 *
 * @param string       $taxonomy    The taxonomy name.
 * @param array|string $object_type Name(s) of the object type(s) for the taxonomy.
 * @param array  $args {
 *     Optional. The taxonomy arguments.
 * 
 *     @type bool   $dashboard_glance Whether to show this taxonomy on the 'At a Glance' section of the admin dashboard.
 *                                    Default false.
 *     @type array  $admin_cols       Associative array of admin screen columns to show for this taxonomy. See the
 *                                    `Extended_Taxonomy_Admin::cols()` method for more information.
 *     @type bool   $exclusive        This parameter isn't feature complete. All it does currently is set the meta box
 *                                    to the 'radio' meta box, thus meaning any given post can only have one term
 *                                    associated with it for that taxonomy. 'exclusive' isn't really the right name for
 *                                    this, as terms aren't exclusive to a post, but rather each post can exclusively
 *                                    have only one term. It's not feature complete because you can edit a post in
 *                                    Quick Edit and give it more than one term from the taxonomy.
 *     @type bool   $allow_hierarchy  All this does currently is disable hierarchy in the taxonomy's rewrite rules.
 *                                    Default false.
 * }
 * @param array  $names {
 *     Optional. The plural, singular, and slug names.
 *
 *     @type string $plural   The plural form of the taxonomy name.
 *     @type string $singular The singular form of the taxonomy name.
 *     @type string $slug     The slug used in the term permalinks for this taxonomy.
 * }
 */
function cs_taxonomy( $taxonomy, $object_type, array $args = array(), array $names = array() ) {

	$taxo = new Extended_Taxonomy( $taxonomy, $object_type, $args, $names );

	if ( is_admin() ) {
		new Extended_Taxonomy_Admin( $taxo, $args );
	}

	return $taxo;

}
}

if ( ! class_exists( 'Extended_Taxonomy' ) ) {
class Extended_Taxonomy {

	/**
	 * Default arguments for custom taxonomies.
	 * Several of these differ from the defaults in WordPress' register_taxonomy() function.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'public'            => true,
		'show_ui'           => true,
		'hierarchical'      => true,
		'query_var'         => true,
		'exclusive'         => false, # Custom arg
		'allow_hierarchy'   => false, # Custom arg
	);

	/**
	 * Some other member variables you don't need to worry about:
	 */
	public $taxonomy;
	public $object_type;
	public $tax_slug;
	public $tax_singular;
	public $tax_plural;
	public $tax_singular_low;
	public $tax_plural_low;
	public $args;

	/**
	 * Class constructor.
	 *
	 * @see cs_taxonomy()
	 *
	 * @param string       $taxonomy    The taxonomy name.
	 * @param array|string $object_type Name(s) of the object type(s) for the taxonomy.
	 * @param array        $args        Optional. The taxonomy arguments.
	 * @param array        $names       Optional. An associative array of the plural, singular, and slug names.
	 */
	public function __construct( $taxonomy, $object_type, array $args = array(), array $names = array() ) {

		/**
		 * Filter the arguments for this taxonomy.
		 *
		 * @since 2.0.0
		 *
		 * @param array $args The taxonomy arguments.
		 */
		$args  = apply_filters( "ext-taxos/{$taxonomy}/args", $args );
		/**
		 * Filter the names for this taxonomy.
		 *
		 * @since 2.0.0
		 *
		 * @param array $names The plural, singular, and slug names (if any were specified).
		 */
		$names = apply_filters( "ext-taxos/{$taxonomy}/names", $names );

		if ( isset( $names['singular'] ) ) {
			$this->tax_singular = $names['singular'];
		} else {
			$this->tax_singular = ucwords( str_replace( array( '-', '_' ), ' ', $taxonomy ) );
		}

		if ( isset( $names['slug'] ) ) {
			$this->tax_slug = $names['slug'];
		} elseif ( isset( $names['plural'] ) ) {
			$this->tax_slug = $names['plural'];
		} else {
			$this->tax_slug = $taxonomy . 's';
		}

		if ( isset( $names['plural'] ) ) {
			$this->tax_plural = $names['plural'];
		} else {
			$this->tax_plural = ucwords( str_replace( array( '-', '_' ), ' ', $this->tax_slug ) );
		}

		$this->object_type = (array) $object_type;
		$this->taxonomy    = strtolower( $taxonomy );
		$this->tax_slug    = strtolower( $this->tax_slug );

		# Build our base taxonomy names:
		$this->tax_singular_low = strtolower( $this->tax_singular );
		$this->tax_plural_low   = strtolower( $this->tax_plural );

		# Build our labels:
		$this->defaults['labels'] = array(
			'menu_name'                  => $this->tax_plural,
			'name'                       => $this->tax_plural,
			'singular_name'              => $this->tax_singular,
			'search_items'               => sprintf( 'Search %s', $this->tax_plural ),
			'popular_items'              => sprintf( 'Popular %s', $this->tax_plural ),
			'all_items'                  => sprintf( 'All %s', $this->tax_plural ),
			'parent_item'                => sprintf( 'Parent %s', $this->tax_singular ),
			'parent_item_colon'          => sprintf( 'Parent %s:', $this->tax_singular ),
			'edit_item'                  => sprintf( 'Edit %s', $this->tax_singular ),
			'view_item'                  => sprintf( 'View %s', $this->tax_singular ),
			'update_item'                => sprintf( 'Update %s', $this->tax_singular ),
			'add_new_item'               => sprintf( 'Add New %s', $this->tax_singular ),
			'new_item_name'              => sprintf( 'New %s Name', $this->tax_singular ),
			'separate_items_with_commas' => sprintf( 'Separate %s with commas', $this->tax_plural_low ),
			'add_or_remove_items'        => sprintf( 'Add or remove %s', $this->tax_plural_low ),
			'choose_from_most_used'      => sprintf( 'Choose from most used %s', $this->tax_plural_low ),
			'not_found'                  => sprintf( 'No %s found', $this->tax_plural_low ),
			'no_terms'                   => sprintf( 'No %s', $this->tax_plural_low ),
			'items_list_navigation'      => sprintf( '%s list navigation', $this->tax_plural ),
			'items_list'                 => sprintf( '%s list', $this->tax_plural ),
			'no_item'                    => sprintf( 'No %s', $this->tax_singular_low ), # Custom label
		);

		# Only set rewrites if we need them
		if ( isset( $args['public'] ) && ! $args['public'] ) {
			$this->defaults['rewrite'] = false;
		} else {
			$this->defaults['rewrite'] = array(
				'slug'         => $this->tax_slug,
				'with_front'   => false,
				'hierarchical' => isset( $args['allow_hierarchy'] ) ? $args['allow_hierarchy'] : $this->defaults['allow_hierarchy'],
			);
		}

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# This allows the 'labels' arg to contain some, none or all labels:
		if ( isset( $args['labels'] ) ) {
			$this->args['labels'] = array_merge( $this->defaults['labels'], $args['labels'] );
		}

		# Rewrite testing:
		if ( $this->args['rewrite'] ) {
			add_filter( 'rewrite_testing_tests', array( $this, 'rewrite_testing_tests' ), 1 );
		}

		# Register taxonomy when WordPress initialises:
		if ( 'init' === current_filter() ) {
			call_user_func( array( $this, 'register_taxonomy' ) );
		} else {
			add_action( 'init', array( $this, 'register_taxonomy' ), 9 );
		}

	}

	/**
	 * Add our rewrite tests to the Rewrite Rule Testing tests array.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param  array $tests The existing rewrite rule tests.
	 * @return array        Updated rewrite rule tests.
	 */
	public function rewrite_testing_tests( array $tests ) {

		$extended = new Extended_Taxonomy_Rewrite_Testing( $this );

		return array_merge( $tests, $extended->get_tests() );

	}

	/**
	 * Registers our taxonomy.
	 *
	 * @return null
	 */
	public function register_taxonomy() {

		if ( true === $this->args['query_var'] ) {
			$query_var = $this->taxonomy;
		} else {
			$query_var = $this->args['query_var'];
		}

		if ( $query_var && count( get_post_types( array( 'query_var' => $query_var ) ) ) ) {
			trigger_error( esc_html( sprintf(
				__( 'Taxonomy query var "%s" clashes with a post type query var of the same name', 'ext_taxos' ),
				$query_var
			) ), E_USER_ERROR );
		} elseif ( in_array( $query_var, array( 'type', 'tab' ) ) ) {
			trigger_error( esc_html( sprintf(
				__( 'Taxonomy query var "%s" is not allowed', 'ext_taxos' ),
				$query_var
			) ), E_USER_ERROR );
		} else {
			register_taxonomy( $this->taxonomy, $this->object_type, $this->args );
		}

	}

}
}

if ( ! class_exists( 'Extended_Taxonomy_Admin' ) ) {
class Extended_Taxonomy_Admin {

	/**
	 * Default arguments for custom taxonomies.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'dashboard_glance'  => false, # Custom arg
		'admin_cols'        => null,  # Custom arg
	);

	public $taxo;
	public $args;
	protected $_cols;
	protected $the_cols = null;

	/**
	* Class constructor.
	*
	* @param Extended_Taxonomy $taxo An extended taxonomy object
	* @param array             $args The admin arguments
	*/
	public function __construct( Extended_Taxonomy $taxo, array $args = array() ) {

		$this->taxo = $taxo;

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# 'At a Glance' dashboard panels:
		if ( $this->args['dashboard_glance'] ) {
			add_filter( 'dashboard_glance_items', array( $this, 'glance_items' ) );
		}

		# Term updated messages:
		add_filter( 'term_updated_messages', array( $this, 'term_updated_messages' ), 1, 2 );

		# Admin columns:
		if ( $this->args['admin_cols'] ) {
			add_filter( "manage_edit-{$this->taxo->taxonomy}_columns",  array( $this, '_log_default_cols' ), 0 );
			add_filter( "manage_edit-{$this->taxo->taxonomy}_columns",  array( $this, 'cols' ) );
			add_action( "manage_{$this->taxo->taxonomy}_custom_column", array( $this, 'col' ), 10, 3 );
		}

	}

	/**
	 * Logs the default columns so we don't remove any custom columns added by other plugins.
	 *
	 * @param  array $cols The default columns for this taxonomy screen
	 * @return array       The default columns for this taxonomy screen
	 */
	public function _log_default_cols( array $cols ) {

		return $this->_cols = $cols;

	}

	/**
	 * Add columns to the admin screen for this taxonomy.
	 *
	 * Each item in the `admin_cols` array is either a string name of an existing column, or an associative
	 * array of information for a custom column.
	 *
	 * Defining a custom column is easy. Just define an array which includes the column title, column
	 * type, and optional callback function. You can display columns for term meta or custom functions.
	 *
	 * The example below adds two columns; one which displays the value of the term's `term_updated` meta
	 * key, and one which calls a custom callback function:
	 *
	 *     cs_taxonomy( 'foo', 'bar', array(
	 *         'admin_cols' => array(
	 *             'foo_updated' => array(
	 *                 'title'    => 'Updated',
	 *                 'meta_key' => 'term_updated'
	 *             ),
	 *             'foo_bar' => array(
	 *                 'title'    => 'Example',
	 *                 'function' => 'my_custom_callback'
	 *             )
	 *         )
	 *     ) );
	 *
	 * That's all you need to do. The columns will handle safely outputting the data
	 * (escaping text, and comma-separating taxonomy terms). No more messing about with all of those
	 * annoyingly named column filters and actions.
	 *
	 * Each item in the `admin_cols` array must contain one of the following elements which defines the column type:
	 *
	 *  - meta_key - A term meta key
	 *  - function - The name of a callback function
	 *
	 * The value for the corresponding term meta are safely escaped and output into the column.
	 *
	 * There are a few optional elements:
	 *
	 *  - title - Generated from the field if not specified.
	 *  - function - The name of a callback function for the column (eg. `my_function`) which gets called
	 *    instead of the built-in function for handling that column. The function is passed the term ID as
	 *    its first parameter.
	 *  - date_format - This is used with the `meta_key` column type. The value of the meta field will be
	 *    treated as a timestamp if this is present. Unix and MySQL format timestamps are supported in the
	 *    meta value. Pass in boolean true to format the date according to the 'Date Format' setting, or pass
	 *    in a valid date formatting string (eg. `d/m/Y H:i:s`).
	 *  - cap - A capability required in order for this column to be displayed to the current user. Defaults
	 *    to null, meaning the column is shown to all users.
	 *
	 * Note that sortable admin columns are not yet supported.
	 *
	 * @param  array $cols Associative array of columns
	 * @return array       Updated array of columns
	 */
	public function cols( array $cols ) {

		// This function gets called multiple times, so let's cache it for efficiency:
		if ( isset( $this->the_cols ) ) {
			return $this->the_cols;
		}

		$new_cols = array();
		$keep = array(
			'cb',
			'name',
			'description',
			'slug',
		);

		# Add existing columns we want to keep:
		foreach ( $cols as $id => $title ) {
			if ( in_array( $id, $keep ) && ! isset( $this->args['admin_cols'][ $id ] ) ) {
				$new_cols[ $id ] = $title;
			}
		}

		# Add our custom columns:
		foreach ( array_filter( $this->args['admin_cols'] ) as $id => $col ) {
			if ( is_string( $col ) && isset( $cols[ $col ] ) ) {
				# Existing (ie. built-in) column with id as the value
				$new_cols[ $col ] = $cols[ $col ];
			} else if ( is_string( $col ) && isset( $cols[ $id ] ) ) {
				# Existing (ie. built-in) column with id as the key and title as the value
				$new_cols[ $id ] = esc_html( $col );
			} else if ( is_array( $col ) ) {
				if ( isset( $col['cap'] ) && ! current_user_can( $col['cap'] ) ) {
					continue;
				}
				if ( ! isset( $col['title'] ) ) {
					$col['title'] = $this->get_item_title( $col );
				}
				$new_cols[ $id ] = esc_html( $col['title'] );
			}
		}

		# Re-add any custom columns:
		$custom   = array_diff_key( $cols, $this->_cols );
		$new_cols = array_merge( $new_cols, $custom );

		return $this->the_cols = $new_cols;

	}

	/**
	 * Output the column data for our custom columns.
	 *
	 * @param string $string      Blank string.
	 * @param string $column_name Name of the column.
	 * @param int    $term_id     Term ID.
	 */
	public function col( $string, $col, $term_id ) {

		# Shorthand:
		$c = $this->args['admin_cols'];

		# We're only interested in our custom columns:
		$custom_cols = array_filter( array_keys( $c ) );

		if ( ! in_array( $col, $custom_cols ) ) {
			return;
		}

		if ( isset( $c[ $col ]['function'] ) ) {
			call_user_func( $c[ $col ]['function'], $term_id );
		} else if ( isset( $c[ $col ]['meta_key'] ) ) {
			$this->col_term_meta( $c[ $col ]['meta_key'], $c[ $col ], $term_id );
		}

	}

	/**
	 * Output column data for a term meta field.
	 *
	 * @param string $meta_key The term meta key
	 * @param array  $args     Optional. Array of arguments for this field
	 * @param int    $term_id  Term ID.
	 */
	public function col_term_meta( $meta_key, array $args, $term_id ) {

		$vals = get_term_meta( $term_id, $meta_key, false );
		$echo = array();
		sort( $vals );

		if ( isset( $args['date_format'] ) ) {

			if ( true === $args['date_format'] ) {
				$args['date_format'] = get_option( 'date_format' );
			}

			foreach ( $vals as $val ) {

				if ( is_numeric( $val ) ) {
					$echo[] = date( $args['date_format'], $val );
				} else if ( ! empty( $val ) ) {
					$echo[] = mysql2date( $args['date_format'], $val );
				}
			}
		} else {

			foreach ( $vals as $val ) {

				if ( ! empty( $val ) || ( '0' === $val ) ) {
					$echo[] = $val;
				}
			}
		}

		if ( empty( $echo ) ) {
			echo '&#8212;';
		} else {
			echo esc_html( implode( ', ', $echo ) );
		}

	}

	/**
	 * Get a sensible title for the current item (usually the arguments array for a column)
	 *
	 * @param  array  $item An array of arguments
	 * @return string       The item title
	 */
	protected function get_item_title( array $item ) {

		if ( isset( $item['meta_key'] ) ) {
			return ucwords( trim( str_replace( array( '_', '-' ), ' ', $item['meta_key'] ) ) );
		} else {
			return '';
		}

	}

	/**
	 * Add our taxonomy to the 'At a Glance' widget on the WordPress 3.8+ dashboard.
	 *
	 * @param  array $items Array of items to display on the widget.
	 * @return array        Updated array of items.
	 */
	public function glance_items( array $items ) {

		$taxonomy = get_taxonomy( $this->taxo->taxonomy );

		if ( ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return $items;
		}
		if ( $taxonomy->_builtin ) {
			return $items;
		}

		# Get the labels and format the counts:
		$count = wp_count_terms( $this->taxo->taxonomy );
		$text  = self::n( $taxonomy->labels->singular_name, $taxonomy->labels->name, $count );
		$num   = number_format_i18n( $count );

		# This is absolutely not localisable. WordPress 3.8 didn't add a new taxonomy label.
		$url = add_query_arg( [
			'taxonomy'  => $this->taxo->taxonomy,
			'post_type' => reset( $taxonomy->object_type ),
		], admin_url( 'edit-tags.php' ) );
		$text = '<a href="' . esc_url( $url ) . '">' . esc_html( $num . ' ' . $text ) . '</a>';

		# Go!
		$items[] = $text;

		return $items;

	}

	/**
	 * Add our term updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Term added."
	 *   2 => "Term deleted."
	 *   3 => "Term updated."
	 *   4 => "Term not added."
	 *   5 => "Term not updated."
	 *   6 => "Terms deleted."
	 *
	 * @param array $messages An associative array of term updated messages with taxonomy name as keys.
	 * @return array Updated array of term updated messages.
	 */
	public function term_updated_messages( array $messages ) {

		$messages[ $this->taxo->taxonomy ] = array(
			1 => esc_html( sprintf( '%s added.', $this->taxo->tax_singular ) ),
			2 => esc_html( sprintf( '%s deleted.', $this->taxo->tax_singular ) ),
			3 => esc_html( sprintf( '%s updated.', $this->taxo->tax_singular ) ),
			4 => esc_html( sprintf( '%s not added.', $this->taxo->tax_singular ) ),
			5 => esc_html( sprintf( '%s not updated.', $this->taxo->tax_singular ) ),
			6 => esc_html( sprintf( '%s deleted.', $this->taxo->tax_plural ) ),
		);

		return $messages;

	}

	/**
	 * A non-localised version of _n()
	 *
	 * @param string $single The text that will be used if $number is 1
	 * @param string $plural The text that will be used if $number is not 1
	 * @param int $number The number to compare against to use either $single or $plural
	 * @return string Either $single or $plural text
	 */
	public static function n( $single, $plural, $number ) {

		return ( 1 === intval( $number ) ) ? $single : $plural;

	}

}
}

/**
 * Walker to output an unordered list of category checkbox <input> elements properly.
 *
 * @uses Walker
 */
if ( ! class_exists( 'Walker_ExtendedTaxonomyCheckboxes' ) ) {
class Walker_ExtendedTaxonomyCheckboxes extends Walker {

	/**
	 * Some member variables you don't need to worry too much about:
	 */
	public $tree_type = 'category';
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);
	public $field = null;

	/**
	 * Class constructor.
	 *
	 * @param array $args Optional arguments.
	 */
	public function __construct( $args = null ) {
		if ( $args && isset( $args['field'] ) ) {
			$this->field = $args['field'];
		}
	}

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent<ul class='children'>\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	/**
	 * Start the element output.
	 *
	 * @param string $output            Passed by reference. Used to append additional content.
	 * @param object $object            Term data object.
	 * @param int    $depth             Depth of term in reference to parents.
	 * @param array  $args              Optional arguments.
	 * @param int    $current_object_id Current object ID.
	 */
	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		$tax = get_taxonomy( $args['taxonomy'] );

		if ( $this->field ) {
			$value = $object->{$this->field};
		} else {
			$value = $tax->hierarchical ? $object->term_id : $object->name;
		}

		if ( empty( $object->term_id ) && ! $tax->hierarchical ) {
			$value = '';
		}

		$output .= "\n<li id='{$args['taxonomy']}-{$object->term_id}'>" .
			'<label class="selectit">' .
			'<input value="' . esc_attr( $value ) . '" type="checkbox" name="tax_input[' . esc_attr( $args['taxonomy'] ) . '][]" ' .
				'id="in-' . esc_attr( $args['taxonomy'] ) . '-' . intval( $object->term_id ) . '"' .
				checked( in_array( $object->term_id, (array) $args['selected_cats'] ), true, false ) .
				disabled( empty( $args['disabled'] ), false, false ) .
			' /> ' .
			esc_html( apply_filters( 'the_category', $object->name ) ) .
			'</label>';

	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $object Term data object.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array $args Optional arguments.
	 */
	public function end_el( &$output, $object, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

}
}

/**
 * A term walker class for radio buttons.
 *
 * @uses Walker
 */
if ( ! class_exists( 'Walker_ExtendedTaxonomyRadios' ) ) {
class Walker_ExtendedTaxonomyRadios extends Walker {

	/**
	 * Some member variables you don't need to worry too much about:
	 */
	public $tree_type = 'category';
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);
	public $field = null;

	/**
	 * Class constructor.
	 *
	 * @param array $args Optional arguments.
	 */
	public function __construct( $args = null ) {
		if ( $args && isset( $args['field'] ) ) {
			$this->field = $args['field'];
		}
	}

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "{$indent}<ul class='children'>\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "{$indent}</ul>\n";
	}

	/**
	 * Start the element output.
	 *
	 * @param string $output            Passed by reference. Used to append additional content.
	 * @param object $object            Term data object.
	 * @param int    $depth             Depth of term in reference to parents.
	 * @param array  $args              Optional arguments.
	 * @param int    $current_object_id Current object ID.
	 */
	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		$tax = get_taxonomy( $args['taxonomy'] );

		if ( $this->field ) {
			$value = $object->{$this->field};
		} else {
			$value = $tax->hierarchical ? $object->term_id : $object->name;
		}

		if ( empty( $object->term_id ) && ! $tax->hierarchical ) {
			$value = '';
		}

		$output .= "\n<li id='{$args['taxonomy']}-{$object->term_id}'>" .
			'<label class="selectit">' .
			'<input value="' . esc_attr( $value ) . '" type="radio" name="tax_input[' . esc_attr( $args['taxonomy'] ) . '][]" ' .
				'id="in-' . esc_attr( $args['taxonomy'] ) . '-' . esc_attr( $object->term_id ) . '"' .
				checked( in_array( $object->term_id, (array) $args['selected_cats'] ), true, false ) .
				disabled( empty( $args['disabled'] ), false, false ) .
			' /> ' .
			esc_html( apply_filters( 'the_category', $object->name ) ) .
			'</label>';

	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $object Term data object.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function end_el( &$output, $object, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

}
}

/**
 * A term walker class for a dropdown menu.
 *
 * @uses Walker
 */
if ( ! class_exists( 'Walker_ExtendedTaxonomyDropdown' ) ) {
class Walker_ExtendedTaxonomyDropdown extends Walker {

	/**
	 * Some member variables you don't need to worry too much about:
	 */
	public $tree_type = 'category';
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);
	public $field = null;

	/**
	 * Class constructor.
	 *
	 * @param array $args Optional arguments.
	 */
	public function __construct( $args = null ) {
		if ( $args && isset( $args['field'] ) ) {
			$this->field = $args['field'];
		}
	}

	/**
	 * Start the element output.
	 *
	 * @param string $output            Passed by reference. Used to append additional content.
	 * @param object $object            Term data object.
	 * @param int    $depth             Depth of term in reference to parents.
	 * @param array  $args              Optional arguments.
	 * @param int    $current_object_id Current object ID.
	 */
	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		$pad = str_repeat( '&nbsp;', $depth * 3 );
		$tax = get_taxonomy( $args['taxonomy'] );

		if ( $this->field ) {
			$value = $object->{$this->field};
		} else {
			$value = $tax->hierarchical ? $object->term_id : $object->name;
		}

		if ( empty( $object->term_id ) && ! $tax->hierarchical ) {
			$value = '';
		}

		$cat_name = apply_filters( 'list_cats', $object->name, $object );
		$output .= "\t<option class=\"level-{$depth}\" value=\"" . esc_attr( $value ) . '"';

		if ( isset( $args['selected_cats'] ) && in_array( $value, (array) $args['selected_cats'] ) ) {
			$output .= ' selected="selected"';
		} elseif ( isset( $args['selected'] ) && in_array( $object->term_id, (array) $args['selected'] ) ) {
			$output .= ' selected="selected"';
		}

		$output .= '>';
		$output .= $pad . esc_html( $cat_name );
		if ( $args['show_count'] ) {
			$output .= '&nbsp;&nbsp;(' . esc_html( number_format_i18n( $object->count ) ) . ')';
		}
		$output .= "</option>\n";
	}

}
}

if ( ! class_exists( 'Extended_Taxonomy_Rewrite_Testing' ) && class_exists( 'Extended_Rewrite_Testing' ) ) {
/**
 * @codeCoverageIgnore
 */
class Extended_Taxonomy_Rewrite_Testing extends Extended_Rewrite_Testing {

	public $taxo;

	public function __construct( Extended_Taxonomy $taxo ) {
		$this->taxo = $taxo;
	}

	public function get_tests() {

		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return array();
		}

		if ( ! isset( $wp_rewrite->extra_permastructs[ $this->taxo->taxonomy ] ) ) {
			return array();
		}

		$struct     = $wp_rewrite->extra_permastructs[ $this->taxo->taxonomy ];
		$tax        = get_taxonomy( $this->taxo->taxonomy );
		$name       = sprintf( '%s (%s)', $tax->labels->name, $this->taxo->taxonomy );

		return array(
			$name => $this->get_rewrites( $struct, array() ),
		);

	}

}
}
