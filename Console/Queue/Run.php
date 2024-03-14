<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：11/7/2023 15:34:45
 */

namespace Weline\Queue\Console\Queue;

use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;
use Weline\Queue\Model\Queue;
use Weline\Queue\QueueInterface;

class Run implements \Weline\Framework\Console\CommandInterface
{
    private Printing $printing;
    private Queue $queue;

    public function __construct(Printing $printing, Queue $queue)
    {
        $this->printing = $printing;
        $this->queue    = $queue;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = [], array $data = []): string
    {
        $id = $args[1] ?? 0;
        if ($id == 0) {
            $this->printing->error('请输入队列ID。 ');
            $this->printing->success('正确示例：php bin/m queue:run --id=1');
            exit();
        }
        $id    = str_replace('--id=', '', $id);
        $queue = $this->queue->load($id);
        if (empty($queue->getId())) {
            $this->printing->error('队列不存在。 ');
            $this->printing->success('正确示例：php bin/m queue:run --id=1');
            exit();
        }
        # 获取执行者
        $type = $queue->getType();
        /**@var QueueInterface $queue_execute */
        $queue_execute   = ObjectManager::getInstance($type->getData('class'));
        $type            = $queue->getType();
        $validate_result = $queue_execute->vaLidate($queue);
        if (is_bool($validate_result) and $validate_result) {
            $result = $queue_execute->execute($queue);
            $queue->setResult($result)->save();
        } else {
            $queue->setResult(__('队列消息内容验证不通过。验证消息：') . $validate_result)->save();
            $result = __('队列消息内容验证不通过。');
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function tip(): string
    {
        return __('运行队列');
    }
}
