SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for light_auth_access
-- ----------------------------
-- auth_access now maps users directly to menus; role_id is intentionally absent.
DROP TABLE IF EXISTS `light_auth_access`;
CREATE TABLE `light_auth_access` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT 'user id',
  `menu_id` int(10) NOT NULL DEFAULT '0' COMMENT '菜单id',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `user_menu` (`user_id`, `menu_id`) USING BTREE,
  KEY `menu_id` (`menu_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='user auth menu map';

-- ----------------------------
-- Records of light_auth_access
-- ----------------------------
BEGIN;
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (1, 1, 13);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (2, 1, 14);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (3, 1, 20);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (4, 1, 28);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (5, 1, 29);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (6, 1, 30);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (7, 1, 36);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (8, 1, 40);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (9, 1, 50);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (10, 1, 53);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (11, 1, 54);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (12, 1, 77);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (13, 1, 78);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (14, 1, 80);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (15, 1, 82);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (16, 1, 105);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (17, 1, 106);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (18, 1, 107);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (19, 1, 108);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (20, 1, 110);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (21, 1, 113);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (22, 1, 114);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (23, 1, 116);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (24, 1, 119);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (25, 1, 120);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (26, 1, 156);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (27, 1, 167);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (28, 1, 236);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (29, 1, 237);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (30, 1, 238);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (31, 1, 239);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (32, 1, 240);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (33, 1, 241);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (34, 1, 242);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (35, 1, 243);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (36, 1, 244);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (37, 1, 245);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (38, 1, 246);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (39, 1, 247);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (40, 1, 248);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (41, 1, 249);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (42, 1, 250);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (43, 1, 251);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (44, 1, 252);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (45, 1, 253);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (46, 1, 254);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (47, 1, 255);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (48, 1, 256);
INSERT INTO `light_auth_access` (`id`, `user_id`, `menu_id`) VALUES (49, 1, 257);
COMMIT;

-- ----------------------------
-- Table structure for light_dict
-- ----------------------------
DROP TABLE IF EXISTS `light_dict`;
CREATE TABLE `light_dict` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '字典类型',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '中文名称',
  `value` varchar(30) NOT NULL DEFAULT '' COMMENT '字典属性值',
  `sort` smallint(5) NOT NULL DEFAULT '8' COMMENT '排序',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `color` varchar(10) NOT NULL DEFAULT '' COMMENT '字典组件颜色',
  `widget_type` varchar(10) NOT NULL DEFAULT '' COMMENT '字典组件类型',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除，0未删除，1已删除',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态，1启用，2禁用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=562 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='字典表';

-- ----------------------------
-- Records of light_dict
-- ----------------------------
BEGIN;
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (75, 'dict_type', '字典类型', 'dict_type', 8, '', '', '', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (250, 'dict_type', '菜单类型', 'menu_type', 8, '', '', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (251, 'menu_type', '目录', '0', 2, '', 'green', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (252, 'menu_type', '菜单', '1', 1, '', 'blue', 'tag', 0, 1);
INSERT INTO `light_dict` (`id`, `type`, `name`, `value`, `sort`, `note`, `color`, `widget_type`, `is_delete`, `status`) VALUES (253, 'menu_type', '权限', '2', 3, '', '', 'tag', 0, 1);
COMMIT;

-- ----------------------------
-- Table structure for light_file
-- ----------------------------
DROP TABLE IF EXISTS `light_file`;
CREATE TABLE `light_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL COMMENT '文件地址',
  `mime_type` varchar(100) NOT NULL COMMENT 'mime类型',
  `file_ext` varchar(30) NOT NULL COMMENT '文件扩展名',
  `file_size` int(11) NOT NULL DEFAULT '0' COMMENT '文件大小',
  `filename` varchar(255) NOT NULL COMMENT '文件名称',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '上传时间',
  `user_id` varchar(30) NOT NULL DEFAULT '' COMMENT '上传用户id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='文件表';

-- ----------------------------
-- Table structure for light_login_log
-- ----------------------------
DROP TABLE IF EXISTS `light_login_log`;
CREATE TABLE `light_login_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(30) NOT NULL DEFAULT '' COMMENT '登录账号',
  `login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '登录ip',
  `browser` varchar(20) NOT NULL DEFAULT '' COMMENT '浏览器',
  `os` varchar(20) NOT NULL DEFAULT '' COMMENT '操作系统',
  `login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='登录日志表';

-- ----------------------------
-- Table structure for light_system_setting
-- ----------------------------
DROP TABLE IF EXISTS `light_system_setting`;
CREATE TABLE `light_system_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `system_name` varchar(100) NOT NULL DEFAULT '' COMMENT '系统名称',
  `logo` varchar(500) NOT NULL DEFAULT '' COMMENT '系统Logo',
  `favicon` varchar(500) NOT NULL DEFAULT '' COMMENT '站点图标',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统设置表';

-- ----------------------------
-- Records of light_system_setting
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_system_version
-- ----------------------------
DROP TABLE IF EXISTS `light_system_version`;
CREATE TABLE `light_system_version` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `version` varchar(32) NOT NULL DEFAULT '' COMMENT '版本号',
  `build` varchar(64) NOT NULL DEFAULT '' COMMENT '构建号',
  `commit_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '代码提交哈希',
  `channel` varchar(32) NOT NULL DEFAULT 'stable' COMMENT '发布通道',
  `release_notes` longtext NULL COMMENT '版本更新说明JSON',
  `installed_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_version` (`version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统版本安装记录表';

-- ----------------------------
-- Records of light_system_version
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_upgrade_task
-- ----------------------------
DROP TABLE IF EXISTS `light_upgrade_task`;
CREATE TABLE `light_upgrade_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `target_version` varchar(32) NOT NULL DEFAULT '' COMMENT '目标版本号',
  `package_url` varchar(1000) NOT NULL DEFAULT '' COMMENT '升级包下载地址',
  `package_path` varchar(1000) NOT NULL DEFAULT '' COMMENT '本地升级包路径',
  `backup_path` varchar(1000) NOT NULL DEFAULT '' COMMENT '升级前备份路径',
  `manifest_url` varchar(1000) NOT NULL DEFAULT '' COMMENT '发布源地址',
  `manifest` longtext NULL COMMENT '发布版本元数据JSON',
  `precheck` longtext NULL COMMENT '预检查结果JSON',
  `status` varchar(32) NOT NULL DEFAULT 'pending' COMMENT '任务状态',
  `progress` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '任务进度百分比',
  `message` varchar(1000) NOT NULL DEFAULT '' COMMENT '当前状态消息',
  `logs` longtext NULL COMMENT '任务日志JSON',
  `error` longtext NULL COMMENT '失败原因',
  `operator_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `started_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `finished_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_target_version` (`target_version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统升级任务表';

-- ----------------------------
-- Records of light_upgrade_task
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_menu
-- ----------------------------
DROP TABLE IF EXISTS `light_menu`;
CREATE TABLE `light_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `path` varchar(100) NOT NULL DEFAULT '' COMMENT '路由路径',
  `component` varchar(255) NOT NULL DEFAULT '' COMMENT '路由组件',
  `hidden` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏',
  `title` varchar(30) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `rules` varchar(100) NOT NULL DEFAULT '' COMMENT '权限节点',
  `sort` smallint(5) NOT NULL DEFAULT '1' COMMENT '排序',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 目录，1菜单，2权限',
  `hide_children` tinyint(1) NOT NULL DEFAULT '0' COMMENT '隐藏子菜单，并且强制渲染为菜单项',
  `active_key` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单高亮key',
  `open_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打开方式 0组件，1内链，2外链',
  `link_url` varchar(500) NOT NULL DEFAULT '' COMMENT '内链地址',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='菜单表';

-- ----------------------------
-- Records of light_menu
-- ----------------------------
BEGIN;
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (13, 0, 'system', 'Layout', 0, '系统管理', 'setting-outlined', '', 5, 0, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (14, 13, 'menu', 'system/menu/index', 0, '菜单管理', 'menu-outlined', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (20, 13, 'user', 'system/user/index', 0, '管理员管理', 'team-outlined', '', 3, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (28, 14, '', '', 0, '添加菜单', '', 'system:menu:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (29, 14, '', '', 0, '修改菜单', '', 'system:menu:update', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (30, 14, '', '', 0, '删除菜单', '', 'system:menu:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (245, 20, '', '', 0, '新增管理员', '', 'system:user:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (40, 20, '', '', 0, '更新管理员', '', 'system:user:update', 2, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (50, 13, 'dict', 'system/dict/index', 0, '字典管理', 'deployment-unit-outlined', '', 5, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (53, 50, '', '', 0, '修改字典', '', 'system:dict:update', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (54, 50, '', '', 0, '删除字典', '', 'system:dict:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (77, 14, '', '', 0, '查看列表', '', 'system:menu:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (80, 20, '', '', 0, '查看列表', '', 'system:user:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (82, 50, '', '', 0, '查看列表', '', 'system:dict:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (105, 156, 'operate', 'system/logs/operate-log', 0, '操作日志', 'profile-outlined', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (106, 105, '', '', 0, '查看列表', '', 'system:operateLog:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (107, 105, '', '', 0, '清空日志', '', 'system:operateLog:clear', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (108, 20, '', '', 0, '修改状态', '', 'system:user:changeStatus', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (110, 50, '', '', 0, '添加字典', '', 'system:dict:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (113, 156, 'login', 'system/logs/login-log', 0, '登录日志', 'diff-outlined', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (114, 113, '', '', 0, '查看列表', '', 'system:loginLog:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (116, 113, '', '', 0, '清空日志', '', 'system:loginLog:clear', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (119, 105, '', '', 0, '删除日志', '', 'system:operateLog:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (120, 113, '', '', 0, '删除日志', '', 'system:loginLog:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (156, 13, 'log', 'RouteView', 0, '日志管理', 'file-text-outlined', '', 1, 0, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (167, 165, 'filetype', 'system/login_log/index', 0, '附件类型', '', '', 1, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (236, 20, '', '', 0, '重置密码', '', 'system:user:resetPassword', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (237, 20, '', '', 0, '删除管理员', '', 'system:user:delete', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (36, 20, '', '', 0, '保存权限', '', 'system:authAccess:save', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (78, 20, '', '', 0, '查看权限', '', 'system:authAccess:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (238, 13, 'setting', 'system/system-setting/index', 0, '系统设置', 'setting-outlined', '', 99, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (239, 238, '', '', 0, '查看配置', '', 'system:systemsetting:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (240, 238, '', '', 0, '保存配置', '', 'system:systemsetting:update', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (241, 13, 'cache', 'system/cache-manage/index', 0, '缓存管理', 'database-outlined', '', 100, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (242, 241, '', '', 0, '访问缓存管理', '', 'system:cache:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (243, 241, '', '', 0, '刷新字典缓存', '', 'system:cache:refreshdict', 2, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (244, 241, '', '', 0, '清理运行缓存', '', 'system:cache:clearruntime', 3, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (249, 13, 'version', 'system/version-manage/index', 0, '版本管理', 'cloud-upload-outlined', '', 101, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (250, 249, '', '', 0, '查看版本信息', '', 'system:version:current', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (251, 249, '', '', 0, '检查更新', '', 'system:version:check', 2, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (252, 249, '', '', 0, '下载安装包', '', 'system:version:download', 3, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (253, 249, '', '', 0, '升级预检', '', 'system:version:precheck', 4, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (254, 249, '', '', 0, '执行升级', '', 'system:version:upgrade', 5, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (255, 249, '', '', 0, '执行回滚', '', 'system:version:rollback', 6, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (256, 249, '', '', 0, '查看任务详情', '', 'system:version:task', 7, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (257, 249, '', '', 0, '查看升级记录', '', 'system:version:tasks', 8, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (246, 13, 'file', 'system/file-manage/index', 0, '文件管理', 'folder-open-outlined', '', 102, 1, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (247, 246, '', '', 0, '查看列表', '', 'system:file:index', 1, 2, 0, '', 0, '');
INSERT INTO `light_menu` (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`) VALUES (248, 246, '', '', 0, '删除文件', '', 'system:file:delete', 2, 2, 0, '', 0, '');
COMMIT;

-- ----------------------------
-- Table structure for light_operate_log
-- ----------------------------
DROP TABLE IF EXISTS `light_operate_log`;
CREATE TABLE `light_operate_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '模块名称',
  `operate` varchar(20) NOT NULL DEFAULT '' COMMENT '操作模块',
  `route` varchar(100) NOT NULL DEFAULT '' COMMENT '路由',
  `params` varchar(1000) NOT NULL DEFAULT '' COMMENT '参数',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'ip',
  `user_id` varchar(30) NOT NULL DEFAULT '' COMMENT '操作用户',
  `method` varchar(255) NOT NULL DEFAULT '' COMMENT '请求方法',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='操作日志';

-- ----------------------------
-- Records of light_operate_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for light_user
-- ----------------------------
DROP TABLE IF EXISTS `light_user`;
CREATE TABLE `light_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `realname` varchar(10) NOT NULL DEFAULT '' COMMENT '姓名',
  `pinyin` varchar(10) NOT NULL DEFAULT '' COMMENT '拼音',
  `phone` varchar(15) NOT NULL DEFAULT '' COMMENT '手机',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1启用，2禁用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `last_login_time` int(10) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '最后登录的IP',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统管理员，0否，1是',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `username` (`username`) USING BTREE COMMENT '用户名唯一'
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='用户表';

-- ----------------------------
-- Records of light_user
-- ----------------------------
BEGIN;
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (1, 'admin', '$2y$10$mH5jYh4WxS8HjqTN9Q1tu.SUyMMezQthe6.LDkZjPu7sABJTyprn6', 'admin', '', '18899996666', '', 1, 1748939339, '', 1750173840, '', 1);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (2, 'demo', '$2y$10$mH5jYh4WxS8HjqTN9Q1tu.SUyMMezQthe6.LDkZjPu7sABJTyprn6', 'demo', '', '', '', 1, 1748939339, '', 1750173831, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (9, 'test', '$2y$10$KZnWAJFpo/d4XXPLgzSuDOBv2Y7SQLcKwYbFZKQggCnawytQ4DSLK', '测试', 'cs', '', '', 1, 1615864955, 'https://zos.alipayobjects.com/rmsportal/ODTLcjxAfvqbxHnVXCYX.png', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (10, 'test2', '$2y$10$89LxsExrBliqqCW/PrOBgOeubhHJUI5tmQkZJ2dBht8ltF/puI6a.', '测试2', 'cs', '', '', 1, 1615865516, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (11, 'yunweu', '$2y$10$Zvbnq8TC7TavN/t/CriXC.g0d89XK4UaBiOYHOSctlwmjD2HLmdYu', '运维管理', 'ywgl', '', '', 1, 1616059337, 'https://gw.alipayobjects.com/zos/antfincdn/XAosXuNZyF/BiazfanxmamNRoxxVxka.png', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (19, 'www', '$2y$10$SFlICzdZZMZy/2VxS4tMIODzQxcoYV40TkCbS48eTYwnvZEJJAd8u', '王五', 'ww', '', '', 1, 1617182546, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (20, 'yang', '$2y$10$g1oVhKY1SXZmAl20SDo0xOAivYFaB4GbtDzzEjw..AcC0iNEQ/Yp2', '杨六', 'yl', '', '', 1, 1645673069, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (21, 'lishi', '$2y$10$e9cdcQEFEb7k9sdixmwXnuO/GD1bRN8C1xw6b1nfbPjuXxEfvP2FS', '李四', 'ls', '', '', 1, 1645673088, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (22, 'zhangs', '$2y$10$cl6yeliFDXHnfQ7accy4fOO3l7Jelcao9k3IdAka2hewcoZwiKAb2', '张三', 'zs', '', '', 1, 1651917072, '', 0, '', 0);
INSERT INTO `light_user` (`id`, `username`, `password`, `realname`, `pinyin`, `phone`, `email`, `status`, `create_time`, `avatar`, `last_login_time`, `last_login_ip`, `is_admin`) VALUES (23, 'wang', '$2y$10$ec8Ln1cnCH0wOGkeWJnJm.68.eue7d/0c2oGWQ25yynxvkOHL6SLK', '王安', 'wa', '', '', 1, 1748939339, '', 0, '', 0);
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
