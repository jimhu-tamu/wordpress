<?php
class wpPubMedRefList{
	
	private $options = array();

	function __construct(){
		$this->options = get_option('wp_pubmed_reflist');	
	}
	
	/*
	key is the key to a particular query, e.g. a faculty name
	*/
	function wp_pubmed_reflist($key,$limit=50){

		$debug = false;
		$msg = "load from cache<br>";
		if(	$debug || !isset($this->options['facprops'][$key.$limit]['last_update']) ){
			$elapsed = 0;
		}else{
			$elapsed = time() - $this->options['facprops'][$key.$limit]['last_update'];
		}	
		if(	$debug 
			|| !isset($this->options['facprops'][$key.$limit]['reflist']) 
			|| $elapsed > 60*60*24 
			#||true # uncomment for debugging
			){
			#do the update
			$msg = "updating from pubmed last update:".$this->options['facprops'][$key.$limit]['last_update'];
			$this->options['facprops'][$key.$limit]['reflist'] = $this->reflist_query($key, $limit);
			
			# update the timestamp array
			$this->options['facprops'][$key.$limit]['last_update'] = time();

			# save the ref list to the options table
			update_option('wp_pubmed_reflist', $this->options);		
		}
		return "<!-- $msg -->".$this->options['facprops'][$key.$limit]['reflist'];
		
	}
	
	/*
	Use negative limit to pick one random reference from a list of abs[$limit]
	*/
	function reflist_query($key, $limit){
		$query = self::build_recursive_query($key);		
		if ($query == "($key)") return "please enter a query for $key in the admin panel<br>";
		$random = false;
		if ($limit < 0){
			$limit = abs($limit);
			$random = true;
		}
		
		# Step 1: Call esearch to get a list of PMIDs
		$query = str_replace(' ','+',$query)."&dispmax=$limit";
		$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=$query";
		$encoded_url = urlencode($url);
		$xml = simplexml_load_file($encoded_url); 
		# extract PMIDs
		$id_list = array();
		foreach ($xml->IdList->Id as $pmid){ 
			$id_list[] = (string)$pmid;
		}
	
		#Step 2 call efetch to get the actual citations
		$refs = array();
		if(!empty($id_list) ){
			$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=".implode(',',$id_list)."&retmode=xml";
			$encoded_url = urlencode($url);
			$xml = simplexml_load_file($encoded_url); 
			$i = 0;
			foreach ($xml->PubmedArticle as $article){
				if($i >= $limit) break;
				$i++;
				$p = new PMIDeFetch($article);
				$citation = $p->citation(); 
				$refs[] =
					$citation['Authors'].
					' ('.$citation['Year'].') '.
					"<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=pubmed&amp;dopt=Abstract&amp;list_uids=".$citation['PMID']."'>".$citation['Title']."</a> ".
					'<i>'.$citation['Journal'].'</i> '.
					'<b>'.$citation['Volume'].'</b>: '.
					$citation['Pages'];
				
			}
		}
		$refs = array_merge($refs, $this->add_extras($key));
		# if random, pick a random one from the list
		if($random){
			$k = rand(0, $limit-1);
			$refs = array($refs[$k]); 
		}
		
		# make the list
		$html = "<ol>";
		$html .= "<li>".implode("</li>\n<li>",$refs)."</li>";
		$html .= "</ol>";
		$html .= "<a href=http://www.ncbi.nlm.nih.gov/pubmed?term=$query>Search PubMed</a>";
		return $html;
	}
	
	function add_extras($key){
		$extras_str = trim($this->options['facprops'][$key]['extras']);
		$extras = explode("\n", $extras_str);
		return array_filter($extras);
	}
	
	# use || to build OR clauses
	function build_recursive_query($query){
		$q = array();
		$query = preg_replace('/\s+/',' ',$query);
		$clauses = explode('||',$query);
		foreach($clauses as $clause){
			$clause = trim($clause);
			if(	
				array_key_exists($clause, $this->options['facprops']) && 
				isset($this->options['facprops'][$clause]['query']) &&
				$this->options['facprops'][$clause]['query'] != ''
				){
				$q[] = self::build_recursive_query($this->options['facprops'][$clause]['query']);
			}else{
				$q[] = $clause;
			}
		}
		$new_query = '('.implode(')OR(',$q).')';
		# need to get rid of leading and trailing empty clauses
		return str_replace(array('()OR','OR()'),'',$new_query);
	}
	
	/**
	* Include the shortscriptfunctions for refs
	* 
	* enables:
	* [pmid-refs query="blah blah"]
	* 
	*/
}