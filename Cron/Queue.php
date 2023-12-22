<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：11/7/2023 09:17:50
 */

namespace Weline\Queue\Cron;

use Weline\Cron\Helper\CronStatus;
use Weline\Cron\Helper\Process;
use Weline\Framework\Output\Cli\Printing;

class Queue implements \Weline\Cron\CronTaskInterface
{

    private \Weline\Queue\Model\Queue $queue;
    private \Weline\Framework\Output\Cli\Printing $printing;

    function __construct(
        \Weline\Queue\Model\Queue $queue,
        Printing                  $printing
    )
    {
        $this->queue    = $queue;
        $this->printing = $printing;
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return '消息队列-消费任务';
    }

    /**
     * @inheritDoc
     */
    public function execute_name(): string
    {
        return 'queue';
    }

    /**
     * @inheritDoc
     */
    public function tip(): string
    {
        return <<<QUEUETIP
定时消费任务，每5秒检测一次消息队列。如果有任务继续执行队列中的任务。
QUEUETIP;

    }

    /**
     * @inheritDoc
     */
    public function cron_time(): string
    {
        return '*/1 * * * *';
    }

    /**
     * @inheritDoc
     */
    public function execute(): string
    {
        $pageSize = 1;
        $this->queue->reset()->where($this->queue::fields_finished, 0)
            ->where($this->queue::fields_auto, 1)
            ->where($this->queue::fields_status, $this->queue::status_done, '!=')
            ->pagination();
        $pages = $this->queue->pagination['lastPage'];
        foreach (range(1, $pages) as $page) {
            # 进程信息管理
            $processes = [];
            $pipes     = [];
            $queues    = $this->queue->reset()->where($this->queue::fields_finished, 0)
                ->where($this->queue::fields_status, $this->queue::status_done, '!=')
                ->where($this->queue::fields_auto, 1)
                ->pagination($page, $pageSize)
                ->select()
                ->fetch()
                ->getItems();
            /**@var \Weline\Queue\Model\Queue $queue */
            foreach ($queues as $key => &$queue) {
                $queue_name = 'queue-'.$queue->getName() . '-' . $queue->getId();
                # 检测程序是否还在运行
                if ($pid = $queue->getPid()) {
                    $output = Process::getProcessOutput($queue_name);
                    if (Process::isProcessRunning($pid)) {
                        $queue->setResult($output)->save();
                        continue;
                    } else {
                        $queue->setFinished(true)
                            ->setEndAt(date('Y-m-d H:i:s'))
                            ->setStatus($queue::status_done)
                            ->setResult($output . __('进程结束...'))
                            ->setPid(0)
                            ->save();
                        # 卸载进程记录文件
                        Process::unsetLogProcessFilePath($queue_name);
                    }
                    continue;
                }
                $queue->setResult('');
                $descriptorspec = array(
                    0 => array('pipe', 'r'),   // 子进程将从此管道读取stdin
                    1 => array('pipe', 'w'),   // 子进程将向此管道写入stdout
                    2 => array('pipe', 'w')    // 子进程将向此管道写入stderr
                );
                # 创建异步程序
                $process_log_path = Process::getLogProcessFilePath($queue_name);
                $command_fix      = !IS_WIN ? ' 2>&1 & echo $!' : '';
                $command          = 'cd ' . BP . ' && nohup ' . PHP_BINARY . ' bin/m queue:run --id=' . $queue->getId() . ' > ' . $process_log_path . $command_fix;
                $process          = proc_open($command, $descriptorspec, $procPipes);
                # 进程保存到进程数组
                $processes[$key] = $process;
                # 设置进程非阻塞
                stream_set_blocking($procPipes[1], false);
                $pipes[$key] = $procPipes;
                if (is_resource($process)) {
                    $pid = proc_get_status($process)['pid'] + 2;
                    // 执行其他操作
                    # 记录PID
                    $queue->setPid($pid)
                        ->setStatus($queue::status_running)
                        ->setStartAt(date('Y-m-d H:i:s'))
                        ->save();
                    // 关闭文件指针
                    fclose($procPipes[0]);
                    fclose($procPipes[1]);
                    fclose($procPipes[2]);
                } else {
                    $queue->setResult(__('进程创建失败！请检查进程状态！'))
                        ->setStatus($queue::status_error)
                        ->save();
                }
            }
//                    # 等待页进程结束
//                    # 循环检查各进程，直到所有子进程结束
//                    if ($processes) {
//                        while (array_filter($processes, function ($proc) {
//                            return proc_get_status($proc)['running'];
//                        })) {
//                            /**@var \Weline\Queue\Model\Queue $queue */
//                            foreach ($queues as $i => &$queue) {
//                                # 如果有对应进程,读取所有可读取的输出（缓冲未读输出）
//                                if (!empty($pipes[$i])) {
//                                    $str = fread($pipes[$i][1], 1024);
//                                    if ($str) {
//                                        $queue->setResult($queue->getResult() . $str)
//                                              ->save();
//                                        echo $str;
//                                    }
//                                }
//                            }
//                        }
//                        # 关闭所有管道和进程
//                        foreach ($queues as $i => &$queue) {
//                            if (!empty($pipes[$i])) {
//                                fclose($pipes[$i][1]);
//                                proc_close($processes[$i]);
//                                $queue->setEndAt(date('Y-m-d H:i:s'));
//                                # 运行完毕将进程ID设置为0
//                                $queue->setPid(0);
//                                $queue->setFinished(true);
//                                $queue->setStatus($queue::status_done);
//                                $queue->save();
//                            }
//                        }
//                    }
        }
        return 'OK';
    }

    /**
     * @inheritDoc
     */
    public function unlock_timeout(int $minute = 30): int
    {
        return 10;
    }
}
