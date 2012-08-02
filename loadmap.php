<?php
//Initialize MongoDB
$m = new Mongo();
$db = $m->neighborhoodmapper;
$hoods = $db->usersets;

$mapid = $_GET['k'];
$mapdata = $hoods->findOne(array('key' => $mapid));
unset($mapdata['_id']);
echo json_encode($mapdata);
?>
