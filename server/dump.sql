
SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
`room_id`  int(11) NOT NULL AUTO_INCREMENT ,
`agent`  varchar(255) NULL ,
`visitor`  varchar(255) NULL ,
`agenturl`  varchar(2048) NULL ,
`visitorurl`  varchar(2048) NULL ,
`password`  varchar(255) NULL ,
`roomId`  varchar(255) NULL ,
`datetime`  varchar(255) NULL ,
`duration`  varchar(255) NULL ,
PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


