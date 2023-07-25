<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：11/7/2023 09:36:16
 */

namespace Weline\Queue\Observer;

use Weline\Framework\App\Env;
use Weline\Framework\Event\Event;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Module\Config\ModuleFileReader;
use Weline\Framework\Module\Model\Module;
use Weline\Queue\Model\Queue\Type;
use Weline\Queue\QueueInterface;

class QueueCollect implements \Weline\Framework\Event\ObserverInterface
{
    private Type $type;
    private ModuleFileReader $reader;

    function __construct(Type $type, ModuleFileReader $reader)
    {
        $this->type   = $type;
        $this->reader = $reader;
    }

    /**
     * @inheritDoc
     */
    public function execute(Event $event)
    {
        $queues  = [];
        $modules = Env::getInstance()->getActiveModules();
        foreach ($modules as $module) {
            $queue_files      = $this->reader->readClass(new Module($module), 'Queue');
            foreach ($queue_files as $queue_class) {
                try {
                    $queue_ref = ObjectManager::getReflectionInstance($queue_class);
                    if(!$queue_ref->isInstantiable()){
                        continue;
                    }
                    /**@var QueueInterface $queue */
                    $queue = ObjectManager::getInstance($queue_class);
                } catch (\Exception $e) {
                    continue;
                }
                $queues[] = [
                    Type::fields_name => $queue->name(),
                    Type::fields_module_name => $module['name'],
                    Type::fields_tip => $queue->tip(),
                    Type::fields_class => $queue::class,
                ];
            }
        }
        $this->type->insert($queues, [Type::fields_class])->fetch();
    }
}
