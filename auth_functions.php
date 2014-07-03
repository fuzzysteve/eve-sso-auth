<?php
function auth_error($error_message)
{
    print "There's been an error";
    error_log($error_message);
    exit();
}
