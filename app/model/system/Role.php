<?php

namespace app\model\system;

use app\model\system\search\RoleSearch;
use app\model\system\traits\{RoleDepartmentTrait, RoleMenuTrait, RoleUserTrait};
use core\base\BaseModel;

class Role extends BaseModel
{
    use RoleSearch;
    use RoleDepartmentTrait;
    use RoleMenuTrait;
    use RoleUserTrait;

    public const ALL_DATA = 1; // 全部数据
    public const SELF_CHOOSE = 2; // 自定义数据
    public const SELF_DATA = 3; // 本人数据
    public const DEPARTMENT_DATA = 4; // 部门数据
    public const DEPARTMENT_DOWN_DATA = 5; // 部门及以下数据
}
