<?php
class wpPubMedRefList{
	
	private $options = array();

	function __construct(){
		$this->options = get_option('wp_pubmed_reflist');	
	}
	
	/*
	key is the key to a particular query, e.g. a faculty name
	*/
	function wp_pubmed_reflist($key, $limit=50,  $linktext, $showlink){

		$debug = false;
		$html = '';
		$this->query = $this->build_recursive_query($key);
		$this->query = str_replace("\n",' ', $this->query);
		$this->query = preg_replace('/\s+/','+', $this->query);
		# if we just want the pubmed link don't bother with doing a query
		$showlink = strtolower($showlink);
		if($showlink != 'link only'){
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
			$html = $this->options['facprops'][$key.$limit]['reflist'];
		}
		switch($showlink){
			case 'false':
			case 'no':
				break;
			default:
				$html .= "<a href=http://www.ncbi.nlm.nih.gov/pubmed?term=$this->query>$linktext</a>";
		}
		return "<!-- $msg -->$html";
		
	}
	
	/*
	Use negative limit to pick one random reference from a list of abs[$limit]
	*/
	function reflist_query($key, $limit){
		$query = $this->query;
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
		return $html;
	}
	
	function add_extras($key){
		$extras_str = trim($this->options['facprops'][$key]['extras']);
		$extras = explode("\n", $extras_str);
		return array_filter($extras);
	}
	/*
	# use || to build OR clauses
	
	Changed in version 0.6 to make this more robust. Use *key* to mark a substitution.
	*/
	function build_recursive_query($query){
		#echo "working on $query<hr>";
		#if the whole thing is a key, make that the new query
		if($this->getQueryFromOptions($query)){
			$new_query = $this->getQueryFromOptions($query);
		}else{
			$new_query = $query;
		}
		# do * substitutions on the whole query
		$recursion = false;
		preg_match_all('/\*(.*)\*/U', $new_query, $m);
	#	echo "<pre>".print_r($m, true)."</pre>";
		foreach($m[1] as $i => $rawKey){
			if($this->getQueryFromOptions($rawKey)){
				$new_query = str_replace("*$rawKey*",$this->getQueryFromOptions($rawKey),$new_query);
				$recursion = true;
			}
		}
		# do the || clauses
		$clauses = explode('||', $new_query);
		$qarr = array();
		foreach($clauses as $clause){
			$clause = trim($clause);
			if($this->getQueryFromOptions($clause)){
				$qarr[] = '('.$this->getQueryFromOptions($clause).')';
				$recursion = true;
			}else{
				$qarr[] = $clause;
			}	
		}
		$new_query = implode('+OR+', $qarr);
		if($recursion == true){ 
			$new_query = $this->build_recursive_query($new_query);
		}	
		#echo "$new_query<hr>";
		return $new_query;
	}
	
	function getQueryFromOptions($key){
		$key = trim($key, "\n ()");
		if(	
			array_key_exists($key, $this->options['facprops']) && 
			isset($this->options['facprops'][$key]['query']) &&
			$this->options['facprops'][$key]['query'] != ''
			){
				return $this->options['facprops'][$key]['query'];
			}
			return false;
	}
	/**
	* Include the shortscriptfunctions for refs
	* 
	* enables:
	* [pmid-refs query="blah blah"]
	* 
	*/
}