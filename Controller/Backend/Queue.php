<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：18/7/2023 09:57:55
 */

namespace Weline\Queue\Controller\Backend;

use PHPUnit\Util\Exception;

class Queue extends \Weline\Framework\App\Controller\BackendController
{
    private \Weline\Queue\Model\Queue $queue;

    public function __construct(\Weline\Queue\Model\Queue $queue)
    {
        $this->queue = $queue;
    }

    public function index()
    {
        $this->assign('title', __('消息队列'));
        $this->queue->joinModel(\Weline\Queue\Model\Queue\Type::class, 't', 'main_table.type_id=t.type_id', 'left');
        $id = $this->request->getGet('id');
        if ($id) {
            $this->queue->where($this->queue::fields_ID, $id);
        } elseif ($search = $this->request->getGet('q')) {
            $this->queue->where("concat(main_table.name,main_table.content,main_table.result) like '%$search%'");
        }
//        p($this->queue->select()->getLastSql());
        $this->queue->order($this->queue::fields_ID, 'DESC')->pagination()->select()->fetch();
        $this->assign('queues', $this->queue->getItems());
        $this->assign('pagination', $this->queue->getPagination());
        return $this->fetch();
    }

    function getDelete()
    {
        $queue_id = $this->request->getGet('id', 0);
        if (empty($queue_id)) {
            $this->getMessageManager()->addWarning(__('请选择要操作的队列'));
            $this->redirect('*/backend/queue');
        }
        $this->queue->load($queue_id);
        if ($this->queue->getStatus() !== $this->queue::status_pending) {
            $this->getMessageManager()->addWarning(__('队列未处于等待状态无法删除！'));
            $this->redirect('*/backend/queue');
        }
        $this->queue->delete();
        $this->getMessageManager()->addSuccess(__('队列已成功删除！'));
        $this->redirect('*/backend/queue');
    }

    function getDetailResult()
    {
        $queue_id = $this->request->getParam('id', 0);
        if (empty($queue_id)) {
            $this->getMessageManager()->addWarning(__('请选择要操作的队列'));
            return $this->fetch('content');
        }
        $this->queue->load($queue_id);
        $data = $this->queue->getData($this->queue::fields_result);
        $this->assign('data', $data);
        return $this->fetch('content');
    }

    function getDetailContent()
    {
        $queue_id = $this->request->getParam('id', 0);
        if (empty($queue_id)) {
            $this->getMessageManager()->addWarning(__('请选择要操作的队列'));
            return $this->fetch('content');
        }
        $this->queue->load($queue_id);
        $data = $this->queue->getData($this->queue::fields_content);
        $this->assign('data', $data);
        return $this->fetch('content');
    }
}
