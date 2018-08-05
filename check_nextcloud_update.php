#!/usr/bin/php
<?php

# Source
# https://github.com/cstegm/check_nextcloud_update

# Changelog
# 2018-08-05 added optional perfdata output ("-p" ) - doctore74 <doc@snowheaven.de>

$shortopts  = "";
$shortopts .= "H:";  // Required value
$shortopts .= "S";   // No value
$shortopts .= "p";   // perfdata output
$longopts = array(
  "help"
);

$options = getopt($shortopts, $longopts);

$nextcloud_releases = "https://download.nextcloud.com/server/releases/";

if(array_key_exists("help",$options)){
  echo "HELP:\n";
  echo " -H hostname \n";
  echo " -S SSL\n";
  echo " -p perfdata output\n";

  exit(3);
}

if(!array_key_exists("H",$options)){
  echo "Please Specify a Host";
  exit(3);
}

if(array_key_exists("S",$options)){
  $nextcloud_server = "https://".$options["H"];
}else{
  $nextcloud_server = "http://".$options["H"];
}
$nextcloud_status = "$nextcloud_server/status.php";

function error($e){
  echo "Something went wrong: $e";
  exit(3);
}

function get_newest_version($nextcloud_releases="https://download.nextcloud.com/server/releases/"){
  $return=false;
  try {
    $homepage = file_get_contents($nextcloud_releases);
    if($homepage === false){
      error("No Content");
    }
  }catch(Exception $e){
    error($e);
  }

  $arr=explode("\n",$homepage);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
  $new[]=array_pop($arr);
//  print_r($new);

  foreach($new as $val){
    if(preg_match('/nextcloud-([0-9]+\.[0-9]\.[0-9])\.zip\.md5/',$val,$res)){
      if(is_array($res)){
        return $res[1];
      }
    }
  }

  return false;
}

function get_installed_version($nextcloud_status){
  $res=false;

  try{
    $status = file_get_contents($nextcloud_status);
    if($status === false){
      error("No Content");
    }
  }catch(Exception $e){
    error($e);
  }
  $json = json_decode($status);
  if(is_object($json)){
    $res = $json->versionstring;
  }
  return $res;
}

# get data
$newer=get_newest_version();
$actual=get_installed_version($nextcloud_status);

# perfdata
if(array_key_exists("p",$options)){
  $perfdata = "running=".str_replace(".", "", $actual)." stable=".str_replace(".", "", $newer);
}
else {
  $perfdata = "";
}

# output
if(version_compare($newer,$actual,"eq")){
  echo "Current version is ($actual). (channel: stable, version: $newer)|$perfdata";
  exit(0);
}else{
  echo "Current version is ($actual). Update to Nextcloud $newer available. (channel: stable)|$perfdata";
  exit(1);
}
