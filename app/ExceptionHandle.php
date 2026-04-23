<?php

namespace app;

use core\exception\FailedException;
use core\facade\Json;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 搴旂敤寮傚父澶勭悊绫?
 */
class ExceptionHandle extends Handle
{
    /**
     * 涓嶉渶瑕佽褰曚俊鎭紙鏃ュ織锛夌殑寮傚父绫诲垪琛?
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
        FailedException::class,
    ];

    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    public function render($request, Throwable $e): Response
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($e instanceof FailedException) {
            return $this->toHttpResponse(fn() => $e->render());
        }

        if ($e instanceof ValidateException) {
            return $this->toHttpResponse(fn() => Json::error($e->getError()));
        }

        if ($request instanceof Request && $request->isEncryptedRequest()) {
            return $this->toHttpResponse(
                fn() => Json::result(
                    [],
                    0,
                    app()->isDebug() ? $e->getMessage() : config('app.error_message'),
                    500
                )
            );
        }

        return parent::render($request, $e);
    }

    private function toHttpResponse(callable $resolver): Response
    {
        try {
            return $resolver();
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        }
    }
}
