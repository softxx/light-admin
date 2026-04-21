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

    public const ALL_DATA = 1; // 鍏ㄩ儴鏁版嵁
    public const SELF_CHOOSE = 2; // 鑷畾涔夋暟鎹?
    public const SELF_DATA = 3; // 鏈汉鏁版嵁
    public const DEPARTMENT_DATA = 4; // 閮ㄩ棬鏁版嵁
    public const DEPARTMENT_DOWN_DATA = 5; // 閮ㄩ棬鍙婁互涓嬫暟鎹?
}
