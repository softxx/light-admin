<?php

namespace app\adminapi\controller\system;

use app\service\system\FileService;
use core\base\BaseController;

class File extends BaseController
{
    private FileService $service;

    public function __construct(FileService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 文件列表
     *
     * @return void
     */
    public function index()
    {
        $this->success($this->service->getList());
    }

    /**
     * 上传通用文件
     *
     * @return void
     */
    public function uploadFile()
    {
        $file = $this->request->file('file');
        $this->success($this->service->uploadFile($file));
    }

    /**
     * 上传图片
     *
     * @return void
     */
    public function uploadImg()
    {
        $file = $this->request->file('file');
        $this->success($this->service->uploadImg($file));
    }

    /**
     * 上传附件
     *
     * @return void
     */
    public function uploadAttachment()
    {
        $file = $this->request->file('file');
        $this->success($this->service->uploadAttachment($file));
    }

    /**
     * 删除文件记录和本地物理文件
     *
     * @return void
     */
    public function delete()
    {
        $id = $this->request->param('id');
        $result = $this->service->delete($id);
        $result ? $this->success('删除成功') : $this->error('删除失败');
    }
}
