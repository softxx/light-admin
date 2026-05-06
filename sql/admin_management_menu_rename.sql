-- Rename the existing user-management menu labels to administrator-management labels.
-- Technical route paths and permission rules stay as system:user:* for compatibility.

UPDATE `light_menu`
SET `title` = '管理员管理'
WHERE `id` = 20;

UPDATE `light_menu`
SET `title` = '新增管理员'
WHERE `id` = 245;

UPDATE `light_menu`
SET `title` = '更新管理员'
WHERE `id` = 40;

UPDATE `light_menu`
SET `title` = '删除管理员'
WHERE `id` = 237;
