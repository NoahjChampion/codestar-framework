<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
// @NOTE
//  Please make sure your closing function does not have a comma after it.
//  ie: the array before your closing function should not have a comma.
// Adding a custom post type
cs_post_type('custom-post-type',
    array(
        'singular' => 'Custom Post Type',
        'plural' => 'Custom Post Type',
        'slug' => 'custom-post-type',
    )
);
// Adding a custom taxonomy to a post type
cs_taxonomy('Categories', 'custom-post-type',
    array(
        'singular' => 'Categories',
        'plural' => 'Category',
        'slug' => 'category',
    )
);