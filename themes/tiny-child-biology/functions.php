<?php // Opening PHP tag

// wp_enqueue_scripts
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/css/editor-style-rtl.css' );
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/css/editor-style.css' );
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/css/ie.css' );
}
