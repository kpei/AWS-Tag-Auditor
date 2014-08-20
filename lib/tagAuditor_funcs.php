<?php
use Aws\Ec2\Ec2Client;
use Aws\Common\Enum\Region;
use Aws\S3\S3Client;
use Aws\AutoScaling\AutoScalingClient;

//Define all formatted output "screen" widths
define("EC2_Name_Width", 40);
define("Tag_Width", 30);
define("S3_Name_Width", 60);
define("ASG_Name_Width", 70);

/* Get all ASG formatted for tags 
@Return Array of { ResourceId , Key => Value }
*/	
function getTagsFromASG($accessKey,$secretKey,$region, $tagfilters)
{

		$ASClient = AutoScalingClient::factory(array(
		'key'    => $accessKey,
		'secret' => $secretKey,
		'region' => $region
		));
	
	//First we get all ASG ids
	$res = $ASClient->describeTags();
	//Next we clean up all the residual information and remove duplicates from the results
	$formattedReturn = array();
	$temp = array();
	foreach($res["Tags"] as $ASG){
		$temp[] = $ASG["ResourceId"];
	}
	//remove duplicates
	$temp = array_unique($temp, SORT_STRING);
	foreach($temp as $t){
		$formattedReturn[]["ResourceId"] = $t;
	}
	
	//Here we create a custom filter array that filters out the tags
	$describeTagFilters = array(
							"Filters" =>
								array(
										array(
											"Name" => "Key",
											"Values" => $tagfilters
											)
									)
								);
	$res = $ASClient->describeTags($describeTagFilters);
	//Next fill in the tags that are filtered into the original array
	foreach($res["Tags"] as $ASG){
		for($i=0; $i < count($formattedReturn); $i++){
			if($formattedReturn[$i]["ResourceId"] == $ASG["ResourceId"])
				$formattedReturn[$i][$ASG["Key"]] = $ASG["Value"];
		}
	}
	
	return $formattedReturn;
}

/* Get all S3 Buckets formatted for tags 
@Return Array of { Name, Array of { Tags } }
*/	
function getTagsFromS3Buckets($accessKey, $secretKey, $tagfilters){

	$S3Client = S3Client::factory(array(
		'key'	=> $accessKey,
		'secret' => $secretKey));
		
	$result = $S3Client->listBuckets();
	
	$formattedReturn = array();
	$j = 0;
	foreach($result["Buckets"] as $bucket){
		try {
			$result = $S3Client->getBucketTagging(array("Bucket" => $bucket["Name"]));
			//if no exception is thrown then there must be a Tag
			$formattedReturn[$j]["Name"] = $bucket["Name"];
			
			$keys = array();
				for($k=0; $k < count($result["TagSet"]); $k++){
					$keys[] = $result["TagSet"][$k]["Key"];
				}
				
			foreach($tagfilters as $tag){
				$index = array_search($tag, $keys);
				if($index !== false){
					$formattedReturn[$j]['Tags'][] = $result["TagSet"][$index]["Value"];
				} else {
					$formattedReturn[$j]['Tags'][] = "-";
				}
			}
			
			$j++;
		} catch (Exception $e){
			$formattedReturn[$j]["Name"] = $bucket["Name"];
			$j++;
		}
	} 
	
	return $formattedReturn;
}

/* Get all EC2 Instances formatted for tags 
@Return Array of { InstanceID, InstanceName, Array of { Tags_} }
*/
function getTagsFromEC2Instances($accessKey,$secretKey,$region, $tagfilters)
{

		$EC2Client = Ec2Client::factory(array(
		'key'    => $accessKey,
		'secret' => $secretKey,
		'region' => $region
	));
	
	$formattedReturn = array();
	$j = 0; //counter
	$result = $EC2Client->DescribeInstances();
	$reservations = $result['Reservations'];
		foreach($reservations as $r){
			$instances = $r['Instances'];
			foreach($instances as $i){
				$formattedReturn[$j]['InstanceId'] = $i['InstanceId'];
				//stuff all the tag keys in an instance into an array
				$keys = array();
				if(isset($i['Tags'])){
					for($k=0; $k < count($i['Tags']); $k++){
						$keys[] = $i['Tags'][$k]["Key"];
					}
				}
					
				//Find the name of the instance through tags
					$index = array_search("Name", $keys);
					if($index !== false){
							$formattedReturn[$j]['InstanceName'] = $i["Tags"][$index]["Value"];
					} else {
							$formattedReturn[$j]['InstanceName'] = "-";
					}
				//Filter by step through each filtered tag and see if the keys array has it
					foreach($tagfilters as $tag){
					$index = array_search($tag, $keys);
						if($index !== false){
							$formattedReturn[$j]['Tags'][] = $i["Tags"][$index]["Value"];
						} else {
							$formattedReturn[$j]['Tags'][] = "-";
						}
					}
					
				$j++;
				}
			}
			
	return $formattedReturn;
}

