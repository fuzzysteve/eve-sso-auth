<?php
require_once('auth_functions.php');
require_once('secret.php');
session_start();

$useragent="Fuzzwork Auth agent.";

// Make sure that the secret matches the one set before the redirect.
if (isset($_SESSION['auth_state']) and isset($_GET['state']) and $_SESSION['auth_state']==$_GET['state']) {
    $code=$_GET['code'];
    $state=$_GET['state'];


    //Do the initial check.
    $url='https://login.eveonline.com/oauth/token';
    $verify_url='https://login.eveonline.com/oauth/verify';
    $header='Authorization: Basic '.base64_encode($clientid.':'.$secret);
    $fields_string='';
    $fields=array(
                'grant_type' => 'authorization_code',
                'code' => $code
            );
    foreach ($fields as $key => $value) {
        $fields_string .= $key.'='.$value.'&';
    }
    rtrim($fields_string, '&');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $result = curl_exec($ch);

    if ($result===false) {
        auth_error(curl_error($ch));
    }
    curl_close($ch);
    $response=json_decode($result);
    $auth_token=$response->access_token;
    $ch = curl_init();

// Get the Character details from SSO

    $header='Authorization: Bearer '.$auth_token;
    curl_setopt($ch, CURLOPT_URL, $verify_url);
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $result = curl_exec($ch);
    if ($result===false) {
        auth_error(curl_error($ch));
    }
    curl_close($ch);
    $response=json_decode($result);


    if (!isset($response->CharacterID)) {
        auth_error('No character ID returned');
    }

// Lookup the character details in the DB.
    require_once('db.inc.php');
    $sql="select corporationname,corporationticker,user.corporationid,
    alliancename,allianceticker,corporation.allianceid,characterid,characterownerhash,
    user.id
    from user 
    join corporation on user.corporationid=corporation.corporationid
    join alliance on corporation.allianceid=alliance.allianceid
    where
    user.characterid=:characterid
    and characterownerhash=:characterhash
    ";

    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(':characterid'=>$response->CharacterID,':characterhash'=>$response->CharacterOwnerHash));

    while ($row = $stmt->fetchObject()) {
        $userdetails=$row;
        $userid=$row->id;
    }

// Fill in character details, if they're not in the DB

    if (!isset($userdetails)) {
        // No database entry for the user. lookup time.
        error_log('Creating user details');
        $ch = curl_init();
        $lookup_url="https://esi.evetech.net/latest/characters/".$response->CharacterID."/";
        curl_setopt($ch, CURLOPT_URL, $lookup_url);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result===false) {
            auth_error('No such character on the API');
        }
        $chardetails=json_decode($result);
        $corporationID=$chardetails->corporation_id;
        $allianceID=$chardetails->alliance_id;
        //Alliance
        if ($allianceID!=0) {
            $alliancesql='select allianceid,allianceticker,alliancename from alliance where allianceid=:allianceid';
            $stmt = $dbh->prepare($alliancesql);
            $stmt->execute(array(':allianceid'=>$allianceID));
            while ($row = $stmt->fetchObject()) {
                $allianceticker=$row->allianceticker;
                $allianceName=$row->alliancename;
            }
            if (!isset($allianceticker)) {
                error_log('Getting alliance details');
                $alliance_url='https://esi.evetech.net/latest/alliances/'.$allianceID.'/';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $alliance_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                $result = curl_exec($ch);
                curl_close($ch);
                $alliance_data=json_decode($result);
                $allianceticker=$alliance_data->ticker;
                $allianceName=$alliance_data->name;
                $alliance_insert_sql="insert into alliance (allianceid,alliancename,allianceticker) 
                    values (:allianceid,:alliancename,:allianceticker)";
                $stmt = $dbh->prepare($alliance_insert_sql);
                $stmt->execute(
                    array(
                    ':allianceid'=>$allianceID,
                    ':alliancename'=>$allianceName,
                    ':allianceticker'=>$allianceticker)
                );
            }

        } else {
            $allianceName="No Alliance";
            $allianceTicker="";
        }
        $userdetails['allianceid']=$allianceID;
        $userdetails['alliancename']=$allianceName;
        $userdetails['allianceticker']=$allianceticker;

        // Corporation
        $corporationsql='select corporationid,corporationticker,corporationname from corporation where corporationid=:corporationid';
        $stmt = $dbh->prepare($corporationsql);
        $stmt->execute(array(':corporationid'=>$corporationID));
        while ($row = $stmt->fetchObject()) {
            $corporationticker=$row->corporationid;
            $corporationName=$row->corporationname;
        }
        if (!isset($corporationticker)) {
            error_log('Getting corporation details');
            $corporation_url="https://esi.evetech.net/latest/corporations/".$corporationID."/";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $corporation_url);
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $result = curl_exec($ch);
            curl_close($ch);
            $corpjson=json_decode($result);
            $corporationticker=$corpjson->ticker;
            $corporationName=$corpjson->name;
            $corporation_insert_sql="insert into corporation
                (corporationid,corporationname,corporationticker,allianceid)
                values (:corporationid,:corporationname,:corporationticker,:allianceid)";
            $stmt = $dbh->prepare($corporation_insert_sql);
            $stmt->execute(
                array(
                ':corporationid'=>$corporationID,
                ':corporationname'=>$corporationName,
                ':corporationticker'=>$corporationticker,
                ':allianceid'=>$allianceID
                )
            );
        }
        $userdetails['corporationid']=$corporationID;
        $userdetails['corporationname']=$corporationName;
        $userdetails['corporationticker']=$corporationticker;
        $user_creation_sql='insert into user (characterid,characterownerhash,character_name,corporationid)
            values (:characterid,:characterownerhash,:character_name,:corporationid)';
        $stmt = $dbh->prepare($user_creation_sql);
        $stmt->execute(
            array(
            ':characterid'=>$response->CharacterID,
            ':characterownerhash'=>$response->CharacterOwnerHash,
            ':character_name'=>$response->CharacterName,
            ':corporationid'=>$corporationID
            )
        );
        $userid=$dbh->lastInsertId();
        $userdetails['id']=$userid;

        error_log("user added to db");
    }

    $_SESSION['auth_characterid']=$response->CharacterID;
    $_SESSION['auth_id']=$userid;
    $_SESSION['auth_charactername']=$response->CharacterName;
    $_SESSION['auth_userdetails']=json_encode($userdetails);
    $_SESSION['auth_characterhash']=$response->CharacterOwnerHash;
    session_write_close();
    header('Location:'. $_SESSION['auth_redirect']);
    
    exit;

} else {
    echo "State is wrong. Did you make sure to actually hit the login url first?";
    error_log($_SESSION['auth_state']);
    error_log($_GET['state']);
}
