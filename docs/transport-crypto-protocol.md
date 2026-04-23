# 请求响应加密协议说明

## 1. 目的

当前项目的请求响应加密协议，已经把部分容易暴露含义的字段做了编号化或短字段化处理，主要目标是：

- 降低接口一眼就能看出加密方案的直观性
- 方便后续协议升级时统一扩展
- 让前后端都只依赖稳定的协议编号，而不是明文算法名

这套协议当前只用于：

- `JSON` 请求
- `JSON` 响应

以下内容不走这套加密：

- `Authorization` 请求头
- 上传接口
- 导出接口
- `/adminapi/crypto/meta`
- `/adminapi/system_setting/public`

## 2. 当前套件编号

当前只启用一个协议套件：

| 编号 | 含义 |
| --- | --- |
| `1` | `SM2 + SM4-CBC + SM3(HMAC)` |

说明：

- `SM2` 用于加密本次请求的 `SM4` 对称密钥
- `SM4-CBC` 用于加密业务数据
- `SM3(HMAC)` 用于完整性校验

当前项目中，请求包里只传 `s: 1`，不再传明文 `alg` 字段。

## 3. `/adminapi/crypto/meta` 字段说明

`/adminapi/crypto/meta` 是前端发起加密请求前的元信息接口。

当前返回的是最小公开字段版：

| 字段 | 含义 | 当前说明 |
| --- | --- | --- |
| `e` | 是否启用加密 | `1` 开启，`0` 关闭 |
| `r` | 协议版本 | 当前为 `1` |
| `s` | 协议套件编号 | 当前为 `1` |
| `k` | 公钥版本号 | 例如 `server-key-xxxx` |
| `p` | 后端 `SM2` 公钥 | 前端用来加密本次请求的 `SM4 key` |
| `x` | `SM2 ASN.1` 开关 | 当前固定为 `0` |
| `a` | `SM4 key` 长度 | 当前为 `16` 字节 |
| `b` | `SM4 iv` 长度 | 当前为 `16` 字节 |

示例：

```json
{
  "e": 1,
  "r": 1,
  "s": 1,
  "k": "server-key-5c59ae7eb265",
  "p": "04df4ce2...",
  "x": 0,
  "a": 16,
  "b": 16
}
```

## 4. 请求包字段说明

前端对受保护接口发起请求时，外层包结构如下：

| 字段 | 含义 |
| --- | --- |
| `v` | 协议版本 |
| `s` | 协议套件编号 |
| `kid` | 公钥版本号 |
| `ts` | 请求时间戳 |
| `nonce` | 本次请求随机串 |
| `ek` | 用后端 `SM2` 公钥加密后的 `SM4 key` |
| `iv` | `SM4-CBC` 使用的随机向量，`base64url` |
| `ct` | 业务密文，`base64url` |
| `mac` | `SM3(HMAC)` 校验值 |

示例：

```json
{
  "v": 1,
  "s": 1,
  "kid": "server-key-5c59ae7eb265",
  "ts": 1776921255,
  "nonce": "0f4dbd2b2f0f1c75e8f6d7c5eec4c517",
  "ek": "04ab...",
  "iv": "8wE6ElN8m5k3W4kA6t1D6g",
  "ct": "6QuS2oJmXvBq...",
  "mac": "a4f0f4d7282a..."
}
```

## 5. 响应包字段说明

后端返回加密响应时，外层包结构如下：

| 字段 | 含义 |
| --- | --- |
| `v` | 协议版本 |
| `enc` | 是否为加密响应，固定为 `1` |
| `ts` | 响应时间戳 |
| `nonce` | 响应随机串 |
| `iv` | 响应使用的 `SM4-CBC` 随机向量，`base64url` |
| `ct` | 响应密文，`base64url` |
| `mac` | 响应完整性校验值 |

说明：

- 响应包里不再出现 `alg`
- 响应包里也不需要 `s`
- 响应继续复用本次请求解出来的 `SM4 key`

## 6. AAD 规则

虽然当前是 `SM4-CBC + SM3(HMAC)`，项目里仍然保留了固定的签名拼接规则。

### 请求侧

```text
REQ
METHOD
PATH
ts
nonce
```

### 响应侧

```text
RES
PATH
request_nonce
ts
nonce
```

## 7. 编码规则

当前协议的编码规则如下：

| 字段 | 编码方式 |
| --- | --- |
| `ek` | `hex` |
| `iv` | `base64url` |
| `ct` | `base64url` |
| `mac` | 小写 `hex` |
| `nonce` | 小写 `hex` |

## 8. 兼容性说明

当前版本已经取消对旧 `alg` 明文字段的兼容：

- 请求包必须带 `s`
- 只带 `alg` 的旧格式请求会被拒绝

典型错误：

| 场景 | 返回 |
| --- | --- |
| 缺少 `s` | `4600` |
| `s` 非法或不支持 | `4608` |

## 9. 当前代码位置

后续如果要改编号映射，可以优先看这几个文件：

- 后端协议入口：[core/service/crypto/TransportCryptoService.php](D:/Project-xian/light-admin/core/service/crypto/TransportCryptoService.php)
- 后端配置：[config/crypto.php](D:/Project-xian/light-admin/config/crypto.php)
- 前端加解密实现：[web_art/src/utils/http/transport-crypto.ts](D:/Project-xian/light-admin/web_art/src/utils/http/transport-crypto.ts)

## 10. 后续扩展建议

如果后面要继续扩展，不建议重新引入明文算法名，建议继续走编号化：

- 新增协议时，扩充 `s` 的编号映射
- 保持 `/adminapi/crypto/meta` 只下发最小必要字段
- 需要升级时，用 `r` 管协议版本，用 `s` 管具体套件
