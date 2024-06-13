<?php
declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：23/4/2024 17:53:42
 */

namespace Weline\Queue\Helper;

use Weline\Framework\App\Env;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Module\Config\ModuleFileReader;
use Weline\Framework\Module\Model\Module;
use Weline\Queue\Model\Queue\Type;
use Weline\Queue\QueueInterface;

class Helper
{
    static function collect(): void
    {
        /** @var ModuleFileReader $reader */
        $reader = ObjectManager::getInstance(ModuleFileReader::class);
        /** @var Type $type */
        $type = ObjectManager::getInstance(Type::class);
        /** @var Type\Attributes $queueTypeAttributeModel */
        $queueTypeAttributeModel = ObjectManager::getInstance(Type\Attributes::class);
        $modules                 = Env::getInstance()->getActiveModules();
        foreach ($modules as $module) {
            $queue_files = $reader->readClass(new Module($module), 'Queue');
            foreach ($queue_files as $queue_class) {
                try {
                    $queue_ref = ObjectManager::getReflectionInstance($queue_class);
                    if (!$queue_ref->isInstantiable()) {
                        continue;
                    }
                    /**@var QueueInterface $queue */
                    $queue = ObjectManager::getInstance($queue_class);
                } catch (\Exception $e) {
                    continue;
                }
                /** @var \Weline\Eav\Model\EavAttribute[] $attrs */
                $attrs = $queue->attributes();
                foreach ($attrs as $attr) {
                    if (!($attr instanceof \Weline\Eav\Model\EavAttribute)) {
                        throw new \Exception(__('队列类：%1 属性错误。 队列属性必须继承自 %2', [
                            $queue_class,
                            \Weline\Eav\Model\EavAttribute::class
                        ]));
                    }
                }
                $type->reset()->where(Type::fields_class, $queue::class)
                    ->find()
                    ->fetch();
                $type_id = (int)$type->getId();
                if ($type_id) {
                    $type->reset()->where($type::fields_ID, $type_id);
                    $type->update([
                        Type::fields_name => $queue->name(),
                        Type::fields_module_name => $module['name'],
                        Type::fields_tip => $queue->tip(),
                        Type::fields_class => $queue::class,
                        Type::fields_attributes => implode(',', array_keys($attrs)),
                    ])
                        ->fetch();
                    $type->clearData();
                } else {
                    $type->clearData();
                    $type_id = $type->setModelFieldsData([
                        Type::fields_name => $queue->name(),
                        Type::fields_module_name => $module['name'],
                        Type::fields_tip => $queue->tip(),
                        Type::fields_class => $queue::class,
                        Type::fields_attributes => implode(',', array_keys($attrs)),
                    ])->save();
                    $type->clearData();
                }
                # 写入类型属性
                foreach ($attrs as $attr) {
                    $queueTypeAttributeModel->clearData()->reset()
                        ->where($queueTypeAttributeModel::fields_type_id, $type_id)
                        ->where($queueTypeAttributeModel::fields_code, $attr->getCode())
                        ->find()
                        ->fetch();
                    if ($queueTypeAttributeModel->getId()) {
                        $queueTypeAttributeModel->reset()->where($queueTypeAttributeModel::fields_code, $attr->getCode())
                            ->where($queueTypeAttributeModel::fields_type_id, $type_id)
                            ->update($queueTypeAttributeModel::fields_name, $attr->getName())
                            ->update($queueTypeAttributeModel::fields_attribute_id, $attr->getId())
                            ->fetch();
                        continue;
                    }
                    $queueTypeAttributeModel
                        ->setTypeId($type_id)
                        ->setAttributeId((int)$attr->getId())
                        ->setData($queueTypeAttributeModel::fields_code, $attr->getCode())
                        ->setData($queueTypeAttributeModel::fields_name, $attr->getName())
                        ->save();
                }
            }
        }
    }
}