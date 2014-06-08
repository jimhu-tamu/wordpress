=== JSmol2wp ===
Contributors: Jim Hu
Tags: shortcodes, JSmol, Jmol, molecular graphics, PDB
Requires at least: 3.0
Tested up to: 3.9.1
Donate link:http://biochemistry.tamu.edu/index.php/alum/giving/
Stable tag: none
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to place JSmol molecular graphics applets in Wordpress posts or pages

== Description ==
This shortcode plugin places JSmol applets in WordPress posts and pages.

Use [jsmol pdb='accession'] for a minimal version. jsmol2wp will look to see if a pdb file has been uploaded to your wordpress and it will use that file if it can find it. If it can't find a matching post for an uploaded attachement, it will try <a href='http://rcsb.org/pdb'>http://rcsb.org/pdb</a>. If it can't find a match there either, you'll get an error message in the JSmol window. Additional information on optional parameters are at the About/Help link in the applets.

This plugin was developed for use on the website for the Department of Biochemistry and Biophysics
at Texas A&M University (http://biochemistry.tamu.edu).

== Installation ==
place in the plugins directory and activate. No additional files or configurations are needed.

Thanks to Bob Hanson and the JMol team for making the javascript code for jsmol available. See: 

http://chemapps.stolaf.edu/jmol/jsmol
http://wiki.jmol.org/index.php/Jmol_JavaScript_Object

This plugin also benefited from using Jaime Prilusky's mediawiki extension for inspiration
http://proteopedia.org/support/JSmolExtension/

== Development notes ==
0.5 alpha
- added wrap and debug options
0.4 alpha
- changed to nojq.
- modified command processing to not split on allowed characters in Jmol syntax.
0.3 alpha
- changed default to spin off in order to save client cpu
- custom command buttons working.
0.2 alpha
- changed system to use a template based on the distro file simple1.htm. 
- added captioning
- works with local or remote pdb files from rcsb.org/pdb
0.1 pre-alpha
basic shortcode working with uploaded pdb file
- adds .pdb chemical/pdb mime type to allowed mime types
- handles multiple shortcodes on the same page
