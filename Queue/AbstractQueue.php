<?php
declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 作者：Administrator
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 * 日期：25/7/2023 16:55:13
 */

namespace Weline\Queue\Queue;

use Aiweline\DataPublication\Cache\PubCache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\DataObject\DataObject;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;
use Weline\Queue\Model\Queue;
use Weline\Queue\QueueInterface;

abstract class AbstractQueue extends DataObject implements QueueInterface
{
    public array $status = [];
    public int $total = 0;
    public string $item_id = 'spu';
    public bool $display = true;
    public int $validate_total = 0;
    protected array $current_item = [];
    protected int $current_index = 1;

    public ?Queue $queue;
    protected Printing $printing;
    protected CacheInterface $cache;

    public function __construct()
    {
        $this->cache = ObjectManager::getInstance(PubCache::class)->create();
        $this->cache->setStatus(true);
        $this->printing = ObjectManager::getInstance(Printing::class);
    }

    final function init(Queue &$queue, string $item_id = 'spu', bool $display = true): self
    {
        $this->queue = &$queue;
        $this->queue->init();
        $this->setData($queue->getData());
        $this->item_id = $item_id;
        $this->display = $display;
        $this->queue->setProcess('')
            ->setResult('运行中...')
            ->save();
        if ($this->display) {
            $this->printing->printing($this->printing->colorize($queue->getName(), 'green') . $this->printing->colorize('初始化...', 'blue'));
        }
        return $this;
    }

    final public function queue_values(Queue &$queue)
    {
        if ($this->display) {
            $this->printing->note(str_pad('-', 45) . '--属性初始化--' . str_pad('-', 45));
        }
        $values = [];
        foreach ($queue->getAttributes() as $attribute) {
            $values[$attribute->getCode()] = $attribute->getValue();
            if ($this->display) {
                $this->printing->success(
                    __('%1(%2): %3', [$attribute->getName(), $attribute->getCode(), $attribute->getValue()]));
            }
        }
        return $values;
    }

    final protected function queue_total(int $total = 0): self|int
    {
        if ($this->display) {
            $this->printing->note(__('队列数据总数：%1', [$total]));
        }
        if ($total == 0) {
            return $this->total;
        }
        $this->total          = $total;
        $this->validate_total = $total;
        return $this;
    }

    final protected function queue_validate_total(int $total = 0): self|int
    {
        if ($this->display) {
            $this->printing->note(__('队列数据总数(有效)：%1', [$total]));
        }
        if ($total == 0) {
            return $this->validate_total;
        }
        $this->queue_result((string)$total, '队列数据总数(有效)');
        $this->validate_total = $total;
        return $this;
    }


    final protected function queue_status_total(string $key = 'success'): int
    {
        return count($this->status[$key] ?? []);
    }

    /**
     * @DESC          # 更新队列状态
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2024/10/10 下午4:33
     * 参数区：
     * @return self
     * @throws \Weline\Framework\Database\Exception\ModelException
     */
    final protected function queue_process(): self
    {
        $sucess = $this->queue_status_total('success');
        $errors = $this->queue_status_total('error');
        $processed = $this->queue_status_total('processed');

        $percent = round(($processed / $this->total) * 100, 2);
        $msg    = __('总计: %1 条,有效: %2 条,成功: %3 条,失败: %4 条 当前：%5（%6）进度：%7%',
            [
                $this->total,
                $this->validate_total,
                $sucess, $errors,
                $this->current_item[$this->item_id] ?? '',
                $processed.'/'.$this->total,
                $percent
                ]);
        $this->queue->setProcess($msg);
        if ($this->display) {
            $this->printing->success($msg);
        }
        return $this;
    }

    /**
     * @DESC          # 更新队列状态
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 2024/10/10 下午4:33
     * 参数区：
     * @return self
     * @throws \Weline\Framework\Database\Exception\ModelException
     */
    final protected function queue_dislay(string $msg): self
    {
        $this->printing->success($msg);
        return $this;
    }


    final protected function queue_result(string $msg = '', string $key = ''): self
    {
        if ($this->display) {
            $this->printing->success($key . ':' . $msg);
        }
        $result = $this->queue->getResult();
        $this->queue->setResult($result . PHP_EOL . $key . ':' . $msg);
        return $this;
    }

