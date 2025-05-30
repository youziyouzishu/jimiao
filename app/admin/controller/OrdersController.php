<?php

namespace app\admin\controller;

use app\admin\exception\ImportExceprion;
use app\admin\model\Admin;
use app\admin\model\AdminRealinfo;
use app\admin\model\Orders;
use app\api\service\Pay;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use plugin\admin\app\controller\Crud;
use support\Db;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Webman\RateLimiter\Limiter;
use Webman\RateLimiter\RateLimitException;

/**
 * 口令管理
 */
class OrdersController extends Crud
{

    /**
     * @var Orders
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Orders;
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->with(['admin']);
        if (in_array(3, admin('roles'))) {
            $query->where('admin_id', admin_id());
        }
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('orders/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $num = $request->post('num');
            $amount = $request->post('amount');
            $start_time = $request->post('start_time');
            $end_time = $request->post('end_time');
            if ($num <= 0) {
                return $this->fail('数量必须大于0');
            }
            if ($amount <= 0) {
                return $this->fail('金额必须大于0');
            }
            $start_time = Carbon::parse($start_time);
            $end_time = Carbon::parse($end_time);
            if ($start_time->isPast()) {
                return $this->fail('开始时间必须大于当前时间');
            }
            if ($start_time->gt($end_time)) {
                return $this->fail('开始时间必须小于结束时间');
            }

            $admin_id = admin_id();
            Db::connection('plugin.admin.mysql')->beginTransaction();
            try {
                for ($i = 1; $i <= $num; $i++) {
                    $ordersn = mb_strtoupper(bin2hex(random_bytes(8)));
                    Orders::create([
                        'admin_id' => $admin_id,
                        'ordersn' => $ordersn,
                        'amount' => $amount,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                    ]);
                }
                Db::connection('plugin.admin.mysql')->commit();
            } catch (\Throwable $e) {
                Db::connection('plugin.admin.mysql')->rollback();
                return $this->fail($e->getMessage());
            }
            return $this->success();
        }
        return view('orders/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $id = $request->post('id');
            $status = $request->post('status');
            $row = $this->model->find($id);
            if ($status == 3 && $row->status != 0) {
                return $this->fail('状态已变更,请刷新');
            }
            return parent::update($request);
        }
        return view('orders/update');
    }


    /**
     * 导入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    function import(Request $request)
    {

        if ($request->method() === 'POST') {
            $realinfo = AdminRealinfo::where('admin_id', admin_id())->where('status', 1)->first();
            if (empty($realinfo)) {
                return $this->fail('请先完善实名认证信息');
            }
            $start_time = $request->post('start_time');
            $end_time = $request->post('end_time');
            $confirm = $request->post('confirm', 'no');
            $file = current($request->file());
            if (!$file || !$file->isValid()) {
                return $this->json(1, '未找到文件');
            }

            $start_time = Carbon::parse($start_time);
            $end_time = Carbon::parse($end_time);
            if ($start_time->isPast()) {
                $start_time = Carbon::now();
            }
            if ($start_time->gt($end_time)) {
                return $this->fail('结束时间必须大于现在时间');
            }

            $ext = $file->getUploadExtension();
            if (!in_array($ext, ['xls', 'xlsx'])) {
                return $this->fail('文件格式错误');
            }

            if ($ext === 'xls') {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }
            if (!$PHPExcel = $reader->load($file->getRealPath())) {
                return $this->fail('文件格式错误');
            }
            if ($confirm == 'yes'){
                try {
                    #限流器 每个用户1秒内只能请求1次
                    Limiter::check('admin_' . admin_id(), 1, 5);
                } catch (RateLimitException $e) {
                    return $this->fail('请求频繁');
                }
            }
            $admin_id = admin_id();
            DB::connection('plugin.admin.mysql')->beginTransaction();
            try {
                // 读取文件中的第一个工作表
                $currentSheet = $PHPExcel->getSheet(0);
                $allColumn = 'B'; // 取得最大的列号
                $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行
                $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
                // 定义字段名
                $columns = ['ordersn', 'amount'];
                // 读取后续行的数据
                $insert = [];
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    $rowValues = [];
                    for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                        $cellAddress = Coordinate::stringFromColumnIndex($currentColumn) . $currentRow;
                        $val = $currentSheet->getCell($cellAddress)->getValue();
                        $rowValues[$columns[$currentColumn - 1]] = $val;
                    }
                    $insert[] = $rowValues;
                }

                $data = [];
                $total_amount = 0;
                $rows = 0;
                $total_service_amount = '0';
                foreach ($insert as $key => $item) {
                    $ordersn = $item['ordersn'];
                    $amount = $item['amount'];

                    if (empty($ordersn) || $this->model->where('ordersn', $ordersn)->exists()) {
                        unset($insert[$key]);
                        continue;
                    }
                    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
                        unset($insert[$key]);
                        continue;
                    }

                    $service_amount = $amount <= 10 ? '0.09' : bcmul($amount, '0.009', 3);
                    $service_amount = $this->formatDecimalWithCeil($service_amount);

                    $data[] = [
                        'admin_id' => $admin_id,
                        'ordersn' => $ordersn,
                        'amount' => $amount,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'service_amount' => $service_amount,
                    ];

                    $total_amount += $amount;
                    $rows++;
                    $total_service_amount = bcadd($total_service_amount, $service_amount, 2);

                }
                $use_amount = bcadd($total_amount, $total_service_amount, 2);
                $msg = '总计导入' . $rows . '条订单,总金额' . $total_amount . '元,' . '服务费' . $total_service_amount . '元,' . '扣款金额' . $use_amount . '元';
                if ($confirm == 'no') {
                    throw new ImportExceprion($msg);
                }

                foreach ($data as $item) {
                    Orders::create($item);
                }


                DB::connection('plugin.admin.mysql')->commit();
            } catch (ImportExceprion $e) {
                DB::connection('plugin.admin.mysql')->rollBack();
                return $this->success($e->getMessage());
            } catch (\Throwable $exception) {
                DB::connection('plugin.admin.mysql')->rollBack();
                return $this->fail($exception->getMessage());
            } finally {
                // 删除临时文件（如果是远程文件）
                if (isset($tmpfname) && file_exists($tmpfname)) {
                    unlink($tmpfname);
                }
            }
            return $this->success('导入成功');
        }
        return view('orders/import');
    }


    private function formatDecimalWithCeil($numberStr)
    {
        // 将字符串转换为浮点数
        $floatVal = (float)$numberStr;

        // 分离整数和小数部分
        $parts = explode('.', number_format($floatVal, 10, '.', '')); // 精确到10位避免精度问题
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // 判断是否超过两位小数
        if (strlen($decimalPart) >= 3) {
            // 超过两位小数时，使用 ceil 并指定保留两位
            $rounded = ceil($floatVal * 100) / 100;
            return number_format($rounded, 2, '.', '');
        } else {
            // 否则直接保留两位小数
            return number_format($floatVal, 2, '.', '');
        }
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $ids = $this->deleteInput($request);
        $this->doDelete($ids);
        return $this->json(0);
    }

    /**
     * 批量作废
     * @param Request $request
     * @return Response
     */
    public function refund(Request $request): Response
    {
        $ids = $this->deleteInput($request);
        DB::connection('plugin.admin.mysql')->beginTransaction();
        try {
            $this->model->whereIn('id', $ids)->where('status', 0)->each(function (Orders $item) {
                $item->status = 3;
                $item->save();
            });

            DB::connection('plugin.admin.mysql')->commit();
        } catch (\Throwable $e) {
            DB::connection('plugin.admin.mysql')->rollBack();
            return $this->fail($e->getMessage());
        }
        return $this->json(0);
    }


}
