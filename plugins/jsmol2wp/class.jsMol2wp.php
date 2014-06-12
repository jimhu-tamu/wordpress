<?php

class jsMol2wp{
	
	var $fileURL = '';
	var $instance = 0;
	var $acc = '';
	var $type = '';
	
	public function __construct($acc, $type, $fileURL){
		$this->path = plugins_url().'/jsmol2wp/';
		$this->acc = $acc;
		$this->type = $type;
		# determine the instance if there are multiple copies
		# of the shortcode in the post
		$m = explode('[jsmol', get_the_content());
		foreach($m as $i => $match){
			if(stripos($match, $acc) > 0) $this->instance = $i;
		}
		
		if($fileURL != ''){
			# use a passed url if it's there
			$this->fileURL = $fileURL;
		}else{	
			# otherwise, look for an attachment
			$attachment = get_page_by_title($acc, OBJECT, 'attachment' );
			if(!is_null($attachment) && isset($attachment->guid)){
				$this->fileURL = $attachment->guid;
			}
		}
	}
	
	
	function makeViewer($acc, $type, $caption, $commands, $wrap, $debug){
		# variables substitution to handle multiple viewers on the same 
		# post/page
		$applet = "jmolApplet".$this->instance;
		$mydiv = "myDiv".$this->instance;
		$html = "";
		$template = $this->getTemplate();
		$template = str_replace('jmolApplet0',$applet, $template );
		$template = str_replace('1crn',$acc, $template );
		$template = str_replace('XXXX',$acc, $template );
		$template = str_replace('__caption__',$caption, $template );
		$template = $this->makeScriptButtons($commands, $template, $wrap);
		# look for a path to a local data file
		if($this->fileURL != ''){
			$template = str_replace(
					"http://www.rcsb.org/pdb/files/$acc.pdb",
					$this->fileURL, $template);
		}elseif(!isset($acc) || $acc  == ''){
			$html = "Please specify the name of an uploaded .pdb file";
		}
		$html .= $template;
		if($debug != 'false'){
			$html .= $this->debug();
		}
		#$html .= "debug:$debug<br>wrap:$wrap";
		return $html;
	}
	
	function makeScriptButtons($commands, $template, $wrap = 4){
		$buttons = '';
		$jmolCommandInput = '';
		$notButtons = 0;
		$applet = "jmolApplet".$this->instance;
		$commands = str_replace("\n",' ', strip_tags($commands));
		if($commands != ''){
			$commandsSet = explode('|||', $commands);
			foreach($commandsSet as $i => $command){
				list($label, $script) = explode('=', $command."=",2);
				$label = trim($label);
				$script = trim($script,"\n=");
				# if there is no script, assume that the line is raw Jmol script
				switch($label){
					case '':
						#Jmol.script(myJmol,"spacefill off; wireframe 0.3;");
						$buttons .= "Jmol.script($applet,\"$script\");\n";
						$notButtons++;
						break;
					case 'jmolCommandInput':
						$jmolCommandInput .= "Jmol.jmolCommandInput($applet);\n";
						$notButtons++;
						break;	
					default:
						$buttons .= "jmolButton('$script','$label')\n";
				}				
				$j = $i+2-$notButtons;
				if($j%$wrap == 0) $buttons .= "jmolBr()\n";
			}
			$buttons .= "jmolButton('reset;select all; display not solvent;center;spacefill off;wireframe off;cartoons on;color structure;zoom 0;','reset')\n";	
		}
		$buttons .= $this->standardButtons($wrap).$jmolCommandInput;
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
jmolButton("spacefill 23%;wireframe 0.15","ball&stick");');
		foreach($stdButtons as $i => $button){
			if($i%$wrap == 0) $str .= "jmolBr();\n";
			$str .= $button;
		}
		return $str;
	}
	
	function getTemplate(){
		$template = file_get_contents(dirname(__FILE__).'/jsmol_template.htm');
		switch ($this->type){
			case 'obj':
				$template = str_replace(
					"+'load",
					"+'isosurface OBJ ", 
					$template);
				$template = str_replace(
					"+'spacefill off;wireframe off;cartoons on;color structure;spin off;'",
					"+'spacefill 23%;wireframe 0.15;color cpk;spin off;'", 
					$template);
				break;
			case 'mol':
				#change the default load coloring and display
				$template = str_replace(
					"+'spacefill off;wireframe off;cartoons on;color structure;spin off;'",
					"+'spacefill 23%;wireframe 0.15;color cpk;spin off;'", 
					$template);
				break;
			case 'mrc':
				return "Support for binary type: $this->type is not implemented yet";
				break;
			default:
		}	
		$template = str_replace('http://chemapps.stolaf.edu/jmol/jsmol/',$this->path, $template );
		$template = str_replace('__j2s__',$this->path."j2s", $template );
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
			if(!wp_remote_fopen("$path$file")){ 
				$str .= "can't load $path$file\n";
			}else{
				$str .= "$file load OK\n";
			}
		}
		$str .= 'file_get_contents: ';
		$str .=  file_get_contents(__FILE__) ? 'Enabled' : 'Disabled';
		$str .= "\npath to uploaded file:".$this->fileURL."\n";
		$attachment = get_page_by_title($this->acc, OBJECT, 'attachment' );
		if(is_null($attachment)) $str .= "attachment for $this->acc not found\n";
		$str .= "attachment:".print_r($attachment,true);
		$str .= "</pre>";
		return $str;
	}
}