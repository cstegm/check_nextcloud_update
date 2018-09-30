#!/usr/bin/php
<?php

# Source
# https://github.com/cstegm/check_nextcloud_update

# Changelog
# 2018-08-05 added optional perfdata output ("-p" ) - doctore74 <doc@snowheaven.de>
# 2018-09-30 added optional clickable output via href ("-c" ) - doctore74 <doc@snowheaven.de>

$shortopts  = "";
$shortopts .= "H:";  // Hostname
$shortopts .= "S";   // SSL
$shortopts .= "p";   // perfdata output
$shortopts .= "c";   // clickable output via href
$longopts = array(
  "help"
);

$options = getopt($shortopts, $longopts);

if(array_key_exists("help",$options)){
  echo "HELP:\n";
  echo " -H hostname (required)\n";
  echo " -S SSL\n";
  echo " -p perfdata output\n";
  echo " -c clickable output via href\n";

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
$nextcloud_status_url = "$nextcloud_server/status.php";


function error($e){
  echo "Something went wrong: $e";
  exit(3);
}

function get_newest_version($nextcloud_releases="https://nextcloud.com/changelog/"){
  $return=false;
  try {
    $homepage = file_get_contents($nextcloud_releases);
    if($homepage === false){
      error("No Content");
    }
  }catch(Exception $e){
    error($e);
  }
  # searching the first nextcloud-#.#.#.zip on the page
    if(preg_match('/.*?>nextcloud-(\d+\.\d+\.\d+)\.zip<.*/',$homepage,$res)){
      if(is_array($res)){
        return $res[1];
      }
    }

  return false;
}

function get_installed_version($nextcloud_status_url){
  $res=false;


  $arrContextOptions=array(
    "ssl"=>array(
      "verify_peer"=>false,
      "verify_peer_name"=>false,
    ),
  );


  try{
    $status = file_get_contents($nextcloud_status_url, false, stream_context_create($arrContextOptions));
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
$actual=get_installed_version($nextcloud_status_url);

# perfdata
if(array_key_exists("p",$options)){
  $perfdata = "running=".str_replace(".", "", $actual)." stable=".str_replace(".", "", $newer);
}
else {
  $perfdata = "";
}

# output
if (version_compare($newer,$actual,"eq")) {
  if (array_key_exists("c",$options)) {
    echo "<a href='$nextcloud_server' target=_blank>Current version is ($actual). (channel: stable, version: $newer)|$perfdata</a>";
  } else {
    echo "Current version is ($actual). (channel: stable, version: $newer)|$perfdata";
  }
  exit(0);
} else {
  if (array_key_exists("c",$options)) {
    echo "<a href='$nextcloud_server' target=_blank>Current version is ($actual). Update to Nextcloud $newer available. (channel: stable)|$perfdata</a>";
  } else {
    echo "Current version is ($actual). Update to Nextcloud $newer available. (channel: stable)|$perfdata";
  }
  exit(1);
}
