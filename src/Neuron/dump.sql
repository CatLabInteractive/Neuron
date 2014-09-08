-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb1
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Genereertijd: 02 apr 2013 om 15:54
-- Serverversie: 5.5.29
-- PHP-Versie: 5.4.6-1ubuntu1.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Databank: `bmgroup_test`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `accounts`
--

CREATE TABLE IF NOT EXISTS `accounts` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_id` int(11) NOT NULL,
  `a_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `a_data` text COLLATE utf8_unicode_ci NOT NULL,
  `a_foreign_id` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`a_id`),
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `u_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_email` varchar(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `u_password` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `u_salt` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `u_name` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `u_firstname` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`u_id`),
  UNIQUE KEY `u_email` (`u_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users_email`
--

CREATE TABLE IF NOT EXISTS `users_email` (
  `ue_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_id` int(11) NOT NULL,
  `ue_email` varchar(250) NOT NULL,
  `ue_code` varchar(20) NOT NULL,
  `ue_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ue_id`),
  KEY `u_id` (`u_id`),
  KEY `ue_email` (`ue_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users_passwordreset`
--

CREATE TABLE IF NOT EXISTS `users_passwordreset` (
  `upw_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_id` int(11) NOT NULL,
  `upw_token` varbinary(20) NOT NULL,
  `upw_date` datetime NOT NULL,
  `upw_ip` binary(16) NOT NULL,
  PRIMARY KEY (`upw_id`),
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Beperkingen voor gedumpte tabellen
--

--
-- Beperkingen voor tabel `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`);

--
-- Beperkingen voor tabel `users_email`
--
ALTER TABLE `users_email`
  ADD CONSTRAINT `users_email_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`);

--
-- Beperkingen voor tabel `users_passwordreset`
--
ALTER TABLE `users_passwordreset`
  ADD CONSTRAINT `users_passwordreset_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`);
