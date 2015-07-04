<?php
/*
Convert SimpleXML objects returned by pubmed Efetch
*/

class PMIDeFetch{

	var $pmidObj;
	var $error_msg;
	
	function __construct($pmidObj){		
		$this->pmidObj = $pmidObj;

	}
	
	
	function article(){
		if (!is_object($this->pmidObj)) return false;
		return $this->pmidObj->MedlineCitation->Article;
	}

	/*
	return authors as an array.
	*/
	function authors(){
		$authors = array();
		$tags = array('LastName', 'Initials', 'Suffix', 'CollectiveName');
		if(is_object($this->article())){
			if(is_object($this->article()->AuthorList->Author)){
			foreach($this->article()->AuthorList->Author as $auth){
				foreach ($tags as $tag){
					$$tag = '';
					if(isset($auth->$tag)) $$tag = (string)$auth->$tag;
				}
				$cite_name = 'Author unknown';
				if($LastName != "") $cite_name = trim("$LastName, $Initials $Suffix");
				if($CollectiveName != "") $cite_name = "$CollectiveName";
				
				$authors[] = array(
					'Last' => $LastName,
					'Initials' => $Initials,
					'CollectiveName' => $CollectiveName,
					'Cite_name' => $cite_name
				);
			}
			}else{
				$authors[0] = array(
						'Last' => 'Anon.',
						'Initials' => '',
						'CollectiveName' => '',
						'Cite_name' => 'Anon.'			
				); 
			}
		}
		return $authors;
	}

	function title(){
		if (!$this->article()){
			return ($this->error_msg);
		}
		return (string)$this->article()->ArticleTitle;
	}

	function journal(){
		return (string)$this->article()->Journal->ISOAbbreviation;
	}

	function volume(){
		return (string)$this->article()->Journal->JournalIssue->Volume;
	}
	function issue(){
		return (string)$this->article()->Journal->JournalIssue->Issue;
	}

	function pmid(){
		return (string)$this->pmidObj->MedlineCitation->PMID;
	}

	function pages(){
		return (string)$this->article()->Pagination->MedlinePgn;
	}

	function year(){
		return (string)$this->article()->Journal->JournalIssue->PubDate->Year;
	}

	/*
	return abstract text as string. Can't use abstract for the method name because it is a reserved word.
	*/
	function abstract_text(){
		return (string)$this->article()->Abstract->AbstractText;
	}
	
	function pubmed_data(){
		return $this->pmidObj->PubmedData;
	}
	
	function xrefs(){
		if (!is_object($this->pmidObj)) return false;
		$arr = array();
		if(is_object ($this->pubmed_data())){
			$xrefs = $this->pubmed_data()->ArticleIdList;
			foreach ($xrefs->ArticleId as $xref){
				$arr[(string)$xref->attributes()] = (string)$xref;
			}
		}
		return $arr;
	}
	
	function mesh(){
		$arr = array();
		if (is_object($this->pmidObj->PubmedArticle->MedlineCitation->MeshHeadingList)){
			$mesh_list = $this->pmidObj->PubmedArticle->MedlineCitation->MeshHeadingList; #print_r($mesh_list);
			foreach($mesh_list->MeshHeading as $mesh_item){
				
				$base_heading = (string)$mesh_item->DescriptorName;
				switch ($mesh_item->QualifierName->count()){
					case 0:
						$arr[] = "$base_heading";
						break;				
					case 1:
						$arr[] = "$base_heading/".(string)$mesh_item->QualifierName;
						break;
					default:
						foreach ($mesh_item->QualifierName as $qualifier){
							$arr[] = "$base_heading/$qualifier";
						}
				}			
			}
		}
		return $arr;
	}
	function epub(){
		$epub = '';
		#echo "<pre>".print_r($this->pmidObj->PubmedData, true)."</pre>";
		if(is_object($this->pmidObj->PubmedData->History)){
			#echo __METHOD__."<br>";
			foreach($this->pmidObj->PubmedData->History as $pubMedDate){
				foreach($pubMedDate as $item){
					if((string)$item->attributes() == 'epublish'){
						$epub = "Epub ".$item->Year.'/'.$item->Month.'/'.$item->Day;
					}
				}
			}
		}
		return $epub;
	}
	
	function citation(){
		$authorlist = array();
		foreach($this->authors() as $auth){
			$authorlist[] = $auth['Cite_name'];
		}
		return array(
			'PMID'    	=> $this->pmid(),
			'Authors' 	=> implode(', ', $authorlist),
			'AuthorList' => $authorlist,
			'Year'   	=> $this->year(),
			'Title'    	=> $this->title(),
			'Journal'   => $this->journal(),
			'Volume'   	=> $this->volume(),
			'Issue'   	=> $this->issue(),
			'Pages'   	=> $this->pages(),
			'Abstract' 	=> $this->abstract_text(),
			'xrefs' 	=> $this->xrefs(),
			'EPub'    	=> $this->epub()
		);
	}
	
	function dump(){
		print_r($this->pmidObj);
	}
}