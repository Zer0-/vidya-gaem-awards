<?php

$result = SteamSignIn::validate();
if (strlen($result) > 0) {
  $_SESSION['login'] = $result;
  
  $info = getAPIinfo($result);
  $_SESSION['name'] = $info->personaname;
  $_SESSION['avatar'] = $info->avatar;

  mysql_query("INSERT INTO `logins` (`ID`, `UserID`, `Timestamp`) VALUES (0, \"$result\", NOW())");
  
  $name = mysql_real_escape_string($info->personaname);
  $avatar = mysql_real_escape_string(base64_encode(file_get_contents($info->avatar)));
  $mysql = mysql_query("SELECT `SteamID` FROM `users` WHERE `SteamID` = \"$result\"");
  if (mysql_num_rows($mysql) == 0) {
    //echo "New user";
    $query = "INSERT INTO `users` (`SteamID`, `Name`, `FirstLogin`, `LastLogin`, `Avatar`) VALUES (\"$result\", \"$name\", NOW(), NOW(), \"$avatar\")";
  } else {
    //echo "Returning user";
    $query = "UPDATE `users` SET `Name` = \"$name\", `Avatar` = \"$avatar\", `LastLogin` = NOW() WHERE `SteamID` = \"$result\"";
  }
  $result = mysql_query($query);
  if (!$result) {
    echo "Sorry, there was an error in processing your login.<br />".mysql_error();
    die();
  }
  
  // Thanks to http://stackoverflow.com/questions/5009685/encoding-cookies-so-they-cannot-be-spoofed-or-read-etc/5009903#5009903
  $randomToken = hash('sha256',uniqid(mt_rand(), true).uniqid(mt_rand(), true));
  $randomToken .= ':'.hash_hmac('md5', $randomToken, $APIkey);
  setcookie("token", $randomToken, time()+60*60*24*30, "/", $domain);
  
  $avatar = mysql_real_escape_string($info->avatar);
  $query =  "REPLACE INTO `login_tokens` (`UserID`, `Name`, `Avatar`, `Token`, `Generated`, `Expires`) ";
  $query .= "VALUES(\"{$_SESSION['login']}\", \"$name\", \"$avatar\", \"$randomToken\", NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";
  mysql_query($query);
  
}

$return = rtrim(implode("/", array_slice($SEGMENTS, 1)), "/");

header("Location: http://$domain/$return");
?>