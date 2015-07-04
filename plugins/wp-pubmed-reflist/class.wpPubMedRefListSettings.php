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
	pre 0.7 Add an item to the Settings menu on the admin page
	
	add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
	menu slug is a url-friendly version of the menu name. Used as the "page name" in other functions.
	
	post 0.7 Add an item to the Tools menu so Editors can see it
	add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	
	note that the wpPubMedRefListSettings constructor also modifies permissions to allow Editors to save.
	*/

	add_management_page(
		'WP PubMed Reflist', 	# page title
		'WP PubMed Reflist', 	# menu title
		'edit_posts',       	#capability
		'wp_pubmed_reflist', 	#menu_slug
		array($wpPubMedRefListSettingsPage, 'options_page') # callback function
	);
}

class wpPubMedRefListSettings{

	private $options = array();
	
	function __construct(){
		# set callback for initializing admin pages
		add_action('admin_init', array($this, 'admin_init'));
		# Allow Editor Role to edit settings
		update_user_option( get_current_user_id(), 'manage_options', true, false );	
	}

	/*
	Initalization: define form sections and callbacks.
	
	add_settings_section( $id, $title, $callback, $page )
	add_settings_field( $id, $title, $callback, $page, $section, $args )
	
	*/
	
	function admin_init(){
		global $new_whitelist_options;
		$this->options = get_option('wp_pubmed_reflist');
		$this->formats = get_option('wp_pubmed_reflist_styles');
		# set default style to NIH if it isn't set
		if(!isset($this->formats['default_style'])) $this->formats['default_style'] = 'NIH';

		/*
		Registry for query managment tab
		*/
		register_setting( 
			'pubmed_refs_options',                   # option group to be called by settings_fields() in form generation
			'wp_pubmed_reflist',                     # option name
			array($this, 'pubmed_queries_validate')  # sanitize callback
			);
		# add new section for adding keys
		add_settings_section(
			'wp_pubmed_reflist_faclist',          	
			'New key', 
			'wpPubMedReflistViews::faclist_text', 
			'wp_pubmed_reflist'
			);
		add_settings_field(
			'wp_pubmed_reflist_faclist', 
			'', array($this, 'faclist_form'), 
			'wp_pubmed_reflist', 
			'wp_pubmed_reflist_faclist'
			);
		# edit queries section
		add_settings_section(
			'wp_pubmed_reflist_qlist',      	# id
			'Queries',                       	# title
			'wpPubMedReflistViews::query_form_text',	# callback 
			'wp_pubmed_reflist'               	# page
			);
		add_settings_field(
			'wp_pubmed_reflist_qlist',        	# id
			'',                              	# title
			array($this,'query_form'),        	# callback
			'wp_pubmed_reflist',             	# page
			'wp_pubmed_reflist_qlist'         	# section
			);
	
		/*
		Registry for styles managment tab
		*/
	
		register_setting( 
			'pubmed_ref_styles',                    	# option group
			'wp_pubmed_reflist_styles',                	# option name
			array($this, 'pubmed_formats_validate')    	# sanitize callback
			);
		# edit formats
		add_settings_section(
			'wp_pubmed_styles',      	# id
			'Add style',                       	# title
			'wpPubMedReflistViews::styles_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_reflist_faclist',      	# id
			'',                              	# title
			array($this, 'styles_form'),      	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_styles'              	# section	 
			);
		add_settings_section(
			'wp_pubmed_style_data_section',      	# id
			'Style data',                       	# title
			'wpPubMedReflistViews::styles_data_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_style_data',         	# id
			'',                              	# title
			array($this,'style_props_form'),   	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_style_data_section'              	# section
			);
		add_settings_section(
			'wp_pubmed_itals',      	# id
			'Italicized words',                       	# title
			'wpPubMedReflistViews::styles_ital_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_style_italicize',         	# id
			'',                              	# title
			array($this,'styles_ital_form'),   	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_itals'              	# section
			);
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
		echo "<h2>PubMed Reflist</h2>
		Manage PubMed queries for use with the [pmid-refs key=<key> limit=<number>] shorttag.";
		echo 
"<div class='wrap'>";
		/*
		Tabbed management
		modified from 		http://code.tutsplus.com/tutorials/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page--wp-24971
		*/
		$active_tab = 'query';
		if( isset( $_GET[ 'tab' ] ) ) {
    		$active_tab = $_GET[ 'tab' ];
		} // end if
		$query_tab_class = $format_tab_class = $help_tab_class = 'nav-tab';
		$active_tab_class = $active_tab.'_tab_class';
		$$active_tab_class = 'nav-tab-active';	
		echo "
		<h2 class='nav-tab-wrapper'>
			<a href='?page=wp_pubmed_reflist&tab=query' class='$query_tab_class'>Queries</a>
			<a href='?page=wp_pubmed_reflist&tab=format' class='$format_tab_class'>Reference Styles</a>
			<a href='?page=wp_pubmed_reflist&tab=help' class='$help_tab_class'>Help</a>
		</h2>";
		echo "<form action='options.php' method='post'>";
		switch($active_tab){
			case 'help':
				wpPubmedReflistViews::help();
				break;
			case 'format':
				settings_fields('pubmed_ref_styles'); 
				do_settings_sections('wp_pubmed_reflist_styles');
				submit_button('Save Changes','submit', true, array('tab'=>'format'));
				break;
			case 'query':
			default:
		settings_fields('pubmed_refs_options'); 
		do_settings_sections('wp_pubmed_reflist');
				submit_button('Save Changes','submit', true, array('tab'=>'query'));
	}
		echo "</form></div>";
	
		return;
	}	
	
	
	