    final protected function queue_update(string $msg = ''): self
    {
        if ($this->display) {
            $this->printing->success(__('更新进度: %1', ($this->queue_status_total('processed') / $this->total) * 100 . '%'));
        }
        if ($msg) {
            $this->queue_result($msg);
        }
        $this->queue->save();
        return $this;
    }

    final protected function queue_error(string $msg = '', string $key = 'error', bool $append_to_result = false): self
    {
        if ($this->display) {
            $rate = ($this->queue_status_total('processed') / $this->total) * 100;
            # 保留两位小数
            $rate = number_format($rate, 2, '.', '');
            $this->printing->success(__('更新进度: %1', $rate . '%'));
        }
        $this->status[$key][] = $msg;
        if ($append_to_result and $msg) {
            $this->queue_result($key . ':' . $msg);
        }
        return $this;
    }

    final protected function queue_fatal_error(string $msg = ''): void
    {
        if ($this->display) {
            $this->printing->success(__('致命错误: %s', [$msg]));
        }
        $this->queue->setStatus($this->queue::status_error)->save();
        throw new \Exception($this->queue->getResult());
    }

    final protected function queue_finished(array $status = []): void
    {
        if (isset($status['errors'])) {
            $this->status['error'] = $status['errors'];
        }
        if (isset($status['success'])) {
            $this->status['success'] = $status['success'];
        }
        $this->queue->setFinished(true)
            ->setStatus($this->queue::status_done);
        $this->status['finished'] = true;
        $this->queue_output();
        $this->queue_update();
        if ($this->display) {
            $this->printing->success(__('队列完成!'));
        }
    }

    /**
     * @DESC          # 设置结果
     *
     * @AUTH  秋枫雁飞
     * @EMAIL aiweline@qq.com
     * @DateTime: 25/7/2023 下午5:08
     * 参数区：
     * @param string $taskClass
     * @return array
     * @throws Exception
     * @throws ModelException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    final protected function queue_output(): string
    {
        if ($this->display) {
            $this->printing->success(__('输出结果...'));
        }
        $result              = [];
        $success_spreadsheet = new Spreadsheet();
        # region 生成成功结果文件
        foreach ($this->status as $status_key => $status) {
            $success_spreadsheet->removeSheetByIndex(0);
            # 非数组内容直接输入到result
            if (!is_array($status)) {
                $this->queue_result((string)$status, $status_key);
                continue;
            }
            # 写入excel 文件
            $save_dir = PUB . 'media' . DS . 'cron' . DS . 'export' . DS . 'queue' . DS;
            if (!is_dir($save_dir)) {
                mkdir($save_dir, 0777, true);
            }
            $sheetName = $status_key;
            $sheet     = $success_spreadsheet->createSheet();
            if (isset($status[0]) and is_array($status[0])) {
                $titles = array_keys($status[0]);
            } else {
                $titles[] = __('消息');
            }
            foreach ($titles as $title_key => $title) {
                $coordinateIndex = $title_key + 1;
                $cellName        = $sheet->getCellByColumnAndRow($coordinateIndex, 1)->getCoordinate();
                $sheet->setCellValue($cellName, $title);
            }
            foreach ($status as $row => $item) {
                $row          += 1;
                $column_index = 0;
                if (is_array($item)) {
                    foreach ($item as $key => $value) {
                        $column_index += 1;
                        $cellName     = $sheet->getCellByColumnAndRow($column_index, $row)->getCoordinate();
                        if (is_array($value)) {
                            $value = w_var_export($value, true);
                        }
                        $sheet->setCellValue($cellName, $value);
                    }
                } elseif (is_string($item)) {
                    $column_index += 1;
                    $cellName     = $sheet->getCellByColumnAndRow($column_index, $row)->getCoordinate();
                    $sheet->setCellValue($cellName, $item);
                }
            }
            # 写入文件名称
            $queue_dir = $save_dir . $this->queue->getId() . DS;
            $filename  = $status_key . '-queue-id-' . $this->queue->getId() . '.xlsx';
            if (!is_dir($queue_dir)) {
                mkdir($queue_dir, 0777, true);
            }
            $writer = new Xlsx($success_spreadsheet);

            $file_path = $queue_dir . $filename;
            $url       = '/pub/media/cron/export/queue/' . $this->queue->getId() . '/' . $filename;
            if (!is_file($file_path)) {
                touch($file_path);
            }
            $writer->save($file_path);
            $file_download = '<a href="' . $url . '" download="' . $filename . '" target="_blank">' . __('下载：') . '</a><b>' . __('总计：') . count($status) . '</b>';
            $this->queue_result($file_download, (string)$status_key);
        }
        # endregion
        return $this->queue->getResult();
    }

    final protected function queue_validate(Queue &$queue): bool
    {
        if ($this->display) {
            $this->printing->success(__('队列校验...'));
        }
        $this->queue = $queue;
        # 自带属性检测 如果有属性类型是 op_select_site 和 op_select_sites 则根据类型必填性检测值
        $attributes = $queue->getAttributes();
        $has_error  = [];
        foreach ($attributes as $attribute) {
            $res = $queue->validateAttribute($attribute);
            if (is_string($res)) {
                $has_error[] = $res;
            }
        }
        if ($has_error) {
            $this->queue_fatal_error(implode(PHP_EOL, $has_error));
        }
        return true;
    }

    /*队列条目信息*/

