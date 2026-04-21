SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for light_auth_access
-- ----------------------------
DROP TABLE IF EXISTS `light_auth_access`;
CREATE TABLE `light_auth_access` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL DEFAULT '0' COMMENT '瑙掕壊id',
  `menu_id` int(10) NOT NULL DEFAULT '0' COMMENT '鑿滃崟id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5751 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鏉冮檺鑿滃崟鍏宠仈琛?;

-- ----------------------------
-- Records of light_auth_access
-- ----------------------------
BEGIN;
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5167, 3, 2);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5168, 3, 1);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5169, 3, 28);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5170, 3, 14);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5171, 3, 29);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5172, 3, 30);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5173, 3, 77);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5174, 3, 114);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5175, 3, 106);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5176, 3, 107);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5177, 3, 38);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5178, 3, 80);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5179, 3, 108);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5180, 3, 40);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5182, 3, 45);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5183, 3, 21);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5184, 3, 46);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5185, 3, 47);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5186, 3, 81);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5187, 3, 53);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5188, 3, 50);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5189, 3, 54);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5190, 3, 82);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5191, 3, 110);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5192, 3, 36);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5193, 3, 32);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5194, 3, 78);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5195, 3, 122);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5196, 3, 13);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5197, 3, 113);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5198, 3, 105);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5199, 3, 16);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5200, 3, 20);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5201, 3, 121);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5579, 1, 107);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5580, 1, 106);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5581, 1, 119);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5582, 1, 114);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5583, 1, 120);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5584, 1, 116);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5585, 1, 130);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5586, 1, 38);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5587, 1, 108);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5588, 1, 80);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5590, 1, 40);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5592, 1, 81);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5593, 1, 45);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5594, 1, 46);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5595, 1, 47);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5596, 1, 110);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5597, 1, 82);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5598, 1, 53);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5599, 1, 54);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5600, 1, 78);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5601, 1, 36);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5602, 1, 105);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5603, 1, 113);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5604, 1, 32);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5605, 1, 16);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5606, 1, 20);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5607, 1, 21);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5608, 1, 50);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5609, 1, 156);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5611, 1, 14);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5612, 1, 77);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5613, 1, 28);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5614, 1, 29);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5615, 1, 30);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5616, 1, 13);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5684, 1, 236);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5686, 2, 107);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5687, 2, 119);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5688, 2, 114);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5689, 2, 80);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5690, 2, 40);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5692, 2, 81);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5693, 2, 45);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5694, 2, 46);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5695, 2, 47);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5696, 2, 36);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5697, 2, 78);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5698, 2, 32);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5699, 2, 21);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5700, 2, 106);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5701, 2, 105);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5702, 2, 113);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5703, 2, 20);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5704, 2, 156);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5705, 2, 13);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5706, 5, 156);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5707, 5, 105);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5708, 5, 113);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5709, 5, 106);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5710, 5, 119);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5711, 5, 107);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5712, 5, 120);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5713, 5, 114);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5714, 5, 116);
INSERT INTO `light_auth_access` (`id`, `role_id`, `menu_id`) VALUES (5715, 5, 13);
COMMIT;

