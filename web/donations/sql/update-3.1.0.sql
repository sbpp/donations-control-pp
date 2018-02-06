CREATE TABLE IF NOT EXISTS `promotions` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `type` int(1) NOT NULL,
  `amount` int(8) NOT NULL,
  `days` int(8) NOT NULL DEFAULT '0',
  `number` int(8) NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL,
  `descript` varchar(128) NOT NULL,
  `timestamp` int(64) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `promotions_redeemed`
--

CREATE TABLE IF NOT EXISTS `promotions_redeemed` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `promo_id` int(8) NOT NULL,
  `promo_code` varchar(32) NOT NULL,
  `steam_id` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;  