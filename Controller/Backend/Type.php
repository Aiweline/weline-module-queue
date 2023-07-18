<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：18/7/2023 10:36:12
 */

namespace Weline\Queue\Controller\Backend;

class Type extends \Weline\Framework\App\Controller\BackendController
{
    private \Weline\Queue\Model\Queue\Type $type;

    public function __construct(\Weline\Queue\Model\Queue\Type $type)
    {
        $this->type = $type;
    }

    public function index()
    {
        $this->assign('title', __('队列类型'));
        if ($search = $this->request->getGet('q')) {
            $this->type->where('concat(name,content,result)', "%$search%", 'LIKE');
        }
        $this->type->pagination()->select()->fetch();
        $this->assign('types', $this->type->getItems());
        $this->assign('pagination', $this->type->getPagination());
        return $this->fetch();
    }
}
