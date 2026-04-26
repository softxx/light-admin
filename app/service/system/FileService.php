<?php

namespace app\service\system;

use app\model\system\File as FileModel;
use core\base\BaseService;
use core\exception\FailedException;
use core\service\upload\UploadService;
use think\facade\Config;

class FileService extends BaseService
{
    private UploadService $upload;

    public function __construct(UploadService $upload, FileModel $model)
    {
        $this->upload = $upload;
        $this->model = $model;
    }

    /**
     * 获取文件分页列表。
     *
     * 支持模型上的动态筛选条件，供后台文件管理页使用。
     *
     * @return mixed
     */
    public function getList()
    {
        return $this->model->search()->order('id', 'desc')->with(['user'])->paginate();
    }

    /**
     * 上传通用文件。
     *
     * @param mixed $file
     * @return array
     */
    public function uploadFile($file)
    {
        return $this->upload->checkFiles()->upload($file);
    }

    /**
     * 上传图片。
     *
     * @param mixed $file
     * @return array
     */
    public function uploadImg($file)
    {
        return $this->upload->checkImages()->upload($file);
    }

    /**
     * 上传附件。
     *
     * @param mixed $file
     * @return array
     */
    public function uploadAttachment($file)
    {
        return $this->upload->checkAttachment()->upload($file);
    }

    /**
     * 删除文件。
     *
     * 本地存储文件会同步删除物理文件；无法解析为本地文件时只删除数据库记录，
     * 便于后续接入 OSS/CDN 时保持兼容。
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool
    {
        $file = $this->model->findOrFail($id);
        $localPath = $this->resolveLocalStoragePath($file);

        if ($localPath && is_file($localPath) && !@unlink($localPath)) {
            throw new FailedException('删除文件失败，请检查文件权限');
        }

        return (bool) $this->model->deleteBy($id);
    }

    /**
     * 根据文件 URL 反推出本地存储路径。
     *
     * 只允许解析 public/storage 目录下的文件，避免删除越权路径。
     *
     * @param FileModel $file
     * @return string|null
     */
    private function resolveLocalStoragePath(FileModel $file): ?string
    {
        $urlPath = parse_url((string) $file->url, PHP_URL_PATH) ?: (string) $file->url;
        $urlPath = str_replace('\\', '/', $urlPath);
        $storageUrl = trim(str_replace('\\', '/', (string) Config::get('filesystem.disks.local.url', '/storage')), '/');
        $relativePath = ltrim($urlPath, '/');

        if ($storageUrl !== '' && str_starts_with($relativePath, $storageUrl . '/')) {
            $relativePath = substr($relativePath, strlen($storageUrl) + 1);
        } elseif (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, strlen('storage/'));
        } else {
            return null;
        }

        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $root = rtrim((string) Config::get('filesystem.disks.local.root', ''), '/\\');
        if ($root === '') {
            return null;
        }

        // 用真实目录校验最终路径仍在本地存储根目录内。
        $candidate = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $rootRealPath = realpath($root);
        $candidateDirRealPath = realpath(dirname($candidate));

        if (!$rootRealPath || !$candidateDirRealPath) {
            return null;
        }

        $rootCompare = rtrim(strtolower(str_replace('\\', '/', $rootRealPath)), '/') . '/';
        $dirCompare = rtrim(strtolower(str_replace('\\', '/', $candidateDirRealPath)), '/') . '/';

        if ($dirCompare !== $rootCompare && !str_starts_with($dirCompare, $rootCompare)) {
            return null;
        }

        return $candidate;
    }
}
