eve-sso-auth
============

Basic PHP code for setting up a session and database entry for usersy

requires a database and the CURL extension.

 CREATE TABLE `alliance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allianceid` int(11) DEFAULT NULL,
  `alliancename` varchar(255) DEFAULT NULL,
  `allianceticker` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8

 CREATE TABLE `corporation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corporationid` int(11) DEFAULT NULL,
  `corporationname` varchar(255) DEFAULT NULL,
  `corporationticker` varchar(10) DEFAULT NULL,
  `allianceid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 
 CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `characterid` int(11) DEFAULT NULL,
  `characterownerhash` varchar(255) DEFAULT NULL,
  `character_name` varchar(255) DEFAULT NULL,
  `corporationid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
