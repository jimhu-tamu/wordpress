<?php

class jsMol2wp{
	
	var $fileURL = '';
	var $instance = 0;
	var $acc = '';
	var $appletID = '';
	var $type = '';
	
	public function __construct($acc, $type, $caption, $id, $fileURL){
		$this->path = plugins_url().'/jsmol2wp/';
		$this->acc = $acc;
		$this->type = $type;
		$p = get_post();
		# determine the instance if there are multiple copies
		# of the shortcode in this post
		$m = explode('[jsmol', get_the_content());
		foreach($m as $i => $match){
			# catenate the post_id to the instance to make the id unique
			# when displaying multiple posts per page
			if(	stripos($match, $acc) > 0 &&
				($caption == '' || stripos($match, $caption) > 0) &&
				($fileURL == '' || stripos($match, $fileURL) > 0) &&
				($id == '' || stripos($match, $id) > 0)
			) $this->instance = $p->ID."_$i";
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
		$this->appletID = "jmolApplet".$this->instance;

	}
	
	
	function makeViewer($acc, $type, $load, $caption, $commands, $wrap, $debug){
		# variables substitution to handle multiple viewers on the same 
		# post/page
		$applet = $this->appletID;
		# not used?
		$mydiv = "myDiv".$this->instance;
		
		# initialize output
		$html = "";
		$template = $this->getTemplate($load);
		$template = str_replace('jmolApplet0',$applet, $template );
	#	$template = str_replace('1crn',$acc, $template );
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
			$html .= $this->debug($debug);
		}
		#$html .= "debug:$debug<br>wrap:$wrap";
		return $html;
	}
	
	function makeScriptButtons($commands, $template, $wrap = 4){
		$buttons = '';
		$jmolCommandInput = '';
		$notButtons = 0;
		$applet = $this->appletID;
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
						# if label is not set, we assume the user wants Jmol script not
						# wrapped in a button
						$buttons .= "Jmol.script($applet,\"$script\");\n";
						$notButtons++;
						break;
					case 'jmolCommandInput':
						# this is a special case to add a command line input
						$jmolCommandInput .= "Jmol.jmolCommandInput($applet);\n";
						$notButtons++;
						break;	
					default:
						$buttons .= "jmolButton('$script','$label')\n";
				}				
				$j = $i+2-$notButtons;
				if($j%$wrap == 0) $buttons .= "jmolBr()\n";
			}
		}
		$buttons .= "jmolButton('reset;select all;$this->load','reset')\n";	
		$buttons .= $this->standardButtons($wrap).$jmolCommandInput;
		$template = str_replace('__commands__',$buttons, $template );	
		return $template;
	}
	/*
	jmolBr() to make $wrap buttons/row
	*/	
	function standardButtons($wrap){
		$str = "Jmol.setButtonCss(null,\"style='width:100px'\")\n";
		$stdButtons = array();
		switch ($this->type){
			case 'pdb':
				$stdButtons = explode("\n", 
'jmolButton("color cpk");
jmolButton("color group");
jmolButton("color amino");
jmolButton("color structure");
jmolButton("trace only");
jmolButton("cartoon only");
jmolButton("backbone only");
jmolButton("spacefill 23%;wireframe 0.15","ball&stick");'
);
				break;
			default:
				$stdButtons = explode("\n", 
'jmolButton("color cpk");
jmolButton("color grey");
jmolButton("spacefill","spacefill");
jmolButton("if(dotsflag);dots off;dotsflag = false;else;dots on;dotsflag = true;endif","dots");
jmolButton("spacefill off;wireframe 0.15","wireframe");
jmolButton("spacefill 23%;wireframe 0.15","ball&stick");'
);
				break;
				
		}

		foreach($stdButtons as $i => $button){
			if($i%$wrap == 0) $str .= "jmolBr();\n";
			$str .= $button;
		}
		return $str;
	}
	
	function getTemplate($load){
		$template = file_get_contents(dirname(__FILE__).'/jsmol_template.htm');
		$loadStr = "load \"http://www.rcsb.org/pdb/files/XXXX.pdb\";set echo top center;echo XXXX;'
+'spacefill off;wireframe off;cartoons on;color structure;spin off;";
		if($load != ''){ 
			$loadStr = $load; 
		}elseif($this->acc{0} == '$'||$this->acc{0} == ':'){
			$loadStr = "load $this->acc;spacefill 23%;wireframe 0.15;color cpk;spin off;";
			$this->acc = ltrim($this->acc,'$:');
			$this->type = 'molecule';	
		}	
		$template = str_replace('__load__', "+'$loadStr'", $template);
		# save the loadstr for later use
		$this->load = $loadStr;
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
			case 'xyz':
			case 'mol':
			case 'mol2':
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
	
	function debug($debug){
		$str = '<pre>';
		# file path
		switch($debug){
			case 'short':
				$str .= "applet ID: $this->appletID\n";
				$str .= "acc: $this->acc\n";
				$str .= "type:$this->type\n";
				break;
			case 'full':
			default:
				$str .= print_r($this,true);
				$dirpath = dirname(__FILE__);
				$str .= "Directory path:$dirpath\n";
				$fileTests = array(
					'JSmol.min.nojq.js' => "$this->path",
					'package.js' => "$this->path/j2s/core/"
				);
				
				$str .= "test whether versious files are readable by wp_remote_fopen\n";
				foreach($fileTests as $file => $path){
					if(!wp_remote_fopen("$path$file")){ 
						$str .= "can't load $path$file\n";
					}else{
						$str .= "$file load OK\n";
					}
				}
				$str .= 'file_get_contents: ';
				$str .=  file_get_contents(__FILE__) ? 'Enabled' : 'Disabled';
				if($this->fileURL != ''){
					$str .= "\npath to uploaded file:".$this->fileURL."\n";
					$tmp = wp_remote_fopen($this->fileURL);
					$str .= "Excerpt from file:".substr($tmp, 0, 20)."...\n";
					$attachment = get_page_by_title($this->acc, OBJECT, 'attachment' );
					if(is_null($attachment)) $str .= "attachment for $this->acc not found\n";
				}
				break;	
		}
		
		$str .= "</pre>";
		return $str;
	}
}