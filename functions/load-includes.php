<?php class eg_include_folders
	{
		function trawl_folder($folder)
			{
				$folder = EGDIR.$folder;
				if ($handler = opendir($folder)) :
					while (false !== ($file = readdir($handler))) :
						if ($file !== "." && $file !== ".." && $file != "load-includes.php" && strpos($file, ".php")) :
							include_once ($folder.$file);
						endif;
					endwhile;
					closedir($handler);
				endif;
			}
	}
	
//Include all the Easygram files
$include_folders = array("functions/");
foreach($include_folders as $inc_folder) :
	$include_folders = new eg_include_folders();
	$folder = $inc_folder;
	$include_folders->trawl_folder($folder);
endforeach;
?>