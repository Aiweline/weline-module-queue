<?php
declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：11/7/2023 09:26:19
 */

namespace Weline\Queue;

use Weline\Queue\Model\Queue;

interface QueueInterface
{
    /**
     * @DESC          # 队列名
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 11/7/2023 下午2:01
     * 参数区：
     * @return string
     */
    public function name(): string;

    /**
     * @DESC          # 队列执行方法
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 11/7/2023 下午2:01
     * 参数区：
     * @return string 执行结果
     */
    public function execute(Queue $queue): string;

    /**
     * @DESC          # 必要的队列参数字段.
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 11/7/2023 下午1:57
     * 参数区：
     * @return array
     */
    public function fields(): array;
}