    final protected function item_start(string|array $current_item, array $merge = []): self
    {
        if (is_string($current_item)) {
            $item               = [$this->item_id => $current_item, 'ok' => 0];
            $this->current_item = &$item;
        } elseif (is_array($current_item)) {
            if (!isset($current_item[$this->item_id])) {
                throw new \Exception('$current_item 必须包含 ' . $this->item_id);
            }
            if (!isset($current_item['ok'])) {
                $current_item['ok'] = 0;
            }
            $this->current_item = &$current_item;
        }
        if ($merge) {
            $current_item       = array_merge($this->current_item, $merge);
            $this->current_item = &$current_item;
        }
        if ($this->current_item) {
            $this->status['processed'][] = &$this->current_item;
        }
        $this->queue_process();
        return $this;
    }

    final protected function item_success(string $msg = ''): self
    {
        if ($this->current_item) {
            $this->current_item['ok'] = 1;
            if (isset($this->current_item['error'][$this->current_item[$this->item_id]])) {
                $this->current_item['msg'][$this->current_item[$this->item_id]] = $this->current_item['msg'][$this->current_item[$this->item_id]] . PHP_EOL . $msg;
            } else {
                $this->current_item['msg'][$this->current_item[$this->item_id]] = $msg;
            }
            $this->status['sucess'][$this->current_item[$this->item_id]] = $this->current_item;
        }
        return $this;
    }

    final protected function item_error(string $msg = ''): self
    {
        if ($this->current_item) {
            $this->current_item['ok'] = 0;
            if (isset($this->current_item['msg'][$this->current_item[$this->item_id]])) {
                $this->current_item['msg'][$this->current_item[$this->item_id]] = $this->current_item['msg'][$this->current_item[$this->item_id]] . PHP_EOL . $msg;
            } else {
                $this->current_item['msg'][$this->current_item[$this->item_id]] = $msg;
            }
            $this->status['error'][$this->current_item[$this->item_id]] = $this->current_item;
        }
        return $this;
    }

    final public function get_file_data(string $file, array|string $keys = []): array|string
    {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }
        if (!is_file($file)) {
            $sourceFile = PUB . 'media' . DS . $file;
            if (!file_exists($sourceFile)) {
                return [];
            }
            $file = $sourceFile;
        }
        $reader = IOFactory::load($file);
        $rows   = $reader->getSheet(0)->toArray();
        $data   = [];
        if (empty($keys)) {
            $data = $rows;
        } else {
            foreach ($rows as $k => $row) {
                if ($k === 0) {
                    continue;
                }
                if (empty($row[0])) {
                    continue;
                }
                $tmpData = [];
                if ($keys) {
                    foreach ($keys as $key => $value) {
                        $tmpData[$value] = $row[$key] ?? '';
                    }
                } else {
                    $tmpData = $row;
                }
                if (empty($tmpData)) {
                    continue;
                }
                $data[] = $tmpData;
            }
        }
        return $data;
    }

}