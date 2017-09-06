<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}


// On veut supprimer la table 'debampass' lors de la suppression du plugin
global $wpdb;

$tableDeBamPass = $wpdb->prefix ."debampass";
$queryDelete = "DROP TABLE $tableDeBamPass";
$wpdb->query($queryDelete);

// On veut supprimer le dossier 'exports'
$directoryName = "/exports/";
$uploadExportsPath = realpath(dirname(__FILE__)) . $directoryName;

delTree($uploadExportsPath);

function delTree($dir)
{
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $aFile) {
		(is_dir("$dir/$aFile")) ? delTree("$dir/$aFile") : unlink("$dir/$aFile");
	}
	
	return rmdir($dir); 
}
