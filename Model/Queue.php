<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：11/7/2023 09:15:50
 */

namespace Weline\Queue\Model;

use Weline\Framework\Database\Api\Db\TableInterface;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Setup\Data\Context;
use Weline\Framework\Setup\Db\ModelSetup;
use Weline\Queue\Model\Queue\Type;

class Queue extends \Weline\Framework\Database\Model
{
    public const fields_ID       = 'queue_id';
    public const fields_type_id  = 'type_id';
    public const fields_pid      = 'pid';
    public const fields_name     = 'name';
    public const fields_start_at = 'start_at';
    public const fields_end_at   = 'end_at';
    public const fields_result   = 'result';
    public const fields_content  = 'content';
    public const fields_status   = 'status';
    public const fields_finished = 'finished';
    public const fields_auto     = 'auto';

    /*状态*/
    public const status_pending = 'pending';
    public const status_running = 'running';
    public const status_done    = 'done';
    public const status_error   = 'error';


    public function setup(ModelSetup $setup, Context $context): void
    {
        $this->install($setup, $context);
    }

    public function upgrade(ModelSetup $setup, Context $context): void
    {
        // TODO: Implement upgrade() method.
    }

    public function install(ModelSetup $setup, Context $context): void
    {
        //        $setup->dropTable();
        if (!$setup->tableExist()) {
            $setup->createTable('任务队列')
                  ->addColumn(self::fields_ID, TableInterface::column_type_INTEGER, 0, 'primary key auto_increment', 'ID')
                  ->addColumn(self::fields_pid, TableInterface::column_type_INTEGER, 0, 'default 0', '进程ID')
                  ->addColumn(self::fields_type_id, TableInterface::column_type_INTEGER, 0, 'not null', '任务类别')
                  ->addColumn(self::fields_name, TableInterface::column_type_VARCHAR, 255, 'not null', '任务名称')
                  ->addColumn(self::fields_start_at, TableInterface::column_type_TIMESTAMP, null, '', '开始时间')
                  ->addColumn(self::fields_end_at, TableInterface::column_type_TIMESTAMP, null, '', '结束时间')
                  ->addColumn(self::fields_result, TableInterface::column_type_TEXT, null, "default ''", '结果')
                  ->addColumn(self::fields_content, TableInterface::column_type_TEXT, null, "default ''", '内容')
                  ->addColumn(self::fields_status, TableInterface::column_type_VARCHAR, 12, "default 'pending'", '状态')
                  ->addColumn(self::fields_finished, TableInterface::column_type_SMALLINT, 1, 'default 0', '是否完成')
                  ->addColumn(self::fields_auto, TableInterface::column_type_SMALLINT, 1, 'default 1', '是否自动')
                  ->addIndex(TableInterface::index_type_KEY, 'type_id', self::fields_type_id, '类型索引')
                  ->addIndex(TableInterface::index_type_KEY, self::fields_finished, self::fields_finished, '是否完成索引')
                  ->create();
        }
    }

    public function getTypeId(): int
    {
        return (int)$this->getData(self::fields_type_id);
    }

    public function getPid(): int
    {
        return (int)$this->getData(self::fields_pid);
    }

    public function getName(): string
    {
        return $this->getData(self::fields_name);
    }

    public function getStartAt(): string
    {
        return $this->getData(self::fields_start_at);
    }


    public function getEndAt(): string
    {
        return $this->getData(self::fields_end_at);
    }

    public function getStatus(): string
    {
        return $this->getData(self::fields_status);
    }

    public function getContent(): string
    {
        return $this->getData(self::fields_content);
    }

    public function getAuto(): bool
    {
        return $this->getData(self::fields_auto) == 1;
    }

    public function setTypeId(int $type_id): static
    {
        return $this->setData(self::fields_type_id, $type_id);
    }


    public function setPid(int $process_id): static
    {
        return $this->setData(self::fields_pid, $process_id);
    }

    public function setName(string $name): static
    {
        return $this->setData(self::fields_name, $name);
    }

    public function setStartAt(string $start_at): static
    {
        return $this->setData(self::fields_start_at, $start_at);
    }

    public function setEndAt(string $end_at): static
    {
        return $this->setData(self::fields_end_at, $end_at);
    }

    public function setStatus(string $status = self::status_pending): static
    {
        return $this->setData(self::fields_status, $status);
    }

    public function setContent(string $content): static
    {
        return $this->setData(self::fields_content, $content);
    }


    public function setResult(string $result_msg): static
    {
        return $this->setData(self::fields_result, $result_msg);
    }

    public function setFinished(bool $finished): static
    {
        return $this->setData(self::fields_finished, $finished);
    }

    public function setAuto(bool $auto): static
    {
        return $this->setData(self::fields_auto, $auto ? 1 : 0);
    }

    public function getType(): Type
    {
        /**@var Type $type */
        $type = ObjectManager::getInstance(Type::class);
        return $type->load($this->getTypeId());
    }
}
