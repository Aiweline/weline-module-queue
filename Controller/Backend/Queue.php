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
        if ($search = $this->request->getGet('q')) {
            $this->queue->where("concat(main_table.name,main_table.content,main_table.result) like '%$search%'");
        }
        if ($id = $this->request->getGet('id')) {
            $this->queue->where('main_table.' . $this->queue::fields_ID, $id);
        }
        $this->queue->order('main_table.' . $this->queue::fields_UPDATE_TIME)->pagination()->select()->fetch();
        $this->assign('queues', $this->queue->getItems());
        $this->assign('pagination', $this->queue->getPagination());
        return $this->fetch();
    }

    function show()
    {
        $id = $this->request->getGet('id');
        if (empty($id)) {
            $this->getMessageManager()->addWarning(__('请选择要查看的队列'));
            $this->redirect('component/offcanvas/error', ['msg' => __('请选择要查看的队列'), 'reload' => 1]);
        }
        $this->queue->joinModel(\Weline\Queue\Model\Queue\Type::class, 't', 'main_table.type_id=t.type_id', 'left')
            ->where('main_table.' . $this->queue::fields_ID, $id)->find()->fetch();
        if (!$this->queue->getId()) {
            $this->getMessageManager()->addWarning(__('队列不存在'));
            $this->redirect('component/offcanvas/error', ['msg' => __('队列不存在'), 'reload' => 0]);
        }
        $this->queue->setData('data', w_var_export(json_decode($this->queue->getData($this->queue::fields_content)), true));
        $this->assign('queue', $this->queue);
        # 如果result结果大于1M，就下载
        $result = $this->queue->getData('result');
        if (!empty($result)) {
            $resultSize = mb_strlen($result);
            if ($resultSize > 1024 * 1024) {
                $dowloadUrl = $this->request->getUrlBuilder()->getBackendUrl('*/backend/queue/dowloadResult', ['id' => $id]);
                $sieMb      = round($resultSize / 1024 / 1024, 2);
                $this->queue->setData('result', __('队列结果过大:%1 Mb。 请<a href="%2">下载队列结果</a>查看。', [$sieMb, $dowloadUrl]));
            }
        }
        return $this->fetch();
    }

    function dowloadResult()
    {
        $id = $this->request->getGet('id');
        if (empty($id)) {
            http_response_code(403);
            exit(__('请选择要下载的队列'));
        }
        $this->queue->load($id);
        if (!$this->queue->getId()) {
            http_response_code(404);
            exit(__('队列不存在'));
        }
        # 自动将结果result生成txt下载
        $dowloadName = 'queue_result_' . $id . '.txt';
        $result      = $this->queue->getData('result');
        if (!empty($result)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $dowloadName. '"');
            echo $result;
            exit;
        } else {
            exit(__('队列没有结果'));
        }
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
