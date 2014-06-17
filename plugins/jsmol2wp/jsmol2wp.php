<?php
/*
Plugin Name: jsmol2wp 
Description: Shorttag Plugin to view embed a jsmol viewer in a wordpress page [jsmol pdb='filename or accession' caption='caption' commands ='']. You can use a local file for the .pdb or pull the file from http://www.rcsb.org/pdb/files/XXXX.pdb. For more info see the help link under the applets.
Version: 0.9 beta
Author: JimHu
Author URI: http://ecoliwiki.net
License: GPL2
*/

if ( ! defined( 'JSMOL2WP_PLUGIN_DIR' ) )
	define( 'JSMOL2WP_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );

# include the object code
include_once(JSMOL2WP_PLUGIN_DIR."/class.jsMol2wp.php");

/**
* Short tag function
* params key, limit 
*/

//add shortcodes
function jsmol2wp_shortcode($atts) {
	extract(shortcode_atts(array(
	'acc'       => '',
	'type'      => 'pdb',
	'pdb'       => '',
	'load'		=> '',
	'fileurl'   => '',
	'caption'	=> '',
	'commands'	=> '',
	'id'      => '', # if you want two copies in the same post with the same acc and caption
	'wrap'      => '4',
	'debug'     => 'false'
	), $atts));
	#backward compatibility
	if($acc == '' && $pdb != ''){
		$acc = $pdb;
		$type = 'pdb';
	}
	$p = new jsMol2wp($acc, $type, $caption, $id, $fileurl);
	return $p->makeViewer($acc, $type, $load, $caption, $commands, $wrap, $debug);
}

add_shortcode( 'jsmol', 'jsmol2wp_shortcode');

function jsmol_mime_types($mime_types){
    //Adjust the $mime_types, which is an associative array where the key is extension and value is mime type.
    $mime_types['pdb'] = 'chemical/x-pdb';
    $mime_types['cif'] = 'chemical/x-cif';
    $mime_types['cml'] = 'chemical/x-cml+xml';
    $mime_types['jvxi'] = 'chemical/x-jvxi';
    $mime_types['mol'] = 'chemical/x-mdl-molfile';
    $mime_types['mol2'] = 'chemical/x-mol2';
    $mime_types['xyz'] = 'chemical/x-xyz';
    $mime_types['ccp4'] = 'text/ccp4';
    return $mime_types;
}
add_filter('upload_mimes', 'jsmol_mime_types', 1, 1);

function enqueue_jsmol_scripts() {
	wp_register_script(
		'jsmol.min.nojq', 
		plugins_url()."/jsmol2wp/JSmol.min.nojq.js",
		array( 'jquery', 'jquery-ui-core','jquery-ui-menu'  ),
		'14.1.7_2014.06.09'
	);
	wp_enqueue_script('jsmol.min.nojq');
	
}
add_action( 'wp_enqueue_scripts', 'enqueue_jsmol_scripts' );
