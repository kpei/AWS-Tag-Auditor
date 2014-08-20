<?php 
require 'vendor/autoload.php'; //You can change this
require 'lib/Spyc.php';
require 'lib/tagAuditor_funcs.php';

//Check opts
$opt = getopt("c::o::");
//Default Opts
	if(!$opt["c"]){
		if(file_exists("tagAuditor.yaml")){
			echo "Config file parameter missing, default assumed: tagAuditor.yaml";
			$configFile = "tagAuditor.yaml";
		} else {
			echo "Config file parameter missing, please use as: \n php tagAuditor.php -c configFileLocation -o screen|file";
			die;
		}
	 } else { 
		if(file_exists($opt["c"]))
			$configFile = $opt["c"];
		else {
			echo "That file doesn't even exist dude!!";
			die;
		}
	}
	
	if(!$opt["o"]){
		echo "Output format parameter missing, default format: screen";
		$outputFormat = "screen";
	} else {
		$outputFormat = $opt["o"];
	}
	
$config = getConfig($configFile);

$ak = $config['Keys']['Access_Key'];
$sk = $config['Keys']['Secret_Key'];
$tagfilters = $config['Tags'];

if(in_array("EC2",$config['Platform'])){
$region = $config['Region'];
	
	foreach($region as $reg){
		//Get from Region
		if($outputFormat=="screen")
			echo "\n\n"."Retrieving EC tags from ".$reg."\n-------------------------------------------------------------\n";
		
		$arr = getTagsFromEC2Instances($ak, $sk, $reg, $tagfilters);
			if($outputFormat=="screen"){
				$EC2OutputHeading = array_merge((array)"Name", (array)"ID", $tagfilters);
			} else $EC2OutputHeading = null;
		formatOutputEC2($EC2OutputHeading, $arr, $tagfilters, $outputFormat, $reg);
	}
}

if(in_array("S3",$config['Platform'])){
	$arr = getTagsFromS3Buckets($ak, $sk, $tagfilters);
	$S3Headings = array_merge((array)"Bucket Name", $tagfilters);
	formatOutputS3($S3Headings, $arr, $outputFormat);
}

if(in_array("ASG", $config['Platform'])){
	$region = $config['Region'];
	
	
	foreach($region as $reg){
		//Get from Region
		if($outputFormat=="screen")
			echo "\n\n"."Retrieving ASG tags from ".$reg."\n-------------------------------------------------------------\n";
		
		$arr = getTagsFromASG($ak, $sk, $reg, $tagfilters);
			if($outputFormat=="screen"){
				$ASGOutputHeading = array_merge((array)"ASG Resource Id", $tagfilters);
			} else $ASGOutputHeading = null;
		formatOutputASG($ASGOutputHeading, $arr, $tagfilters, $outputFormat, $reg);
	}
}

?>