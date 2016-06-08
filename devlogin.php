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
    $redirect_uri="https%3A%2F%2Fwww.fuzzwork.co.uk%2Fauth%2Fdevauthcallback.php";
    $state=uniqid();

    $redirecturl=$_SERVER['HTTP_REFERER'];
    
    if (!preg_match("#^https://www.fuzzwork.co.uk/(.*)$#", $redirecturl, $matches)) {
        $redirecturl='/';
    } else {
        $redirecturl=$matches[1];
    }

    $redirect_to="https://www.fuzzwork.co.uk/".$redirecturl;
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
