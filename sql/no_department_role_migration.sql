-- Migration for existing databases after removing department and role modules.
-- It converts old role-based menu permissions into direct user permissions.

-- 1. Preserve existing effective permissions before dropping the old auth_access table.
CREATE TEMPORARY TABLE `tmp_user_auth_access` AS
SELECT DISTINCT
  `ur`.`user_id`,
  `aa`.`menu_id`
FROM `light_user_role` AS `ur`
INNER JOIN `light_auth_access` AS `aa`
  ON `aa`.`role_id` = `ur`.`role_id`
INNER JOIN `light_menu` AS `m`
  ON `m`.`id` = `aa`.`menu_id`
WHERE `aa`.`menu_id` NOT IN (16, 21, 32, 38, 45, 46, 47, 81, 130);

-- 2. Move the permission-management buttons from the removed role page to user management.
UPDATE `light_menu`
SET
  `pid` = 20,
  `path` = '',
  `component` = '',
  `hidden` = 0,
  `title` = '保存权限',
  `icon` = '',
  `rules` = 'system:authAccess:save',
  `sort` = 8,
  `type` = 2,
  `hide_children` = 0,
  `active_key` = '',
  `open_type` = 0,
  `link_url` = ''
WHERE `id` = 36;

UPDATE `light_menu`
SET
  `pid` = 20,
  `path` = '',
  `component` = '',
  `hidden` = 0,
  `title` = '查看权限',
  `icon` = '',
  `rules` = 'system:authAccess:index',
  `sort` = 9,
  `type` = 2,
  `hide_children` = 0,
  `active_key` = '',
  `open_type` = 0,
  `link_url` = ''
WHERE `id` = 78;

-- 3. Backfill the two permission buttons for databases that no longer have the old role page.
INSERT INTO `light_menu`
  (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`)
SELECT
  36, 20, '', '', 0, '保存权限', '', 'system:authAccess:save', 8, 2, 0, '', 0, ''
WHERE NOT EXISTS (SELECT 1 FROM `light_menu` WHERE `id` = 36);

INSERT INTO `light_menu`
  (`id`, `pid`, `path`, `component`, `hidden`, `title`, `icon`, `rules`, `sort`, `type`, `hide_children`, `active_key`, `open_type`, `link_url`)
SELECT
  78, 20, '', '', 0, '查看权限', '', 'system:authAccess:index', 9, 2, 0, '', 0, ''
WHERE NOT EXISTS (SELECT 1 FROM `light_menu` WHERE `id` = 78);

-- 4. Remove department and role menu entries after moving reusable permission buttons.
DELETE FROM `light_menu`
WHERE `id` IN (16, 21, 32, 38, 45, 46, 47, 81, 130);

-- 5. Recreate auth_access with the new user_id + menu_id schema.
DROP TABLE IF EXISTS `light_auth_access`;

CREATE TABLE `light_auth_access` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT 'user id',
  `menu_id` int(10) NOT NULL DEFAULT '0' COMMENT 'menu id',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `user_menu` (`user_id`, `menu_id`) USING BTREE,
  KEY `menu_id` (`menu_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='user auth menu map';

-- 6. Restore preserved permissions, skipping menu ids that no longer exist.
INSERT IGNORE INTO `light_auth_access` (`user_id`, `menu_id`)
SELECT `user_id`, `menu_id`
FROM `tmp_user_auth_access`
WHERE `menu_id` IN (SELECT `id` FROM `light_menu`);

DROP TEMPORARY TABLE IF EXISTS `tmp_user_auth_access`;

-- 7. Drop old department/role data structures from the running database.
ALTER TABLE `light_user` DROP COLUMN `dept_id`;

DROP TABLE IF EXISTS
  `light_user_role`,
  `light_role_department`,
  `light_role`,
  `light_department`;
