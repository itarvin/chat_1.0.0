create database chat default charset=utf8;
use chat;


DROP TABLE IF EXISTS `chat_user`;
CREATE TABLE `chat_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '' COMMENT '用户名',
  `avatar` varchar(128) NOT NULL DEFAULT '' COMMENT '头像',
  `sign` varchar(255) NOT NULL DEFAULT '' COMMENT '签名',
  `online` varchar(10) NOT NULL DEFAULT 'online' COMMENT '是否在线',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户表';

-- alter table chat_user add `online` varchar(10) NOT NULL DEFAULT 'online' COMMENT '是否在线';

DROP TABLE IF EXISTS `chat_group`;
CREATE TABLE `chat_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(64) NOT NULL DEFAULT '' COMMENT '组名',
  `avatar` varchar(128) NOT NULL DEFAULT '' COMMENT '头像',
  `user_id` varchar(128) NOT NULL DEFAULT '' COMMENT '用户ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户组';

-- alter table chat_group add `user_id` varchar(128) NOT NULL DEFAULT '' COMMENT '用户ID';

DROP TABLE IF EXISTS `chat_crowd`;
CREATE TABLE `chat_crowd` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(64) NOT NULL DEFAULT '' COMMENT '组名',
  `avatar` varchar(128) NOT NULL DEFAULT '' COMMENT '头像',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户群';

DROP TABLE IF EXISTS `chat_group_user`;
CREATE TABLE `chat_group_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `g_id` varchar(12) NOT NULL DEFAULT '' COMMENT '用户组',
  `u_id` varchar(12) NOT NULL DEFAULT '' COMMENT '用户',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='组成员';


DROP TABLE IF EXISTS `chat_friend`;
CREATE TABLE `chat_friend` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `u_id` int unsigned NOT NULL DEFAULT '' COMMENT '用户ID',
  `f_id` int unsigned NOT NULL DEFAULT '' COMMENT '朋友ID',
  PRIMARY KEY (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='朋友';


DROP TABLE IF EXISTS `chat_chat`;
CREATE TABLE `i_chat` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `fromid` int(5) NOT NULL COMMENT '发送id',
  `fromname` varchar(50) NOT NULL COMMENT '发送名称',
  `toid` int(5) NOT NULL COMMENT '接收id',
  `toname` varchar(50) NOT NULL COMMENT '接收名称',
  `content` text NOT NULL COMMENT '内容',
  `addtime` datetime DEFAULT NULL COMMENT '加入时间',
  `isread` tinyint(2) DEFAULT '0' COMMENT '是否阅读',
  `type` tinyint(2) DEFAULT '1' COMMENT '1是普通文本，2是图片',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
