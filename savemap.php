<?php
//Initialize MongoDB
$m = new Mongo();
$db = $m->neighborhoodmapper;
$hoods = $db->usersets;

function genKey($int, $padding = 3) {
    $letterset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $base = strlen($letterset);
    $out = '';
    for($curpow = floor(log($int, $base)); $curpow >= 0; $curpow--) {
        $curval = pow($base, $curpow);
        $curpart = floor($int/$curval);
        $int -= $curpart * $curval;
        $out .= $letterset[$curpart];
    }
    //Pad to three characters
    for($curpad = strlen($out); $curpad <= $padding; $curpad++) {
        $out = "a$out";
    }
    return $out;
}

$data = json_decode($_POST['mapdata'],true);
$data['version'] = '1';

if(empty($data['key'])) {
    //If it is new, save it to the db
    $success = false;
    $jump_ahead = 0;
    $data['created'] = time();
    while($success == false && $jump_ahead < 50) {
        $jump_ahead++;
        $link = $hoods->find()->sort(array('index' => -1))->limit(1);
        $mostrecent = $link->hasNext() ? $link->getNext() : array('index' => 1);

        $data['index'] = $mostrecent['index'] + $jump_ahead;
        $data['key'] = genKey($data['index']);
        try {
            $status = $hoods->insert($data, array('safe' => true));
            $success = ($status['err'] == null);
        } catch(MongoCursorException $e) {
            $success = false;
        } catch(MongoCursorTimeoutException $e) {
            $success = false;
        }
    }
} else {
    //This might screw with stuff, so we'll just take it out
    unset($data['_id']);
    $data['modified'] = time();
    try{ 
        $status = $hoods->update(
            array('key' => $data['key']),
            $data,
            array('safe' => true, 'upsert' => true)
        );
        $success = ($status['err'] == null);
    } catch (MongoCursorException $e) {
        $success = false;
    }
}

echo json_encode(array(
    'success' => $success,
    'savedata' => $data,
    'status' => $status,
));
?>