-- ----------------------------
-- Table structure for light_department
-- ----------------------------
DROP TABLE IF EXISTS `light_department`;
CREATE TABLE `light_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '閮ㄩ棬鍚嶇О',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '鐖剁骇閮ㄩ棬',
  `sort` smallint(5) NOT NULL DEFAULT '8' COMMENT '閮ㄩ棬鎺掑簭',
  `leader_id` int(11) NOT NULL DEFAULT '0' COMMENT '閮ㄩ棬璐熻矗浜?,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='閮ㄩ棬琛?;

-- ----------------------------
-- Records of light_department
-- ----------------------------
BEGIN;
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (1, '浜轰簨閮?, 4, 8, 9);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (2, '璐㈠姟閮?, 4, 8, 22);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (3, '鎶€鏈儴', 4, 8, 11);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (4, '鎬诲叕鍙?, 0, 8, 18);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (5, '甯傚満閮?, 4, 8, 1);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (7, '涓婃捣鍒嗗叕鍙?, 0, 8, 1);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (8, '骞夸笢鍒嗗叕鍙?, 0, 8, 1);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (13, '鍖椾含鍒嗗叕鍙?, 0, 8, 0);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (14, '浜轰簨閮?, 7, 8, 0);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (15, '鐮斿彂閮?, 4, 8, 0);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (16, '鎶€鏈儴', 7, 8, 0);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (17, '寮€鍙戦儴', 8, 8, 0);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (18, '浜轰簨閮?, 8, 8, 22);
INSERT INTO `light_department` (`id`, `name`, `parent_id`, `sort`, `leader_id`) VALUES (19, '浜轰簨閮?, 13, 8, 20);
COMMIT;

-- ----------------------------
-- Table structure for light_dict
-- ----------------------------
DROP TABLE IF EXISTS `light_dict`;
CREATE TABLE `light_dict` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '瀛楀吀绫诲瀷',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '涓枃鍚嶇О',
  `value` varchar(30) NOT NULL DEFAULT '' COMMENT '瀛楀吀灞炴€у€?,
  `sort` smallint(5) NOT NULL DEFAULT '8' COMMENT '鎺掑簭',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '澶囨敞',
  `color` varchar(10) NOT NULL DEFAULT '' COMMENT '瀛楀吀缁勪欢棰滆壊',
  `widget_type` varchar(10) NOT NULL DEFAULT '' COMMENT '瀛楀吀缁勪欢绫诲瀷',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '鏄惁鍒犻櫎锛?鏈垹闄わ紝1宸插垹闄?,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '鐘舵€侊紝1鍚敤锛?绂佺敤',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=562 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='瀛楀吀琛?;

-- ----------------------------
-- Records of light_dict
-- ----------------------------
BEGIN;
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (75, 'dict_type', '瀛楀吀绫诲瀷', 'dict_type', 8, '', '', '', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (250, 'dict_type', '鑿滃崟绫诲瀷', 'menu_type', 8, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (251, 'menu_type', '鐩綍', '0', 2, '', 'green', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (252, 'menu_type', '鑿滃崟', '1', 1, '', 'blue', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (253, 'menu_type', '鏉冮檺', '2', 3, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (429, 'gender', '鏈煡', '0', 3, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (431, 'gender', '鐢?, '1', 1, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (432, 'gender', '濂?, '2', 2, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (489, 'dict_type', '璁惧绫诲瀷', 'device_type', 8, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (490, 'dict_type', '璁惧绾у埆', 'device_level', 8, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (495, 'dict_type', '宀椾綅', 'job_post', 8, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (496, 'dict_type', '鑱岀О', 'job_title', 8, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (498, 'dict_type', '鎬у埆', 'gender', 8, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (526, 'device_type', '涓€鑸澶?, '3', 2, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (528, 'device_type', '鐢熶骇璁惧', '5', 3, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (529, 'device_type', '閲嶈璁惧', '6', 1, '', '', 'text', 1, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (531, 'device_level', '涓€绾?, '1', 1, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (532, 'device_level', '浜岀骇', '2', 2, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (535, 'device_level', '涓夌骇', '6', 5, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (537, 'job_post', '鎶€鏈矖', '2', 2, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (538, 'job_post', '鏅€氬矖', '3', 1, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (539, 'job_post', '绠＄悊宀?, '4', 3, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (541, 'job_title', '鍒濈骇', '1', 4, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (542, 'job_title', '涓骇', '2', 2, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (543, 'job_title', '鍓珮绾?, '3', 3, '', '', 'text', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (544, 'job_title', '姝ｉ珮绾?, '4', 1, '', '', 'text', 0, 1);
COMMIT;

-- ----------------------------
-- Table structure for light_file
-- ----------------------------
DROP TABLE IF EXISTS `light_file`;
CREATE TABLE `light_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL COMMENT '鏂囦欢鍦板潃',
  `mime_type` varchar(100) NOT NULL COMMENT 'mime绫诲瀷',
  `file_ext` varchar(30) NOT NULL COMMENT '鏂囦欢鎵╁睍鍚?,
  `file_size` int(11) NOT NULL DEFAULT '0' COMMENT '鏂囦欢澶у皬',
  `filename` varchar(255) NOT NULL COMMENT '鏂囦欢鍚嶇О',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '涓婁紶鏃堕棿',
  `user_id` varchar(30) NOT NULL DEFAULT '' COMMENT '涓婁紶鐢ㄦ埛id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鏂囦欢琛?;

