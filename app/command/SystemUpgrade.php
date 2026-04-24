<?php

namespace app\command;

use app\service\system\UpgradeService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 版本升级命令。
 *
 * 后台页面只负责创建任务和拉起命令，真正的文件替换、迁移和回滚都在 CLI 中完成，
 * 避免升级过程被 HTTP 请求超时中断。
 */
class SystemUpgrade extends Command
{
    protected function configure()
    {
        $this->setName('system:upgrade')
            ->setDescription('Run a Light Admin upgrade task')
            ->addArgument('package', Argument::OPTIONAL, 'Reserved package path')
            ->addOption('task', null, Option::VALUE_REQUIRED, 'Upgrade task id')
            ->addOption('rollback', null, Option::VALUE_NONE, 'Rollback the task backup');
    }

    protected function execute(Input $input, Output $output)
    {
        $taskId = (int) $input->getOption('task');
        if ($taskId <= 0) {
            $output->error('Missing --task option.');
            return 1;
        }

        /** @var UpgradeService $service */
        $service = app()->make(UpgradeService::class);
        if ($input->getOption('rollback')) {
            $service->runRollback($taskId, $output);
        } else {
            $service->runTask($taskId, $output);
        }

        return 0;
    }
}
