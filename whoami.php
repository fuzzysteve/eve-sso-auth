<?php
session_start();
if (isset($_SESSION['auth_characterid'])) {
  echo $_SESSION['auth_characterid'].'<br>';
  echo $_SESSION['auth_charactername'].'<br>';
  echo $_SESSION['auth_characterhash'].'<br>';
  echo $_SESSION['auth_userdetails'].'<br>';
} else {
  echo "You don't appear to be logged in.";
}