function formatOutputASG($heading, $output, $tagfilters, $of, $reg){
if($of=="screen"){
	//Header instance name + id (fixed)
	printf("%-".ASG_Name_Width."s", $heading[0]);
	//Header Tags (variable)
	for($i=1; $i < count($heading); $i++){
		printf("%-".Tag_Width."s", $heading[$i]);
	}
	echo "\n";
	
	foreach($output as $o){
		printf("%-".ASG_Name_Width."s", $o['ResourceId']);
		//See the way the array is returned from getTagsFromASG()
		//If no tag, the field is empty
		foreach($tagfilters as $tag){
			if(!isset($o[$tag]))
				printf("%-".Tag_Width."s","-");
			else
				printf("%-".Tag_Width."s", $o[$tag]);
		}
		echo "\n";
	}
} else if($of=="file"){
	foreach($output as $o){
		echo $reg."\t".$o['ResourceId'];
		foreach($tagfilters as $tag){
			if(!isset($o[$tag]))
				echo "\t".$tag."\t"."-";
			else
				echo "\t".$tag."\t".$o[$tag];
		}
		echo "\n";
	}
}
}

function formatOutputEC2($heading, $output, $tagfilters, $of, $reg){
if($of=="screen"){

	//Header instance name + id (fixed)
	printf("%-".EC2_Name_Width."s %-".Tag_Width."s", $heading[0], $heading[1]);
	//Header Tags (variable)
	for($i=2; $i < count($heading); $i++){
		printf("%-".Tag_Width."s", $heading[$i]);
	}
	echo "\n";
	
	foreach($output as $o){
		printf("%-".EC2_Name_Width."s %-".Tag_Width."s", $o['InstanceName'], $o['InstanceId']);
		for($i=0; $i < count($o['Tags']); $i++){
			printf("%-".Tag_Width."s", $o['Tags'][$i]);
		}
		echo "\n";
	}
} else if($of=="file"){
	foreach($output as $o){
		echo $reg."\t".$o['InstanceName']."\t".$o['InstanceId'];
		for($i=0; $i < count($o['Tags']); $i++){
			echo "\t".$tagfilters[$i]."\t".$o['Tags'][$i];
		}
		echo "\n";
	}
}
}

function formatOutputS3($heading, $output, $of){
if($of=="screen"){
	//Header instance name + id (fixed)
	printf("%-".S3_Name_Width."s", $heading[0]);
	//Header Tags (variable)
	for($i=1; $i < count($heading); $i++){
		printf("%-".Tag_Width."s", $heading[$i]);
	}
	echo "\n";
	
	foreach($output as $o){
		printf("%-".S3_Name_Width."s", $o['Name']);
		//if No tags are found, print blanks
		if(!isset($o['Tags'])){
			for($i=1; $i < count($heading);$i++){
				printf("%-".Tag_Width."s", "-");
			}
		} else {
			//Else just print them normally
			for($i=0; $i < count($o['Tags']); $i++){
				printf("%-".Tag_Width."s", $o['Tags'][$i]);
			}	
		}
		echo "\n";
	}
} else if($of=="file"){
	
	foreach($output as $o){
		echo $o['Name'];
		if(!isset($o['Tags'])){
			for($i=1; $i < count($heading);$i++){
				echo "\t".$heading[$i]."\t"."-";
			}
		} else {
			for($i=0; $i < count($o['Tags']); $i++){
				echo "\t".$heading[$i+1]."\t".$o['Tags'][$i];
			}
		}
		echo "\n";
	}
}
}

function getConfig($filename){
	return spyc_load_file($filename);
}


?>