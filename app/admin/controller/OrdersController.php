<?php

namespace app\admin\controller;

use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Ramsey\Uuid\Uuid;
use SplFileInfo;
use support\Db;
use support\Request;
use support\Response;
use app\admin\model\Orders;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Yansongda\Pay\Pay;

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
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        $roles = admin('roles');
        if (in_array(3, $roles)) {
            $show = true;
        }else{
            $show = false;
        }
        return view('orders/index',['show'=>$show]);
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
            }catch (\Throwable $e) {
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
            $start_time = $request->post('start_time');
            $end_time = $request->post('end_time');
            $file = $request->post('file');
            if (empty($file)) {
                return $this->fail('请选择文件');
            }
            $start_time = Carbon::parse($start_time);
            $end_time = Carbon::parse($end_time);
            if ($start_time->isPast()) {
                return $this->fail('开始时间必须大于当前时间');
            }
            if ($start_time->gt($end_time)) {
                return $this->fail('开始时间必须小于结束时间');
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);

            $filePath = public_path(str_replace('/app/admin/', '', $file),'admin');
            $admin_id = admin_id();
            //实例化reader
            if (!in_array($ext, ['xls', 'xlsx'])) {
                return $this->fail('文件格式错误');
            }
            if ($ext === 'csv') {
                $file = fopen($file->getRealPath(), 'r');
                $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
                $fp = fopen($filePath, 'w');
                $n = 0;
                while ($line = fgets($file)) {
                    $line = rtrim($line, "\n\r\0");
                    $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                    if ($encoding !== 'utf-8') {
                        $line = mb_convert_encoding($line, 'utf-8', $encoding);
                    }
                    if ($n == 0 || preg_match('/^".*"$/', $line)) {
                        fwrite($fp, $line . "\n");
                    } else {
                        fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                    }
                    $n++;
                }
                fclose($file) || fclose($fp);

                $reader = new Csv();
            } elseif ($ext === 'xls') {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }
            try {
                if (!$PHPExcel = $reader->load($filePath)) {
                    return $this->fail('文件格式错误');
                }
                // 读取文件中的第一个工作表
                $currentSheet = $PHPExcel->getSheet(0);
                $allColumn = 'B'; // 取得最大的列号
                $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行
                $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
                // 定义字段名
                $columns = ['ordersn', 'amount'];
                // 读取后续行的数据
                $insert = [];
                for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
                    $rowValues = [];
                    for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                        $cellAddress = Coordinate::stringFromColumnIndex($currentColumn) . $currentRow;
                        $val = $currentSheet->getCell($cellAddress)->getValue();
                        $rowValues[$columns[$currentColumn - 1]] = $val;
                    }
                    $insert[] = $rowValues;
                }
                DB::connection('plugin.admin.mysql')->beginTransaction();
                try {
                    foreach ($insert as $item) {
                        $ordersn = $item['ordersn'];
                        $amount = $item['amount'];
                        if ($this->model->where('ordersn', $ordersn)->exists()){
                            continue;
                        }
                        $data = [
                            'admin_id' => $admin_id,
                            'ordersn' => $ordersn,
                            'amount' => $amount,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                        ];
                        Orders::create($data);
                    }
                    DB::connection('plugin.admin.mysql')->commit();
                } catch (\Exception $e) {
                    DB::connection('plugin.admin.mysql')->rollBack();
                    throw $e;
                }
            } catch (\Throwable $exception) {
                return $this->fail($exception->getMessage());
            }
            return $this->success('导入成功');
        }
        return view('orders/import');

    }


}
