<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
// @NOTE
//  Please make sure your closing function does not have a comma before it.
//  ie: your final array before your closing function should not have a comma after it.
// Adding a custom post type
cs_post_type('custom-post-type',
    array(
        'singular' => 'Demo Post Type',
        'plural' => 'Demo Post Type',
        'slug' => 'demo-post-type',
    ) // <= No comma after this point or error will trigger
);
// Adding a custom taxonomy to a post type
cs_taxonomy('Categories', 'custom-post-type',
    array(
        'singular' => 'Categories',
        'plural' => 'Category',
        'slug' => 'category',
    ) // <= No comma after this point or error will trigger
);
