<?php
session_start();
echo $_SESSION['auth_characterid'].'<br>';
echo $_SESSION['auth_charactername'].'<br>';
echo $_SESSION['auth_characterhash'].'<br>';
echo $_SESSION['auth_userdetails'].'<br>';
