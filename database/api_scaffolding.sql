CREATE DATABASE IF NOT EXISTS api_scaffolding_yii11 DEFAULT CHARSET utf8 COLLATE utf8_general_ci;

CREATE TABLE `sys_code_auth_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` bigint(20) NOT NULL COMMENT '用户id',
  `code` varchar(32) NOT NULL COMMENT '验证码',
  `status` tinyint(4) NOT NULL COMMENT '状态 0未使用 1验证成功 2验证失败',
  `add_time` int(10) NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='code验证码记录表';

CREATE TABLE `sys_msg_auth_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL COMMENT '电话',
  `send_time` int(10) NOT NULL COMMENT '发送时间',
  `code` char(32) DEFAULT NULL COMMENT '发送代码',
  `ip` varchar(32) DEFAULT NULL COMMENT '验证码请求发送的IP',
  `failed_count` int(10) NOT NULL DEFAULT '0' COMMENT '验证失败次数',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0未使用，1已使用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='短信验证码记录表';

CREATE TABLE `sys_user_basic` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '用户UID',
  `account` varchar(64) DEFAULT NULL COMMENT '账号',
  `user_type` tinyint(4) DEFAULT NULL COMMENT '用户类型',
  `password` varchar(64) DEFAULT NULL COMMENT '密码',
  `salt` varchar(32) DEFAULT NULL COMMENT '密码盐',
  `name` varchar(64) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(120) DEFAULT NULL COMMENT '头像',
  `sex` tinyint(4) DEFAULT NULL COMMENT '性别(1:男,2:女)',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机',
  `email` varchar(64) DEFAULT NULL COMMENT '邮箱',
  `email_verify` tinyint(4) DEFAULT '0' COMMENT '是否验证邮箱(0:未验证,1:已验证)',
  `city` int(10) DEFAULT NULL COMMENT '所在城市',
  `add_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `last_login` int(10) DEFAULT '0' COMMENT '最后登录时间',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态(是否有效,0:无效,1:有效)',
  `failed_count` int(10) DEFAULT '0' COMMENT '登录失败次数',
  `lock_expired_time` int(10) DEFAULT '0' COMMENT '账号锁定到期时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='基础用户表';

CREATE TABLE `sys_user_hr` (
  `user_id` bigint(20) NOT NULL COMMENT '用户ID',
  `company_name` varchar(128) DEFAULT NULL COMMENT '公司名称',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='扩展HR用户表';

CREATE TABLE `sys_user_weixin` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `wxid` varchar(32) DEFAULT NULL COMMENT '微信OPENID',
  `unionid` varchar(32) DEFAULT NULL COMMENT '微信UNIONID',
  `nick_name` varchar(128) DEFAULT NULL COMMENT '微信昵称',
  `avatar` varchar(256) DEFAULT NULL COMMENT '微信头像URL',
  `sex` tinyint(4) DEFAULT '0' COMMENT '性别(1：男,2:女)',
  `country` varchar(128) DEFAULT NULL COMMENT '国家',
  `province` varchar(128) DEFAULT NULL COMMENT '省份',
  `city` varchar(128) DEFAULT NULL COMMENT '城市',
  `language` varchar(64) DEFAULT NULL COMMENT '语言',
  `subscribe` tinyint(4) DEFAULT '0' COMMENT '是否关注(0:默认，1：关注，2：取消关注)',
  `subscribe_time` int(10) DEFAULT '0' COMMENT '关注时间',
  `bind_uid` bigint(20) DEFAULT '0' COMMENT '绑定的用户ID',
  `add_time` int(10) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信用户表';

CREATE TABLE `user_auths` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL COMMENT '平台用户id',
  `app_type` tinyint(4) DEFAULT NULL COMMENT '第三方类型1微信2QQ3新浪微博',
  `app_user_id` varchar(255) DEFAULT '' COMMENT '第三方用户唯一标示',
  `access_token` varchar(255) DEFAULT NULL COMMENT '第三方access_tocken',
  `nickname` varchar(255) DEFAULT '' COMMENT '第三方昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '第三方头像',
  `add_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `is_bind` tinyint(4) DEFAULT '0' COMMENT '是否绑定平台账户',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='第三方用户登录';

CREATE TABLE `sys_static` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '类别代号',
  `value` varchar(128) NOT NULL DEFAULT '' COMMENT '实际值',
  `label` varchar(256) NOT NULL DEFAULT '' COMMENT '显示值',
  `priority` int(10) DEFAULT '0' COMMENT '优先级',
  `add_time` int(10) NOT NULL DEFAULT '0' COMMENT '建立日期',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态(是否有效,0:无效,1:有效) ',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=361 DEFAULT CHARSET=utf8 COMMENT='静态配置表';