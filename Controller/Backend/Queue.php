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
        $this->queue->joinModel(\Weline\Queue\Model\Queue\Type::class,'t','main_table.type_id=t.type_id','left');
        if ($search = $this->request->getGet('q')) {
            $this->queue->where("concat(name,content,result)", "%$search%", 'LIKE');
        }
        $this->queue->pagination()->select()->fetch();
        $this->assign('queues', $this->queue->getItems());
        $this->assign('pagination', $this->queue->getPagination());
        return $this->fetch();
    }
}
