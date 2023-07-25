<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：11/7/2023 09:47:54
 */

namespace Weline\Queue\Model\Queue;

use Weline\Framework\Database\Api\Db\TableInterface;
use Weline\Framework\Setup\Data\Context;
use Weline\Framework\Setup\Db\ModelSetup;

class Type extends \Weline\Framework\Database\Model
{
    public const fields_ID = 'type_id';
    public const fields_name = 'name';
    public const fields_tip = 'tip';
    public const fields_module_name = 'module_name';
    public const fields_class = 'class';

    /**
     * @inheritDoc
     */
    public function setup(ModelSetup $setup, Context $context): void
    {
        $this->install($setup, $context);
    }

    /**
     * @inheritDoc
     */
    public function upgrade(ModelSetup $setup, Context $context): void
    {
        // TODO: Implement upgrade() method.
    }

    /**
     * @inheritDoc
     */
    public function install(ModelSetup $setup, Context $context): void
    {
//                $setup->dropTable();
        if (!$setup->tableExist()) {
            $setup->createTable('队列类型消费者')
                ->addColumn(self::fields_ID, TableInterface::column_type_INTEGER, 0, 'primary key auto_increment', 'ID')
                ->addColumn(self::fields_name, TableInterface::column_type_VARCHAR, 255, 'not null', '队列类型名称')
                ->addColumn(self::fields_tip, TableInterface::column_type_VARCHAR, 255, 'not null', '提示')
                ->addColumn(self::fields_module_name, TableInterface::column_type_VARCHAR, 255, 'not null', '队列所属模块名称')
                ->addColumn(self::fields_class, TableInterface::column_type_VARCHAR, 128, 'not null unique', '队列类型实现类名')
                ->addAdditional('ENGINE=MyISAM')
                ->create();
        }
    }

    public function getTypeId(): int
    {
        return $this->getData(self::fields_ID);
    }


    public function getModuleName(): string
    {
        return $this->getData(self::fields_module_name);
    }

    public function getName(): string
    {
        return $this->getData(self::fields_name);
    }


    public function getTip(): string
    {
        return $this->getData(self::fields_tip);
    }

    public function getClass(): string
    {
        return $this->getData(self::fields_class);
    }

    public function setTypeId(int $type_id): static
    {
        return $this->setData(self::fields_ID, $type_id);
    }

    public function setName(string $name): static
    {
        return $this->setData(self::fields_name, $name);
    }

    public function setTip(string $tip): static
    {
        return $this->setData(self::fields_tip, $tip);
    }

    public function setModuleName(string $module_name): static
    {
        return $this->setData(self::fields_module_name, $module_name);
    }

    public function setClass(string $class): static
    {
        return $this->setData(self::fields_class, $class);
    }
}
