<?php

class jsMol2wp{
	
	var $fileURL = '';
	
	function __construct($pdb){
		$this->path = plugins_url().'/jsmol2wp/';
		$this->pdb = $pdb;
		# determine the instance if there are multiple copies
		# of the shortcode in the post
		preg_match_all('/\[jsmol (.*)\]/U', get_the_content(), $m);
		foreach($m[1] as $i => $match){
			if(stripos($match, $pdb) > 0) $this->instance = $i;
		}
		$attachment = get_page_by_title($pdb, OBJECT, 'attachment' );
		if(!is_null($attachment) && isset($attachment->guid)){
			$this->fileURL = $attachment->guid;
		}
	}
	# this seems to be needed for reasons I don't understand.
	function jsMol2wp(){
	
	}
	
	
	function makeViewer($pdb, $caption, $commands){
		# variables substitution to handle multiple viewers on the same 
		# post/page
		$applet = "jmolApplet".$this->instance;
		$mydiv = "myDiv".$this->instance;
		$html = "";
		$template = $this->getTemplate();
		$template = str_replace('jmolApplet0',$applet, $template );
		$template = str_replace('1crn',$pdb, $template );
		$template = str_replace('__caption__',$caption, $template );
		$template = $this->makeScriptButtons($commands, $template);
		# look for a path to a local pdb file
		if($this->fileURL != ''){
			$template = str_replace(
					'http://www.rcsb.org/pdb/files/XXXX.pdb',
					$this->fileURL, $template);
		}elseif(!isset($pdb) || $pdb == ''){
			$html = "Please specify the name of an uploaded .pdb file";
		}
		$html .= $template;
		return $html;
	}
	
	function makeScriptButtons($commands, $template){
		$commands = str_replace("\n",' ', strip_tags($commands));
		$buttons = "";
		if($commands != ''){
			$commandsSet = explode('|', $commands);
			foreach($commandsSet as $i => $command){
				list($label, $script) = explode('=', $command."=");
				$label = trim($label);
				$script = trim($script);
				$buttons .= "jmolButton('$script','$label')\n";
				if($i%4 == 2) $buttons .= "jmolBr()\n";
			}
			#if($i%4 == 1) $buttons = "jmolBr()\n";
			$buttons .= "jmolButton('reset;select all; display not solvent;center;spacefill off;wireframe off;cartoons on;color structure;zoom 0;','reset')\n";	
		}
		$template = str_replace('__commands__',$buttons, $template );	
		return $template;
	}
	
	function getTemplate(){
		$template = file_get_contents(plugins_url().'/jsmol2wp/jsmol_template.htm');
		$template = str_replace('http://chemapps.stolaf.edu/jmol/jsmol/',$this->path, $template );
		$template = str_replace('__j2s__',$this->path."/j2s", $template );
		$template = str_replace('__help__', "<a href='$this->path/help.htm'>About/Help</a>", $template );
		return $template;
	}
}