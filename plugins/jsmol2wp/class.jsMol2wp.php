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
	
	
	function makeViewer($pdb, $caption, $commands, $wrap, $debug){
		# variables substitution to handle multiple viewers on the same 
		# post/page
		$applet = "jmolApplet".$this->instance;
		$mydiv = "myDiv".$this->instance;
		$html = "";
		$template = $this->getTemplate();
		$template = str_replace('jmolApplet0',$applet, $template );
		$template = str_replace('1crn',$pdb, $template );
		$template = str_replace('__caption__',$caption, $template );
		$template = $this->makeScriptButtons($commands, $template, $wrap);
		# look for a path to a local pdb file
		if($this->fileURL != ''){
			$template = str_replace(
					'http://www.rcsb.org/pdb/files/XXXX.pdb',
					$this->fileURL, $template);
		}elseif(!isset($pdb) || $pdb == ''){
			$html = "Please specify the name of an uploaded .pdb file";
		}
		$html .= $template;
		if($debug != 'false'){
			$html .= $this->debug();
		}
		return $html;
	}
	
	function makeScriptButtons($commands, $template, $wrap = 4){
		$commands = str_replace("\n",' ', strip_tags($commands));
		if($commands != ''){
			$commandsSet = explode('|||', $commands);
			foreach($commandsSet as $i => $command){
				list($label, $script) = explode('=', $command."=",2);
				$label = trim($label);
				$script = trim($script,"\n=");
				$buttons .= "jmolButton('$script','$label')\n";
				$j = $i+2;
				if($j%$wrap == 0) $buttons .= "jmolBr()\n";
			}
			$buttons .= "jmolButton('reset;select all; display not solvent;center;spacefill off;wireframe off;cartoons on;color structure;zoom 0;','reset')\n";	
		}
		$buttons .= $this->standardButtons($wrap);
		$template = str_replace('__commands__',$buttons, $template );	
		return $template;
	}
	/*
	jmolBr() to make $wrap buttons/row
	*/	
	function standardButtons($wrap){
		$str = "Jmol.setButtonCss(null,\"style='width:100px'\")\n";
		$stdButtons = explode("\n", 
'jmolButton("color cpk");
jmolButton("color group");
jmolButton("color amino");
jmolButton("color structure");
jmolButton("trace only");
jmolButton("cartoon only");
jmolButton("backbone only");
jmolButton("spacefill only;spacefill 23%;wireframe 0.15","ball&stick");');
		foreach($stdButtons as $i => $button){
			if($i%$wrap == 0) $str .= "jmolBr();\n";
			$str .= $button;
		}
		return $str;
	}
	
	function getTemplate(){
		$template = file_get_contents(plugins_url().'/jsmol2wp/jsmol_template.htm');
		$template = str_replace('http://chemapps.stolaf.edu/jmol/jsmol/',$this->path, $template );
		$template = str_replace('__j2s__',$this->path."/j2s", $template );
		$template = str_replace('__help__', "<a href='$this->path/help.htm'>About/Help</a>", $template );
		return $template;
	}
	
	function debug(){
		$str = '<pre>';
		# file path
		$dirpath = dirname(__FILE__);
		$str .= "Directory path:$dirpath\n";
		$str .= "URL path:        $this->path\n";
		$fileTests = array(
		#	'foo.js' => "$this->path",
			'JSmol.min.nojq.js' => "$this->path",
			'package.js' => "$this->path/j2s/core/"
		);
		foreach($fileTests as $file => $path){
			if(!file_get_contents("$path$file")){ 
				$str .= "can't load $path$file\n";
			}else{
				$str .= "$file load OK\n";
			}
		}
		$str .= "</pre>";
		return $str;
	}
}