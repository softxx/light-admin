<?php

namespace core\service\excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use core\exception\FailedException;
class Excel
{
    /**
     * 只允许业务导入明确支持的本地表格格式，避免 IOFactory 解析远程 wrapper 或异常文件类型。
     */
    protected const IMPORT_EXTENSIONS = [
        'csv' => true,
        'xls' => true,
        'xlsx' => true,
    ];

    protected $spreadsheet = null;

    protected $extension = 'xlsx';

    protected $start = 'A';

    protected $row = 1;

    protected $sheetsData = [];

    /**
     * 获取当前的工作薄数据
     *
     * false 代表获取全部工作薄数据
     *
     * @var bool
     */
    protected $currentActive = true;

    /**
     * @var array | string
     */
    protected $columns = [];

    /**
     * 导出文件
     *
     * @param ExcelContract $excel
     * @param $path
     * @param string $disk
     * @return string[]
     * @throws Exception
     */
    public function export($fileName = '', $columns = [], $data = [])
    {
        //设置列数据
        $this->setColumns($columns);
        // 设置表头数据
        $this->setExcelHeaders();
        // 设置单元格数据
        $this->setWorksheetData($data);
        $fileName = $fileName .  date('Y-m-d')  . '.' . $this->extension;
        //返回文件流
        return $this->outputFileStream($fileName);
    }



    /**
     * 导入
     * @param string|object $file 本地文件路径，或包含 getRealPath 的上传文件对象
     * @param bool $delFirstRow 是否删除第一行
     * @return Excel
     * @throws FailedException 当文件不存在、类型不支持或读取失败时抛出
     */
    public function import($file, bool $delFirstRow = true): Excel
    {
        $filePath = $this->resolveImportFilePath($file);

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            // 设置只读，导入场景只需要单元格数据，避免解析多余格式内容。
            $reader->setReadDataOnly(true);

            // 支持中文
            if (method_exists($reader, 'setInputEncoding')) {
                $reader->setInputEncoding('GBK');
            }

            $spreadsheet = $reader->load($filePath);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new FailedException($e->getMessage());
        }

        if ($this->currentActive) {
            $this->sheetsData = $spreadsheet->getActiveSheet()->toArray();
            if ($delFirstRow) {
                unset($this->sheetsData[0]);
            }
        } else {
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $this->sheetsData[] = $sheet->toArray();
            }
        }

        return $this;
    }

    /**
     * 解析导入文件的真实本地路径。
     *
     * PhpSpreadsheet 会根据传入路径探测读取器，这里先拒绝远程协议、异常路径和不支持的
     * 扩展名，降低解析恶意文件时触发 SSRF、XXE 或路径穿越类问题的风险。
     *
     * @param string|object $file 本地文件路径，或包含 getRealPath 的上传文件对象
     * @return string 规范化后的本地真实路径
     * @throws FailedException 当文件不可读或类型不在白名单时抛出
     */
    protected function resolveImportFilePath($file): string
    {
        $path = is_object($file) && method_exists($file, 'getRealPath')
            ? (string) $file->getRealPath()
            : (string) $file;

        if ($path === '' || preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $path)) {
            throw new FailedException('Excel 导入文件路径无效');
        }

        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            throw new FailedException('Excel 导入文件不存在或不可读');
        }

        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        if ($extension === '' && is_object($file) && method_exists($file, 'getOriginalExtension')) {
            $extension = strtolower((string) $file->getOriginalExtension());
        }

        if (!isset(self::IMPORT_EXTENSIONS[$extension])) {
            throw new FailedException('仅支持导入 xls、xlsx 或 csv 文件');
        }

        return $realPath;
    }



    /**
     * 获取 sheetsData
     *
     * @return array
     */
    public function getSheetsData(array $field = []): array
    {
        if (empty($field)) return $this->sheetsData;
        $data = [];
        foreach ($this->sheetsData as &$sheet) {
            $v = [];
            foreach ($field as $k => $header) {
                $v[$header] = $sheet[$k];
            }
            $data[] = $v;
        }
        return $data;
    }


    /**
     * 输出文件流
     *
     * @return mixed
     */
    public function outputFileStream($fileName)
    {
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:filename=" . urlencode($fileName));
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header("Access-Control-Expose-Headers: Content-Disposition");
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); 
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer = IOFactory::createWriter($this->spreadsheet, ucfirst($this->extension));
        $writer->save('php://output');
        exit;
    }


    /**
     * 获取活动工作薄
     *
     * @throws Exception
     * @return Worksheet
     */
    protected function getWorksheet()
    {
        return $this->getSpreadsheet()->getActiveSheet();
    }


    /**
     *  获取电子表格对象
     *
     * @return Spreadsheet
     */
    protected function getSpreadsheet()
    {
        if (!$this->spreadsheet) {
            $this->spreadsheet = new Spreadsheet();
        }
        return $this->spreadsheet;
    }


    /**
     * 设置 column 信息 
     *
     * @return array
     */
    protected function setColumns($columns)
    {
        if (is_string($columns)) {
            $array = explode(',', $columns);
            $list = [];
            foreach ($array as $value) {
                if (strpos($value, '|')) {
                    [$key, $title] = explode('|', $value);
                    $list[$key] = $title;
                }
            }
            $this->columns = $list;
        } else {
            $this->columns = $columns;
        }
    }


    /**
     *  设置表头数据
     *
     * @return Spreadsheet
     */
    public function setExcelHeaders()
    {
        $worksheet = $this->getWorksheet();
        $header = array_values($this->columns);
        $worksheet->fromArray($header, NULL, $this->start . $this->row);
        $this->incRow();
    }

    /**
     *  设置表格数据
     *
     * @return Spreadsheet
     */
    public function setWorksheetData(array $data)
    {
        $worksheet = $this->getWorksheet();
        $filed = array_keys($this->columns);
        $sheetsData = [];
        $is_assoc = count(array_filter($filed, 'is_string'));
        foreach ($data as $value) {
            foreach ($value as &$item) {
                if ($item == 0) $item = strval($item);
            }
            //是否是关联数组
            if ($is_assoc) {
                $val = array_pick(implode(',', $filed), $value);
                array_push($sheetsData, $val);
            } else {
                $worksheet->fromArray($value, null, $this->start . $this->row);
                $this->incRow();
            }
        }
        if ($is_assoc) {
            foreach ($sheetsData as $value) {
                $worksheet->fromArray($value, null, $this->start . $this->row);
                $this->incRow();
            }
        }
    }

    /**
     * 设置单元格宽度
     * @param array $width ['A' => 20,'B' => 20]
     * @return void
     */
    protected function setSheetWidth(array $width): Excel
    {
        foreach ($width as $sheet => $w) {
            $this->getWorksheet()->getColumnDimension($sheet)->setWidth($w);
        }
        return $this;
    }

    /**
     * 设置内存限制
     *
     * @return void
     */
    public function setMemoryLimit($memory): Excel
    {
        ini_set('memory_limit', $memory);
        return $this;
    }

    /**
     * set extension
     *
     * @param $extension
     * @return $this
     */
    public function setExtension($extension): Excel
    {
        $this->extension = $extension;

        return $this;
    }

    public function incRow()
    {
        ++$this->row;
    }
}
