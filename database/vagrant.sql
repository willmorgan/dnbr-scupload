CREATE DATABASE  IF NOT EXISTS `vagrant` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `vagrant`;

DROP TABLE IF EXISTS `scupload_track_queue`;

CREATE TABLE `scupload_track_queue` (
	id varchar(64) primary key,
	coalesce_id varchar(128),
	sequence int(11) auto_increment unique,
	mutex tinyint(1) not null default "0",
	state varchar(255) not null,
	object BLOB not null
) ENGINE=InnoDB AUTO_INCREMENT=1;
