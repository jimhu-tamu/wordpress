=== JSmol2WP ===
Contributors: Jim Hu
Tags: shortcodes, JSmol, Jmol, molecular graphics, PDB
Requires at least: 3.0
Tested up to: 4.1
Donate link:http://biochemistry.tamu.edu/index.php/alum/giving/
Stable tag: none
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to place JSmol molecular graphics applets in Wordpress posts or pages.

== Description ==
This shortcode plugin places JSmol applets in WordPress posts and pages.

Use [jsmol pdb='accession'] for a minimal version. jsmol2wp will look to see if a pdb file has been uploaded to your wordpress and it will use that file if it can find it. If it can't find a matching post for an uploaded attachement, it will try <a href='http://rcsb.org/pdb'>http://rcsb.org/pdb</a>. If it can't find a match there either, you'll get an error message in the JSmol window. Additional information on optional parameters are at the About/Help link in the applets.

This plugin was developed for use on the website for the Department of Biochemistry and Biophysics
at Texas A&M University (http://biochemistry.tamu.edu).

== Installation ==
Place in the plugins directory and activate. No additional files or configurations are needed.

Thanks to Bob Hanson and the JMol team for making the javascript code for jsmol available. See: 

http://chemapps.stolaf.edu/jmol/jsmol
http://wiki.jmol.org/index.php/Jmol_JavaScript_Object

This plugin also benefited from using Jaime Prilusky's mediawiki extension for inspiration
http://proteopedia.org/support/JSmolExtension/
== Upgrade Notice ==
Version 1.03 updates the Jmol libraries and fixes a bug with the load parameter
== Frequently Asked Questions ==
= Is there an example of an installation? =
See http://jimhu.org/jsmol2wp-plugin-released-at-wordpress-org/
= Where can I learn more about what JSmol can do? =
Jmol documentation can be found at http://jmol.sourceforge.net/#Learn%20to%20use%20Jmol and http://jmol.sourceforge.net/docs/JmolUserGuide/
== Screenshots ==
1. Applet for a protein. 
2. Applet for a small molecule.

== Changelog ==
= 1.03 =
* updated jsmol package from Jmol sourceforge
* Remove beta from help.htm
* fix bug where load param was not working
= 1.02 =
* fixes to this readme.txt file to improve the display at the wordpress.org plugin repository
= 1.01 =
* tweaks for wordpress.org deposition
= 1.0 =
* update JSmol code to 14.3.12_2015.01.28
* prepare for release to wordpress.org plugin repository
= 0.94 beta =
* add isosurface support
* rewrite the code to set up structure loading
* replace WP get_page_by_title with a function that matches the filename
* add jvxl to file types
* fixed bug where caption nonmatching required casting match as a string.
* move the help demo page to a more stable URL.
= 0.93 beta =
* set default type based on fileurl extension if present
* fix bug where reset button failed with data from fileurl
= 0.92 beta =
* change appletID to not require $acc.
= 0.9 beta =
* improve help page
* improve uniqueness identifiers for multiple Jmolapplets on the same post/page; add the option to hand code instances
* improve debug messages (or at least change them)
* make reset button standard and have it remember the load commands
* standard buttons depend on the type of molecule loaded.
* add some semicolons to the template to try to fix lint warnings: http://www.javascriptlint.com/online_lint.php
= 0.8 beta =
* removed data directory
* changed system for counting instances of the shorttag so we don't need preg_match
* removed whitespace from template hoping that solves the problem of themes adding markup
* simplified load script as suggested by Bob Hansen
* made applet IDs more unique by appending post id
= 0.7 beta =
* update jsmol libraries to 4.1.7_2014.06.09
* add dependencies for jquery-ui-core and jquery-ui-menu fixes popup problem in some themes
* refactor to support additional file types (in progress)
* fix multiline regex bug
* fix bug that caused failure to load when permalinks used ?p=post_number format
* debug constructor
*  debug view
..* add path to uploaded file
..* add test for get_page_by_title
= 0.6 alpha =
* register script before enqueueing it.
* added ability to add Jmol.script commands
* added the ability to add jmolCommandInput
= 0.5 alpha = 
* added wrap and debug options
= 0.4 alpha =
* changed to nojq.
* modified command processing to not split on allowed characters in Jmol syntax.
= 0.3 alpha =
* changed default to spin off in order to save client cpu
* custom command buttons working.
= 0.2 alpha =
* changed system to use a template based on the distro file simple1.htm. 
* added captioning
* works with local or remote pdb files from rcsb.org/pdb
= 0.1 pre-alpha =
* basic shortcode working with uploaded pdb file
* adds .pdb chemical/pdb mime type to allowed mime types
* handles multiple shortcodes on the same page
