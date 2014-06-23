<?php

class jsMol2wp{
	
	var $fileURL = '';
	var $instance = 0;
	var $acc = '';
	var $appletID = '';
	var $type = '';
	var $load = '';
	
	public function __construct($acc, $type, $caption, $id, $fileURL, $isosurface){
		$this->path = plugins_url().'/jsmol2wp/';
		$this->acc = $acc;
		$this->type = $type;
		$p = get_post();
		# determine the instance if there are multiple copies
		# of the shortcode in this post
		# we want to do this without preg_match to work on different PHP versions
		$m = explode('[jsmol', get_the_content());
		foreach($m as $i => $match){
			$t = explode(']', $match);
			# there could be nested shortcodes or other shortcodes in the text
			# but trim off what is safe to trim off
			if(count($t) > 1){
				array_pop($t);
				$match = implode(']', $t);
			}
			# catenate the post_id to the instance to make the id unique
			# when displaying multiple posts per page
			if(	($acc == '' || stripos($match, $acc) > 0 ) &&
				($caption == '' || stripos($match, $caption) > 0) &&
				($fileURL == '' || stripos($match, $fileURL) > 0) &&
				($isosurface == '' || stripos($match, $fileURL) > 0) &&
				($id == '' || stripos($match, $id) > 0)
			) $this->instance = $p->ID."_$i";
		}
		
		if($fileURL != ''){
			# use a passed url if it's there
			$this->fileURL = $fileURL;
		}else{	
			# otherwise, look for an attachment
		#	$attachment = get_page_by_title($acc, OBJECT, 'attachment' );
			$attachment = self::getAttachmentPost("$acc.$type");
			if(!is_null($attachment) && isset($attachment->guid)){
				$this->fileURL = $attachment->guid;
			}
		}
		$this->isosurface = $isosurface;
		if($isosurface != ''){
			# isosurface will be an uploaded file or a URL
			# replace if it's an attachment
			$attachment = self::getAttachmentPost($isosurface);
			if(!is_null($attachment) && isset($attachment->guid)){
				$this->isosurface = $attachment->guid;
			}				
		}
		$this->appletID = "jmolApplet".$this->instance;

	}
	
	
	function makeViewer($acc, $type, $load, $caption, $commands, $wrap, $debug){
		# not used?
		$mydiv = "myDiv".$this->instance;		
		# initialize output
		$html = "";
		$template = $this->getTemplate($load);
		$template = str_replace('jmolApplet0',$this->appletID, $template );
		$template = str_replace('XXXX',$acc, $template );
		$template = str_replace('__caption__',$caption, $template );
		$template = $this->makeScriptButtons($commands, $template, $wrap);
		$html .= $template;
		if($debug != 'false'){
			$html .= $this->debug($debug);
		}
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
	/*
	Gets the template and creates the molecule load and format commands
	*/
	function getTemplate($load){
		$template = file_get_contents(dirname(__FILE__).'/jsmol_template.htm');
		# if load is set, it is used and we don't guess
		if($load != '') $loadStr = $load;
		# otherwise try to guess the right load string
		if($this->load != ''){ 
			$loadStr = $this->load; 
		}else{
			if($this->acc != '' && ($this->acc{0} == '$'||$this->acc{0} == ':')){
				$loadStr = "load $this->acc;spacefill 23%;wireframe 0.15;color cpk;spin off;";
				$this->type = 'mol';	
			}
			# if the type is pdb, look for it at rcsb.org
			if($this->type == 'pdb'){
				$loadStr = "load \"http://www.rcsb.org/pdb/files/$this->acc.pdb\";";			
			}
			# if there is a local file, override the guessed remote load and if needed, guess the type
			if($this->fileURL != ''){
				$this->type = pathinfo($this->fileURL, PATHINFO_EXTENSION);
				$loadStr = "load $this->fileURL;";
			}
			# add the default format
			switch ($this->type){
				case 'obj':
					#this is going to only be with a fileURL?
					$loadStr = str_replace(
						"load",
						"isosurface OBJ ", 
						$loadStr);
					$loadStr .= " spacefill 23%;wireframe 0.15;color cpk;spin off;";
					break;
				case 'xyz':
				case 'mol':
				case 'mol2':
					#change the default load coloring and display
					$loadStr .= ' spacefill 23%;wireframe 0.15;color cpk;spin off;';
					break;
				case 'mrc':
					return "Support for binary type: $this->type is not implemented yet";
					break;
				case 'pdb':
					$loadStr .= ' spacefill off;wireframe off;cartoons on;color structure;spin off;';
					break;
				default:
			}
		}
		# add isosurface if it's present
		if($this->isosurface != ''){
			$loadStr .= " isosurface $this->isosurface;";
		}
		# add the acc label
		if($this->acc != '') $loadStr .= "set echo top center; echo ".ltrim($this->acc,'$:').';';
		$template = str_replace('__load__', $loadStr, $template);
		# save the loadstr for use by the reset button
		$this->load = $loadStr;
		$template = str_replace('http://chemapps.stolaf.edu/jmol/jsmol/',$this->path, $template );
		$template = str_replace('__j2s__',$this->path."j2s", $template );
		$template = str_replace('__help__', "<a href='$this->path/help.htm'>About/Help</a>", $template );
		return $template;
	}
	
	static function getAttachmentPost($filename){
		$args = array('post_type' => 'attachment', 'posts_per_page'=>-1, 'post_status' => 'any', 'post_parent' => null);
		$media = get_posts( $args );
		foreach($media as $p){
			$attFileName = basename($p->guid);
			if($filename == $attFileName){
				return $p;
			}	
		}
		# if the above fails, check for a case where the extension was not given in type
		$attachment = get_page_by_title($filename, OBJECT, 'attachment' );
		return $attachment;
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
				
				$str .= "test whether various files are readable by wp_remote_fopen\n";
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