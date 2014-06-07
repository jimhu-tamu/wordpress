<?php
/*
this file added to version 0.11 to make OO and to collapse options to a single array

Based on 
	http://codex.wordpress.org/Creating_Options_Pages
and	
	Otto's WordPress Settings API Tutorial
	http://ottopress.com/2009/wordpress-settings-api-tutorial/

revised structure for the options array as a single wp-options property 

	array(
		faclist => array(),
		facprops=> array(
					facname => array(
								query, 
								reflist => string
								extras => string #todo
								last_update => string/timestamp
								
				)
	
	)
	
*/

# calling the function to create the object defers construction until the rest of WP codex has loaded.
add_action('admin_menu', 'pubmed_refs_admin_add_page');
function pubmed_refs_admin_add_page() {
	$wpPubMedRefListSettingsPage = new wpPubMedRefListSettings;
	/*
	Add an item to the Settings menu on the admin page
	
	add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
	menu slug is a url-friendly version of the menu name. Used as the "page name" in other functions.
	*/

	add_options_page(
		'WP PubMed Reflist', 	# page title
		'WP PubMed Reflist', 	# menu title
		'manage_options',    	#capability
		'wp_pubmed_reflist', 	#menu_slug
		array($wpPubMedRefListSettingsPage, 'options_page') # callback function
	);
}



class wpPubMedRefListSettings{

	private $options = array();
	
	function __construct(){
		add_action('admin_init', array($this, 'admin_init'));
	
	}

	/*
	This is the wrapper HTML form displayed when the Settings menu is called
	unlike other places in WP, this seems to just echo the content to the screen, rather than returning
	a string.
	
	The settings_fields function tells WP, what "option-group" we are working with.
	
	do_settings_sections Prints out all settings sections added to a particular settings page.
	*/
	function options_page(){
		global $new_whitelist_options;
		echo "<div>
		<h2>PubMed Reflist</h2>
		Manage PubMed queries for use with the [pmid-refs] shorttag.
		<form action='options.php' method='post'>";
		
		settings_fields('pubmed_refs_options'); 
		do_settings_sections('wp_pubmed_reflist');
		# not sure what the esc_attr_e function does.  Copying from tutorial
		echo "<input name='Submit' type='submit' value='";esc_attr_e('Save Changes'); echo " ' /></form></div>";	
		return;
	}
	
	/*
	Initalization: define form sections and callbacks.
	
	add_settings_section( $id, $title, $callback, $page )
	add_settings_field( $id, $title, $callback, $page, $section, $args )
	
	*/
	
	function admin_init(){
		global $new_whitelist_options;
		$this->options = get_option('wp_pubmed_reflist');
		register_setting( 'pubmed_refs_options', 'wp_pubmed_reflist', array($this, 'pubmed_queries_validate') );
		# main query section
		add_settings_section('wp_pubmed_reflist_qlist', 'Queries', array($this, 'query_form_text'), 'wp_pubmed_reflist');
		add_settings_field('wp_pubmed_reflist_qlist', '', array($this,'query_form'), 'wp_pubmed_reflist', 'wp_pubmed_reflist_qlist');
	
		# add new section
	#	register_setting( 'pubmed_refs_options', 'wp_pubmed_reflist', array($this, 'pubmed_faclist_validate' ));
		add_settings_section('wp_pubmed_reflist_faclist', 'New key', array($this, 'faclist_text'), 'wp_pubmed_reflist');
		add_settings_field('wp_pubmed_reflist_faclist', '', array($this, 'faclist_form'), 'wp_pubmed_reflist', 'wp_pubmed_reflist_faclist');
	}	
	
	function query_form_text() {
		echo '<p>Edit Queries. Queries can be any valid pubmed query, '.
		'or can be a catenation of query keys and query text using || (two pipes) as an OR separator</p>'.
		'<p>Extras is for citations of publications not listed in pubmed. Put formatted citations in this field, one citation per line</p>';
	}
	
	/*
	Generate the forms for query editing
	*/
	function query_form(){
		# get the previous value returns false if empty
		$faclist = $this->options['faclist'];
		$facprops = $this->options['facprops'];
		#echo "<pre>options: "; var_dump($faclist);var_dump($facprops);echo "</pre>"; 	
		echo "<table><tr><th>Search Key</th><th>Query</th><th>Extras</th><th>Delete?</th></tr>";
		if($faclist){
			sort($faclist);
			foreach($faclist as $name){
				if ($name == '' || (isset($facprops[$name]['delete']) && $facprops[$name]['delete'] == 'on')) continue;
				$name = strtolower($name);
				$val = $extras = "";
				echo "<tr><td>$name</td>";

				if(isset($facprops[$name]['query'])) $val = $facprops[$name]['query'];
				echo "<td><textarea id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][query]' 
				cols='60' rows='2' >$val</textarea></td>";

				if(isset($facprops[$name]['extras'])) $extras = $facprops[$name]['extras'];
				echo "<td><textarea id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][extras]' 
				cols='60' rows='2' >$extras</textarea></td>";
				
				echo "<td><input type='checkbox' id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][delete]' /></td>
				<tr>";
				
				echo "<input id='wp_pubmed_reflist_faclist' name='wp_pubmed_reflist[facprops][$name][last_update]' type='hidden' value='0' />";

			}
		}	
		echo "</table>";
	}
	
	function faclist_text() {
		echo '<p>Add new search key.  Search keys are used to tell the shortcode what query '.
		'to use and can be almost any arbitrary unquoted text. We use names of faculty members, '.
		'or mnemonics for searches like "recent"</p>';
	}
	
	function faclist_form(){
		$faclist = array();
		$facprops = $this->options['facprops'];
		if($this->options && isset($this->options['faclist']) ) $faclist = $this->options['faclist'];
		#$faclist = get_option('wp_pubmed_reflist_faclist');
		#echo "<pre>options: "; var_dump($this->options);echo "</pre>"; 	
		echo "<table>";
		$i = 0;
		sort($faclist);
		foreach ($faclist as $name){
			$name = strtolower($name);
			if ($name != "" && !(isset($facprops[$name]['delete']) && $facprops[$name]['delete'] == 'on')){
				#echo "$name<br>";
				echo "<input id='wp_pubmed_reflist_faclist' name='wp_pubmed_reflist[faclist][$i]' type='hidden' value='{$name}' />";
				$i++;
			}
		}		
		echo "<tr><td>Add new:<input id='wp_pubmed_reflist' name='wp_pubmed_reflist[faclist][$i]' size='40' type='text' value='' /></td></tr>";
		echo "</table>";
	
	}
	
	
	/*
	Input validation
	TODO!
	*/
	function pubmed_queries_validate($text){
		return $text;
	}
	function pubmed_faclist_validate($text){
		return $text;
	}

}