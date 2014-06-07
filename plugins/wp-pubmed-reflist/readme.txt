=== WP Pubmed Reflist ===
Contributors: Jim Hu
Tags: shortcodes, pubmed, references
Requires at least: 3.0
Tested up to: 3.5.1
Donate link:http://biochemistry.tamu.edu/index.php/alum/giving/
Stable tag: 0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to use a pubmed query to generate a reference list on a page

== Description ==

This plugin uses a pubmed query to generate a reference list on a page. It provides an admin page
where you can associate PubMed queries with keys and a shorttag that allows you to display a 
list of references based on any of your keys on a page or post.

This plugin was developed for use on the website for the Department of Biochemistry and Biophysics
at Texas A&M University (http://biochemistry.tamu.edu).

== Installation ==

1. Upload `wp-pubmed-reflist` folder to your `/wp-content/plugins` folder
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use Settings to associate keys with PubMed queries.
4. Use the shortcode in your posts or pages: [pmid-refs key="<key>" limit=10]

== Frequently Asked Questions ==
= How do I compose a query =
For Query syntax
See: http://www.ncbi.nlm.nih.gov/books/NBK3830/
= Can I combine saved queries =
Yes. use double pipes to combine other queries using their named keys. 
The plugin will combine the two queries with a logical OR
= Can I display a random reference based on my query =
Yes. Use negative limit to pick one random reference from a list of abs[$limit]
= Can I add references that are not in PubMed =
Yes, add these to the extras box. Use one reference per line. These will be added to the
end of your list. The plugin is not smart enough to sort these into the main list at this
time.
= Your donate link seems odd. How do I support this plugin =
This plugin was developed as part of my work for the Department of Biochemistry and Biophysics
at Texas A&M. Donating won't lead me to give up my day job to spend more time on plugin
development. But if you want to donate out of gratitude/niceness, give whatever you think 
would be appropriate to the Biochemistry/Biophysics improvement fund. It's tax-deductible
and it will go to some other worthy activity.
= Are these questions really frequently asked? =
No. This is the first release, so I'm just guessing what people will ask.
== Screenshots ==
1. Admin page
2. Page displaying a reference list using the shorttag

== Upgrade Notice ==
n/a
== Changelog ==
TODO:
* input validation for the admin page
* jquery datatables for settings view
= 0.5 =
* Add fulltext and PMC links
* Allow customization of text link to pubmed
* change display if there are zero hits
= 0.4 =
* integrate non-PMID citations
= 0.3 =
added the ability to delete a key - query pair from the admin settings UI
added the ability to construct complex queries using other keys.
	e.g.
		smith => smith j[au] AND escherichia coli[majr]
		jones => jones jp[au] AND enzyme
		smith_jones = smith || jones 
			= (smith j[au] AND escherichia coli[majr])OR(jones jp[au] AND enzyme)
= 0.2 =
* convert to OO
* condense options to a single array
= 0.1 =
* The prototype
