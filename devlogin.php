<?php
session_start();
if (isset($_SESSION['auth_characterid'])) {
    echo "Logged in. ".$_SESSION['auth_characterid'];
    exit;
} else {
    //Throw login redirect.
    $authsite='https://sisilogin.testeveonline.com';
    $authurl='/oauth/authorize';
    $client_id='3rdparty_fuzzwork';
    $redirect_uri="https://www.fuzzwork.co.uk/auth/devauthcallback.php";
    $state=uniqid();
    $redirect_to="https://www.fuzzwork.co.uk/auth/whoami.php";
    $_SESSION['auth_state']=$state;
    $_SESSION['auth_redirect']=$redirect_to;
    session_write_close();
    header(
        'Location:'.$authsite.$authurl
        .'?response_type=code&redirect_uri='.$redirect_uri
        .'&client_id='.$client_id.'&scope=&state='.$state
    );
    exit;
}