-- ----------------------------
-- Table structure for light_login_log
-- ----------------------------
DROP TABLE IF EXISTS `light_login_log`;
CREATE TABLE `light_login_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(30) NOT NULL DEFAULT '' COMMENT '鐧诲綍璐﹀彿',
  `login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '鐧诲綍ip',
  `browser` varchar(20) NOT NULL DEFAULT '' COMMENT '娴忚鍣?,
  `os` varchar(20) NOT NULL DEFAULT '' COMMENT '鎿嶄綔绯荤粺',
  `login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鐧诲綍鏃堕棿',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '鐢ㄦ埛id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鐧诲綍鏃ュ織琛?;

-- ----------------------------
-- Table structure for light_system_setting
-- ----------------------------
DROP TABLE IF EXISTS `light_system_setting`;
CREATE TABLE `light_system_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `system_name` varchar(100) NOT NULL DEFAULT '' COMMENT '绯荤粺鍚嶇О',
  `logo` varchar(500) NOT NULL DEFAULT '' COMMENT '绯荤粺Logo',
  `favicon` varchar(500) NOT NULL DEFAULT '' COMMENT '绔欑偣鍥炬爣',
  `homepage_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '棣栭〉寮€鍏?1寮€鍚?0鍏抽棴',
  `homepage_title` varchar(100) NOT NULL DEFAULT '' COMMENT '棣栭〉鏍囬',
  `homepage_intro` varchar(1000) NOT NULL DEFAULT '' COMMENT '棣栭〉绠€浠?,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鍒涘缓鏃堕棿',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鏇存柊鏃堕棿',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='绯荤粺璁剧疆琛?;

-- ----------------------------
-- Records of light_system_setting
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_menu
-- ----------------------------
DROP TABLE IF EXISTS `light_menu`;
CREATE TABLE `light_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鐖秈d',
  `path` varchar(100) NOT NULL DEFAULT '' COMMENT '璺敱璺緞',
  `component` varchar(255) NOT NULL DEFAULT '' COMMENT '璺敱缁勪欢',
  `hidden` tinyint(1) NOT NULL DEFAULT '0' COMMENT '鏄惁闅愯棌',
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '鑿滃崟鍚嶇О',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '鑿滃崟鍥炬爣',
  `rules` varchar(100) NOT NULL DEFAULT '' COMMENT '鏉冮檺鑺傜偣',
  `sort` smallint(5) NOT NULL DEFAULT '1' COMMENT '鎺掑簭',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 鐩綍锛?鑿滃崟锛?鏉冮檺',
  `hide_children` tinyint(1) NOT NULL DEFAULT '0' COMMENT '闅愯棌瀛愯彍鍗曪紝骞朵笖寮哄埗娓叉煋涓鸿彍鍗曢」',
  `active_key` varchar(255) NOT NULL DEFAULT '' COMMENT '鑿滃崟楂樹寒key',
  `open_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '鎵撳紑鏂瑰紡 0缁勪欢锛?鍐呴摼锛?澶栭摼',
  `link_url` varchar(500) NOT NULL DEFAULT '' COMMENT '鍐呴摼鍦板潃',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鑿滃崟琛?;

-- ----------------------------
-- Records of light_menu
-- ----------------------------
BEGIN;
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (13, 0, 'system', 'Layout', 0, '绯荤粺绠＄悊', 'setting-outlined', '', 5, 0, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (14, 13, 'menu', 'system/menu/index', 0, '鑿滃崟绠＄悊', 'menu-outlined', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (16, 13, 'department', 'system/department/index', 0, '閮ㄩ棬绠＄悊', 'apartment-outlined', '', 2, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (20, 13, 'user', 'system/user/index', 0, '鐢ㄦ埛绠＄悊', 'team-outlined', '', 3, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (21, 13, 'role', 'system/role/index', 0, '瑙掕壊绠＄悊', 'user-outlined', '', 4, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (28, 14, '', '', 0, '娣诲姞鑿滃崟', '', 'system:menu:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (29, 14, '', '', 0, '淇敼鑿滃崟', '', 'system:menu:update', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (30, 14, '', '', 0, '鍒犻櫎鑿滃崟', '', 'system:menu:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (32, 13, 'auth/:id', 'system/role/auth', 1, '鏉冮檺璁剧疆', 'insurance-outlined', '', 10, 1, 0, 'system/role', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (36, 32, '', '', 1, '淇濆瓨鏉冮檺', '', 'system:authAccess:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (38, 16, '', '', 0, '鍒犻櫎閮ㄩ棬', '', 'system:department:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (40, 20, '', '', 0, '鏇存柊鐢ㄦ埛', '', 'system:user:update', 2, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (45, 21, '', '', 0, '娣诲姞瑙掕壊', '', 'system:role:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (46, 21, '', '', 0, '淇敼瑙掕壊', '', 'system:role:update', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (47, 21, '', '', 0, '鍒犻櫎瑙掕壊', '', 'system:role:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (50, 13, 'dict', 'system/dict/index', 0, '瀛楀吀绠＄悊', 'deployment-unit-outlined', '', 5, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (53, 50, '', '', 0, '淇敼瀛楀吀', '', 'system:dict:update', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (54, 50, '', '', 0, '鍒犻櫎瀛楀吀', '', 'system:dict:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (77, 14, '', '', 0, '鏌ョ湅鍒楄〃', '', 'system:menu:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (78, 32, '', '', 1, '鏌ョ湅鏉冮檺', '', 'system:authAccess:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (80, 20, '', '', 0, '鏌ョ湅鍒楄〃', '', 'system:user:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (81, 21, '', '', 0, '鏌ョ湅鍒楄〃', '', 'system:role:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (82, 50, '', '', 0, '鏌ョ湅鍒楄〃', '', 'system:dict:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (105, 156, 'operate', 'system/logs/operate-log', 0, '鎿嶄綔鏃ュ織', 'profile-outlined', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (106, 105, '', '', 0, '鏌ョ湅鍒楄〃', '', 'system:operateLog:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (107, 105, '', '', 0, '娓呯┖鏃ュ織', '', 'system:operateLog:clear', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (108, 20, '', '', 0, '淇敼鐘舵€?, '', 'system:user:changeStatus', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (110, 50, '', '', 0, '娣诲姞瀛楀吀', '', 'system:dict:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (113, 156, 'login', 'system/logs/login-log', 0, '鐧诲綍鏃ュ織', 'diff-outlined', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (114, 113, '', '', 0, '鏌ョ湅鍒楄〃', '', 'system:loginLog:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (116, 113, '', '', 0, '娓呯┖鏃ュ織', '', 'system:loginLog:clear', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (119, 105, '', '', 0, '鍒犻櫎鏃ュ織', '', 'system:operateLog:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (120, 113, '', '', 0, '鍒犻櫎鏃ュ織', '', 'system:loginLog:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (130, 16, '', '', 0, '娣诲姞閮ㄩ棬', '', 'system:department:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (156, 13, 'log', 'RouteView', 0, '鏃ュ織绠＄悊', 'file-text-outlined', '', 1, 0, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (167, 165, 'filetype', 'system/login_log/index', 0, '闄勪欢绫诲瀷', '', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (236, 20, '', '', 0, '閲嶇疆瀵嗙爜', '', 'system:user:resetPassword', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (237, 20, '', '', 0, '鍒犻櫎鐢ㄦ埛', '', 'system:user:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (238, 13, 'setting', 'system/system-setting/index', 0, '绯荤粺璁剧疆', 'setting-outlined', '', 99, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (239, 238, '', '', 0, '鏌ョ湅閰嶇疆', '', 'system:systemsetting:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (240, 238, '', '', 0, '淇濆瓨閰嶇疆', '', 'system:systemsetting:update', 1, 2, 0, '', 0, '');
COMMIT;

-- ----------------------------
-- Table structure for light_operate_log
-- ----------------------------
DROP TABLE IF EXISTS `light_operate_log`;
CREATE TABLE `light_operate_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '妯″潡鍚嶇О',
  `operate` varchar(20) NOT NULL DEFAULT '' COMMENT '鎿嶄綔妯″潡',
  `route` varchar(100) NOT NULL DEFAULT '' COMMENT '璺敱',
  `params` varchar(1000) NOT NULL DEFAULT '' COMMENT '鍙傛暟',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'ip',
  `user_id` varchar(30) NOT NULL DEFAULT '' COMMENT '鎿嶄綔鐢ㄦ埛',
  `method` varchar(255) NOT NULL DEFAULT '' COMMENT '璇锋眰鏂规硶',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鐧诲綍鏃堕棿',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鎿嶄綔鏃ュ織';