	/*
	Generate the forms for query editing
	*/
	function query_form(){
		# get the previous value returns false if empty
		$faclist = @$this->options['faclist'];
		$facprops = @$this->options['facprops'];
		#echo "<pre>options: \nfaclist\n"; var_dump($faclist);echo "facprops\n";var_dump($facprops);echo "</pre>"; 	
		echo "<table><tr><th>Search Key</th><th>Query</th><th>Extras</th><th>Delete?</th></tr>";
		if($faclist){
			sort($faclist);
			foreach($faclist as $name){
				# sanitize the faclist name
				$sanitizedName = preg_replace('/[^a-zA-Z0-9\s-_\(\)]/', '', $name);
				if($sanitizedName != $name){
					echo "$name contains characters not allowed in the key";
					# delete it if it is already in the db.
					if(($key = array_search($name, $this->options['faclist'])) !== false) {
						unset($this->options['faclist'][$key]);
					}
					if(isset($facprops[$name]['query'])){
						$facprops[$sanitizedName]['query'] = $facprops[$name]['query'];
						unset ($facprops[$name]['query']);
					}							
					continue;
				}
				if ($name == '' || (isset($facprops[$name]['delete']) && $facprops[$name]['delete'] == 'on')) continue;
				$name = strtolower($name);
				$val = $extras = "";
				echo "<tr><td>$name</td>";
				# query
				if(isset($facprops[$name]['query'])) $val = $facprops[$name]['query'];
				echo "<td><textarea id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][query]' 
				cols='60' rows='2' >$val</textarea></td>";
				# extra citations not in pubmed
				if(isset($facprops[$name]['extras'])) $extras = $facprops[$name]['extras'];
				echo "<td><textarea id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][extras]' 
				cols='60' rows='2' >$extras</textarea></td>";
				# delete?
				echo "<td><input type='checkbox' id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][delete]' /></td>
				<tr>";
				
				echo "<input id='wp_pubmed_reflist_faclist' name='wp_pubmed_reflist[facprops][$name][last_update]' type='hidden' value='0' />";

			}
		}	
		echo "</table>";
	}
	
	
	/*
	The form that adds new faculty gets submitted each time whether or not there is anything new
	We use hidden input fields to make sure we keep all the old values
	*/
	function faclist_form(){
		$faclist = array();
		$facprops = @$this->options['facprops'];
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