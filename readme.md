AWS Tag Auditor
======

The AWS Tag Auditor, TAuditor for short, is an automated process that scans through Amazon resources for missing tags as specified by the end user.  TAuditor supports Amazon S3, AutoScaling Groups and Elastic Compute Cloud 2.  

Requirements
------
  
* PHP >= 5
* AWS SDK - Ensure that AWS SDK folder `/vendor` is present in the same folder.  This can be changed on line 2 of the index.php

Usage
------
  
Using the command line:  
` php index.php -c configFileLocation -o screen|file `  

**-c** - Specify the configuration file location. This is a formatted .YAML file with Amazon keys, and resource information, see section on Config format for more information.  
**-o** - Only accepts `screen` or `file` as input, this specifies whether the tag auditors output will be in a readable format or in tab delimited style for further manipulation.  This usually works in complement with the other AWS tag tools.  Output to a file can be achieved by appending `> OutputFileName.txt` to the end of the command.  

Configuration File
------
  
The configuration fine is a .YAML format file.  This yaml format is used for easy editing as the markup is in an easy human readable format. [Read more .YAML](http://www.yaml.org/start.html)  

Below is a structure of the configuration file:

__Keys__:

* Secret\_Key \- Your AWS Developer Secret Key  
* Access\_Key \- Your AWS Developer Access Key  

__Region__: An array of regions to check in.  
__Tags__: An array of tags to look for and report back on  
__Platform__: An array of platforms to check.  Currently the ones supported are EC2, S3 and ASG  

### Example YAML configuration file

    Keys:
      Secret_Key: *****************************
      Access_Key: ******************
    Region:
      - us-east-1
      - us-west-1
      - us-west-2
      - eu-west-1
    Tags:
      - Product
      - Uses
      - Owner
    Platform:
      - EC2
      - S3
      - ASG

Creator: Kevin Pei  
Copyright 2014  
[MIT License](https://tldrlegal.com/license/mit-license#summary)