-- ----------------------------
-- Records of light_operate_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_role
-- ----------------------------
DROP TABLE IF EXISTS `light_role`;
CREATE TABLE `light_role` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '鐘舵€?,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '鍚嶇О',
  `note` varchar(100) NOT NULL DEFAULT '' COMMENT '澶囨敞',
  `role_key` varchar(30) NOT NULL DEFAULT '' COMMENT '鏉冮檺鏍囪瘑',
  `data_range` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 鍏ㄩ儴鏁版嵁 2 鑷畾涔夋暟鎹?3 浠呮湰浜烘暟鎹?4 閮ㄩ棬鏁版嵁 5 閮ㄩ棬鍙婁互涓嬫暟鎹?,
  `delete_time` int(10) NOT NULL DEFAULT '0' COMMENT '鍒犻櫎鏃堕棿',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `role_key` (`role_key`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='瑙掕壊琛?;

-- ----------------------------
-- Records of light_role
-- ----------------------------
BEGIN;
INSERT INTO `light_role` (`id`, `status`, `name`, `note`, `role_key`, `data_range`, `delete_time`) VALUES (1, 1, '瓒呯骇绠＄悊鍛?, '鍐呯疆瑙掕壊锛屼笉鍙淮鎶?, 'super_admin', 1, 0);
INSERT INTO `light_role` (`id`, `status`, `name`, `note`, `role_key`, `data_range`, `delete_time`) VALUES (2, 1, '绠＄悊鍛?, '涓氬姟鏉冮檺', 'admin', 1, 0);
INSERT INTO `light_role` (`id`, `status`, `name`, `note`, `role_key`, `data_range`, `delete_time`) VALUES (3, 1, '鎬荤粡鐞?, '', 'general', 1, 0);
INSERT INTO `light_role` (`id`, `status`, `name`, `note`, `role_key`, `data_range`, `delete_time`) VALUES (5, 1, '閮ㄩ棬缁忕悊', '', 'manager', 1, 0);
COMMIT;

-- ----------------------------
-- Table structure for light_role_department
-- ----------------------------
DROP TABLE IF EXISTS `light_role_department`;
CREATE TABLE `light_role_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `dept_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC COMMENT='閮ㄩ棬瑙掕壊鍏宠仈琛?;

-- ----------------------------
-- Records of light_role_department
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_user
-- ----------------------------
DROP TABLE IF EXISTS `light_user`;
CREATE TABLE `light_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL COMMENT '鐢ㄦ埛鍚?,
  `password` varchar(255) NOT NULL COMMENT '瀵嗙爜',
  `realname` varchar(10) NOT NULL DEFAULT '' COMMENT '濮撳悕',
  `pinyin` varchar(10) NOT NULL DEFAULT '' COMMENT '鎷奸煶',
  `phone` varchar(15) NOT NULL DEFAULT '' COMMENT '鎵嬫満',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '閭',
  `dept_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '閮ㄩ棬id',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1鍚敤锛?绂佺敤',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '娣诲姞鏃堕棿',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '澶村儚',
  `last_login_time` int(10) NOT NULL DEFAULT '0' COMMENT '鏈€鍚庣櫥褰曟椂闂?,
  `last_login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '鏈€鍚庣櫥褰曠殑IP',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '鏄惁绯荤粺绠＄悊鍛橈紝0鍚︼紝1鏄?,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `username` (`username`) USING BTREE COMMENT '鐢ㄦ埛鍚嶅敮涓€'
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鐢ㄦ埛琛?;

-- ----------------------------
-- Records of light_user
-- ----------------------------
BEGIN;
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (1, 'admin', '$2y$10$mH5jYh4WxS8HjqTN9Q1tu.SUyMMezQthe6.LDkZjPu7sABJTyprn6', 'super', '', '18899996666', '', 4, 1, 1748939339, '', 1750173840, '', 1);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (2, 'demo', '$2y$10$mH5jYh4WxS8HjqTN9Q1tu.SUyMMezQthe6.LDkZjPu7sABJTyprn6', 'demo', '', '', '', 4, 1, 1748939339, '', 1750173831, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (9, 'test', '$2y$10$KZnWAJFpo/d4XXPLgzSuDOBv2Y7SQLcKwYbFZKQggCnawytQ4DSLK', '娴嬭瘯', 'cs', '', '', 15, 1, 1615864955, 'https://zos.alipayobjects.com/rmsportal/ODTLcjxAfvqbxHnVXCYX.png', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (10, 'test2', '$2y$10$89LxsExrBliqqCW/PrOBgOeubhHJUI5tmQkZJ2dBht8ltF/puI6a.', '娴嬭瘯2', 'cs', '', '', 16, 1, 1615865516, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (11, 'yunweu', '$2y$10$Zvbnq8TC7TavN/t/CriXC.g0d89XK4UaBiOYHOSctlwmjD2HLmdYu', '杩愮淮绠＄悊', 'ywgl', '', '', 17, 1, 1616059337, 'https://gw.alipayobjects.com/zos/antfincdn/XAosXuNZyF/BiazfanxmamNRoxxVxka.png', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (19, 'www', '$2y$10$SFlICzdZZMZy/2VxS4tMIODzQxcoYV40TkCbS48eTYwnvZEJJAd8u', '鐜嬩簲', 'ww', '', '', 7, 1, 1617182546, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (20, 'yang', '$2y$10$g1oVhKY1SXZmAl20SDo0xOAivYFaB4GbtDzzEjw..AcC0iNEQ/Yp2', '鏉ㄥ叚', 'yl', '', '', 4, 1, 1645673069, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (21, 'lishi', '$2y$10$e9cdcQEFEb7k9sdixmwXnuO/GD1bRN8C1xw6b1nfbPjuXxEfvP2FS', '鏉庡洓', 'ls', '', '', 4, 1, 1645673088, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (22, 'zhangs', '$2y$10$cl6yeliFDXHnfQ7accy4fOO3l7Jelcao9k3IdAka2hewcoZwiKAb2', '寮犱笁', 'zs', '', '', 8, 1, 1651917072, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `dept_id`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (23, 'wang', '$2y$10$ec8Ln1cnCH0wOGkeWJnJm.68.eue7d/0c2oGWQ25yynxvkOHL6SLK', '鐜嬪畨', 'wa', '', '', 7, 1, 1748939339, '', 0, '', 0);
COMMIT;

-- ----------------------------
-- Table structure for light_user_role
-- ----------------------------
DROP TABLE IF EXISTS `light_user_role`;
CREATE TABLE `light_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='鐢ㄦ埛瑙掕壊鍏宠仈琛?;

-- ----------------------------
-- Records of light_user_role
-- ----------------------------
BEGIN;
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (27, 14, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (28, 13, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (46, 12, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (83, 15, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (108, 1, 1);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (110, 2, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (126, 19, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (130, 24, 1);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (131, 24, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (132, 18, 1);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (133, 18, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (134, 18, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (137, 22, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (138, 23, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (140, 10, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (153, 11, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (154, 11, 5);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (155, 9, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (156, 20, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (157, 20, 3);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (158, 21, 2);
INSERT INTO `light_user_role` (`id`, `user_id`, `role_id`) VALUES (159, 21, 3);
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
