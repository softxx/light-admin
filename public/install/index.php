<?php
declare(strict_types=1);
use core\install\InstallGuard;
use core\install\InstallService;
require __DIR__ . '/bootstrap.php';
$guard = app()->make(InstallGuard::class);
if ($guard->isInstalled()) {
    http_response_code(404);
    exit('Not Found');
}
$installService = app()->make(InstallService::class);
$bootstrapJson = json_encode(
    $installService->getBootstrapPayload(),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?><!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>系统安装向导</title>
  <style>
    :root{--card:#fff;--soft:#f5f8ff;--line:#d9e1f2;--text:#182033;--sub:#62708a;--brand:#1f5ec8;--brand2:#173f8d;--ok:#15803d;--warn:#b45309;--bad:#c2410c}
    *{box-sizing:border-box}body{margin:0;min-height:100vh;font:14px/1.65 "Segoe UI Variable","PingFang SC","Microsoft YaHei",sans-serif;color:var(--text);background:linear-gradient(180deg,#f7f9ff,#edf2fb)}
    h1,h2,h3,p{margin:0}a{text-decoration:none}code{padding:0 6px;border-radius:8px;background:#ebf2ff;color:var(--brand2)}
    .shell{width:min(1100px,calc(100% - 24px));margin:20px auto;padding:20px;border:1px solid #fff;border-radius:28px;background:rgba(255,255,255,.72);box-shadow:0 24px 60px rgba(31,61,128,.12)}
    .shell.focus-step .header,.shell.focus-step .steps{display:none}
    .shell.focus-step .panel{margin-top:0}
    .main{background:var(--card);border:1px solid var(--line);border-radius:24px;box-shadow:0 14px 34px rgba(20,39,84,.06);padding:24px}
    .header{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}.tag{display:inline-flex;align-items:center;min-height:30px;padding:0 12px;border-radius:999px;background:#ebf2ff;color:var(--brand);font-size:12px;font-weight:700;letter-spacing:.04em}
    .title{margin-top:14px;font-size:32px;line-height:1.15}.lead{margin-top:10px;max-width:760px;color:var(--sub)}.muted{color:var(--sub)}
    .meta{display:grid;gap:10px;min-width:176px}.meta-item{padding:12px 14px;border:1px solid var(--line);border-radius:16px;background:var(--soft)}.meta-item small{display:block;color:var(--sub)}.meta-item strong{display:block;margin-top:6px;font-size:17px}
    .steps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:20px}.step{padding:14px;border:1px solid var(--line);border-radius:18px;background:var(--soft)}.step.active{border-color:#b8c8ed;background:#eef4ff}.step.done{border-color:#c7ead4;background:#f4fbf7}
    .n{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;font-weight:800;color:var(--brand);background:#fff;border:1px solid #bfd0f5}.step.done .n{background:linear-gradient(135deg,#20a56d,#15803d);color:#fff;border-color:transparent}
    .step b{display:block;margin-top:10px}.step span{display:block;margin-top:4px;color:var(--sub);font-size:12px}
    .panel{display:none;margin-top:22px}.panel.active{display:block}.pt{margin-top:14px;font-size:28px;line-height:1.15}
    .g3,.overview,.core,.form,.credentials,.log{display:grid;gap:12px}.g3{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:18px}.overview{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:16px}.core{grid-template-columns:repeat(4,minmax(0,1fr));margin-top:12px}
    .box,.stat,.core-item,.wrap,.summary,.board,.success-box{padding:16px;border-radius:20px;border:1px solid var(--line);background:var(--soft)}.box p,.stat p,.core-item p{margin-top:8px;color:var(--sub)}.stat small{display:block;color:var(--sub)}.stat strong{display:block;margin-top:6px;font-size:24px}
    .core-item{min-height:108px}.core-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}.core-name{font-size:14px;font-weight:700}.core-text{margin-top:10px;color:var(--sub);font-size:12px;line-height:1.65;word-break:break-word}
    .chips{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-top:12px}.chip{display:flex;align-items:center;gap:8px;min-height:34px;padding:0 10px;border-radius:12px;background:#fff;font-size:12px;font-weight:700}.dot{width:9px;height:9px;border-radius:99px;flex:none}
    .ok{color:var(--ok);background:rgba(21,128,61,.12)}.bad{color:var(--bad);background:rgba(194,65,12,.12)}.warn{color:var(--warn);background:rgba(180,83,9,.12)}.chip.ok .dot{background:var(--ok)}.chip.bad .dot{background:var(--bad)}
    .pill{display:inline-flex;align-items:center;justify-content:center;min-width:72px;height:30px;padding:0 12px;border-radius:999px;font-size:12px;font-weight:700}
    .summary{margin-top:12px}.summary-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
    .actions,.links{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:20px}
    .btn,.link{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 18px;border:0;border-radius:999px;font-size:14px;font-weight:700;text-align:center}.btn{cursor:pointer}.btn.primary{color:#fff;background:linear-gradient(135deg,var(--brand),var(--brand2));box-shadow:0 12px 24px rgba(31,94,200,.22)}.btn.light,.link{color:var(--brand);background:#ebf2ff}.btn:disabled{opacity:.6;cursor:not-allowed}
    .form{grid-template-columns:repeat(2,minmax(0,1fr));margin-top:18px}.field{display:grid;gap:8px}.field label{font-size:13px;font-weight:700;color:var(--sub)}.field input{height:46px;padding:0 14px;border:1px solid var(--line);border-radius:16px;background:#fff;color:var(--text);font-size:14px}.field input:focus{outline:none;border-color:#9bb4ea;box-shadow:0 0 0 4px rgba(31,94,200,.08)}.field input.is-invalid{border-color:var(--bad);box-shadow:0 0 0 4px rgba(194,65,12,.08)}
    .hint{font-size:12px;line-height:1.55;color:var(--sub)}.hint strong{color:var(--brand2)}.field-error{min-height:18px;font-size:12px;line-height:1.5;color:var(--bad)}
    .note{margin-top:12px;color:var(--sub);font-size:13px}.board{margin-top:16px}.board h3{font-size:16px}.board p{margin-top:8px;color:var(--sub)}.log{margin-top:12px}
    .log-item{display:flex;gap:12px;padding:12px 14px;border-radius:14px;background:#fff}.log-dot{width:10px;height:10px;border-radius:99px;background:var(--brand);margin-top:6px;box-shadow:0 0 0 5px rgba(31,94,200,.12)}.meta-t{margin-top:4px;color:var(--sub);font-size:12px}
    .banner{display:flex;justify-content:space-between;gap:16px;align-items:center;padding:18px;margin-top:18px;border-radius:18px;background:linear-gradient(135deg,rgba(34,197,94,.18),rgba(14,165,233,.12));color:#14532d}.banner strong{font-size:20px}.banner p{margin-top:6px;color:#25634e}
    .credentials{grid-template-columns:repeat(2,minmax(0,1fr));margin-top:16px}.cred{padding:16px;border-radius:18px;background:#fff}.cred small{display:block;color:var(--sub);text-transform:uppercase;letter-spacing:.06em}.cred strong{display:block;margin-top:8px;font-size:18px}
    .success-box{margin-top:16px}
    @media (max-width:980px){.header{flex-direction:column}.steps,.g3,.overview,.core,.chips,.form,.credentials{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="shell">
    <main class="main">
      <div class="header">
        <div>
          <div class="tag">Install Wizard</div>
          <h1 class="title">Light Admin 系统安装向导</h1>
          <p class="lead">安装器会按步骤完成系统介绍、环境检查、数据库配置与最终安装。安装成功后页面会停留在结果页，不会自动跳转。</p>
        </div>
        <div class="meta">
          <div class="meta-item"><small>当前步骤</small><strong id="metricStep">系统介绍</strong></div>
          <div class="meta-item"><small>环境状态</small><strong id="metricEnv">待检查</strong></div>
        </div>
      </div>

      <div class="steps">
        <div class="step active" data-step-item="1"><div class="n">1</div><b>系统介绍</b><span>先看安装规则和流程。</span></div>
        <div class="step" data-step-item="2"><div class="n">2</div><b>环境检查</b><span>检查 PHP 依赖与目录权限。</span></div>
        <div class="step" data-step-item="3"><div class="n">3</div><b>数据库配置</b><span>测试连接后再执行安装。</span></div>
        <div class="step" data-step-item="4"><div class="n">4</div><b>安装完成</b><span>保留成功页，手工跳转。</span></div>
      </div>

      <section class="panel active" data-step-panel="1">
        <div class="tag">Step 1</div>
        <h2 class="pt">先快速了解这次安装会做什么</h2>
        <p class="lead">安装器会先检查当前服务器是否满足运行条件，再让你填写数据库连接信息。确认空库后，系统会自动写入 <code>.env</code> 数据库配置并执行 <code>sql/install.sql</code>，最后停留在安装成功页等待你手工跳转。</p>
        <div class="g3">
          <div class="box"><h3>环境优先</h3><p>先检查 PHP 版本、扩展、安装 SQL 文件和目录权限，再继续安装。</p></div>
          <div class="box"><h3>空库安装</h3><p>安装器只接受空库初始化，不允许在已有表的数据库上覆盖安装。</p></div>
          <div class="box"><h3>完成页停留</h3><p>安装成功后保留结果页，给出后台登录手工跳转按钮。</p></div>
        </div>
        <div class="actions"><button id="step1NextBtn" class="btn primary" type="button">下一步：检查环境</button></div>
      </section>

      <section class="panel" data-step-panel="2">
        <div class="tag">Step 2</div>
        <h2 class="pt">确认当前 PHP 环境是否满足安装要求</h2>
        <p class="lead">这里改成了概览卡片加紧凑检查区的形式。核心检查单独展示，扩展则压缩成一屏内的状态网格，尽量避免整页向下滚动。</p>
        <div class="overview">
          <div class="stat"><small>检查总数</small><strong id="envTotalCount">0</strong><p>当前安装器需要验证的环境项目总数。</p></div>
          <div class="stat"><small>已通过</small><strong id="envOkCount">0</strong><p>已满足要求的项目数量。</p></div>
          <div class="stat"><small>待修复</small><strong id="envFailCount">0</strong><p>仍需要处理后才能继续安装。</p></div>
        </div>
        <div id="envCoreList" class="core"></div>
        <div class="wrap">
          <div style="font-weight:700;">扩展支持</div>
          <div class="muted" style="margin-top:4px;font-size:12px;">绿色表示已加载，红色表示缺失。</div>
          <div id="envExtensionList" class="chips"></div>
        </div>
        <div class="summary"><div class="summary-row"><div id="envSummaryText" class="muted">正在载入环境检查结果。</div><span id="envSummaryBadge" class="pill warn">待确认</span></div></div>
        <div class="actions">
          <button id="step2PrevBtn" class="btn light" type="button">上一步</button>
          <button id="recheckEnvBtn" class="btn light" type="button">重新检查环境</button>
          <button id="step2NextBtn" class="btn primary" type="button" disabled>下一步：数据库配置</button>
        </div>
      </section>

      <section class="panel" data-step-panel="3">
        <div class="tag">Step 3</div>
        <h2 class="pt">填写数据库信息并执行安装</h2>
        <p class="lead">这里会先测试数据库连接，再确认目标库是否为空。当前版本固定使用 <code>light_</code> 作为表前缀，安装时会同步更新根目录下的 <code>.env</code> 配置。</p>
        <form id="dbForm" class="form">
          <div class="field"><label for="hostname">数据库主机</label><input id="hostname" name="hostname" type="text" autocomplete="off" /><div class="hint"><strong>必填</strong>，例如 `127.0.0.1` 或数据库所在地址。</div><div class="field-error" id="hostnameError"></div></div>
          <div class="field"><label for="hostport">端口</label><input id="hostport" name="hostport" type="text" autocomplete="off" /><div class="hint"><strong>必填</strong>，常用默认值为 `3306`。</div><div class="field-error" id="hostportError"></div></div>
          <div class="field"><label for="database">数据库名</label><input id="database" name="database" type="text" autocomplete="off" /><div class="hint"><strong>必填</strong>，安装器会先检查这个库是否为空。</div><div class="field-error" id="databaseError"></div></div>
          <div class="field"><label for="charset">字符集</label><input id="charset" name="charset" type="text" autocomplete="off" /><div class="hint"><strong>必填</strong>，默认使用 `utf8mb4`。</div><div class="field-error" id="charsetError"></div></div>
          <div class="field"><label for="username">用户名</label><input id="username" name="username" type="text" autocomplete="off" /><div class="hint"><strong>必填</strong>，请填写具备建库或建表权限的账号。</div><div class="field-error" id="usernameError"></div></div>
          <div class="field"><label for="password">密码</label><input id="password" name="password" type="password" autocomplete="new-password" /><div class="hint"><strong>必填</strong>，请填写数据库账号对应的登录密码。</div><div class="field-error" id="passwordError"></div></div>
        </form>
        <div id="dbResult" class="summary" hidden></div>
        <div class="actions">
          <button id="step3PrevBtn" class="btn light" type="button">上一步</button>
          <button id="checkDbBtn" class="btn light" type="button">测试数据库连接</button>
          <button id="runInstallBtn" class="btn primary" type="button" disabled>开始安装</button>
        </div>
        <p class="note">执行安装后，系统会写入数据库配置、导入初始化 SQL，并生成安装锁。安装完成后页面会自动切换到成功页，但不会自动跳转走。</p>
        <div class="board"><h3 id="statusTitle">等待开始安装</h3><p id="statusDetail">请先完成数据库连接测试，确认目标库可安装后，再点击“开始安装”。</p><div id="statusLog" class="log"></div></div>
      </section>

      <section class="panel" data-step-panel="4">
        <div class="tag">Step 4</div>
        <h2 class="pt">安装已经完成</h2>
        <p class="lead">系统初始化成功，当前页面会停留在这里。你可以查看默认管理员账号，并手工跳转到后台登录页。</p>
        <div class="banner"><div><strong>安装成功</strong><p>安装能力已经被锁定，后台会继续尝试自动清理 <code>public/install</code> 目录。</p></div><span id="cleanupBadge" class="pill warn">等待清理</span></div>
        <div class="credentials">
          <div class="cred"><small>管理员账号</small><strong id="adminUsername">admin</strong></div>
          <div class="cred"><small>管理员密码</small><strong id="adminPassword">123456</strong></div>
        </div>
        <div class="success-box"><strong>安装目录清理状态</strong><p id="cleanupNote" class="muted" style="margin-top:8px;">安装成功后，系统会在后台尝试自动删除 <code>public/install</code> 目录。当前页面会继续停留，不会自动跳转。</p></div>
        <div class="links"><a id="loginButton" class="link" href="/#/auth/login" target="_blank" rel="noreferrer noopener">进入后台登录</a></div>
      </section>
    </main>
  </div>

  <script>window.INSTALL_BOOTSTRAP = <?= $bootstrapJson ?: '{}' ?>;</script>
  <script>
    ;(() => {
      const b = window.INSTALL_BOOTSTRAP || {}
      const s = { step: 1, env: b.environment || { passed: false, items: [] }, db: null, token: '', installing: false, cleaned: false }
      const t = { 1: '系统介绍', 2: '环境检查', 3: '数据库配置', 4: '安装完成' }
      const $ = (id) => document.getElementById(id)
      const shell = document.querySelector('.shell')
      const stepItems = [...document.querySelectorAll('[data-step-item]')]
      const panels = [...document.querySelectorAll('[data-step-panel]')]
      const core = $('envCoreList')
      const exts = $('envExtensionList')
      const statusLog = $('statusLog')
      const fieldKeys = ['hostname', 'hostport', 'database', 'charset', 'username', 'password']
      const fields = fieldKeys.reduce((m, k) => (m[k] = $(k), m), {})
      const errors = fieldKeys.reduce((m, k) => (m[k] = $(`${k}Error`), m), {})
      const fieldLabels = {
        hostname: '数据库主机',
        hostport: '端口',
        database: '数据库名',
        charset: '字符集',
        username: '用户名',
        password: '密码'
      }
      $('loginButton').href = b.links?.login || '/#/auth/login'

      const esc = (v) => String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;')
      const badge = (el, cls, text) => { el.className = `pill ${cls}`; el.textContent = text }
      const setStatus = (title, detail) => { $('statusTitle').textContent = title; $('statusDetail').textContent = detail }

      function go(step) {
        s.step = Number(step)
        shell.classList.toggle('focus-step', s.step === 2 || s.step === 3)
        $('metricStep').textContent = t[s.step] || '安装向导'
        stepItems.forEach((item) => {
          const n = Number(item.dataset.stepItem)
          item.classList.toggle('active', n === s.step)
          item.classList.toggle('done', n < s.step)
        })
        panels.forEach((panel) => panel.classList.toggle('active', Number(panel.dataset.stepPanel) === s.step))
      }

      function log(title, detail) {
        const time = new Date().toLocaleTimeString('zh-CN', { hour12: false })
        const item = document.createElement('div')
        item.className = 'log-item'
        item.innerHTML = `<div class="log-dot"></div><div><strong>${esc(title)}</strong><div class="meta-t">${esc(time)} · ${esc(detail)}</div></div>`
        statusLog.prepend(item)
      }

      function renderEnv(result) {
        const items = Array.isArray(result.items) ? result.items : []
        const ext = items.filter((item) => String(item.key || '').startsWith('ext_'))
        const base = items.filter((item) => !String(item.key || '').startsWith('ext_'))
        const ok = items.filter((item) => item.ok).length
        const fail = items.length - ok
        $('envTotalCount').textContent = String(items.length)
        $('envOkCount').textContent = String(ok)
        $('envFailCount').textContent = String(fail)
        core.innerHTML = base.map((item) => `<div class="core-item"><div class="core-head"><div class="core-name">${esc(item.label)}</div><span class="pill ${item.ok ? 'ok' : 'bad'}">${item.ok ? '通过' : '缺失'}</span></div><div class="core-text">${esc(item.message)}</div></div>`).join('')
        exts.innerHTML = ext.map((item) => `<div class="chip ${item.ok ? 'ok' : 'bad'}"><span class="dot"></span><span>${esc(String(item.label || '').replace(/^扩展\\s+/, ''))}</span></div>`).join('')
        if (result.passed) {
          $('metricEnv').textContent = '可安装'
          $('envSummaryText').textContent = '当前服务器环境满足安装要求，可以继续配置数据库。'
          badge($('envSummaryBadge'), 'ok', '已通过')
        } else {
          $('metricEnv').textContent = '需修复'
          $('envSummaryText').textContent = '当前还有环境项未通过，请先修复缺失项后再继续。'
          badge($('envSummaryBadge'), 'bad', '未通过')
        }
      }

      function fillForm() {
        const d = b.database || {}
        fields.hostname.value = d.hostname || '127.0.0.1'
        fields.hostport.value = d.hostport || '3306'
        fields.database.value = d.database || ''
        const charset = String(d.charset || '').trim().toLowerCase()
        fields.charset.value = !charset || charset === 'utf8' ? 'utf8mb4' : d.charset
        fields.username.value = d.username === 'root' ? '' : (d.username || '')
        fields.password.value = d.password || ''
      }

      function clearFieldError(key) {
        fields[key].classList.remove('is-invalid')
        errors[key].textContent = ''
      }

      function validateField(key) {
        const value = fields[key].value.trim()
        if (value !== '') {
          clearFieldError(key)
          return true
        }
        fields[key].classList.add('is-invalid')
        errors[key].textContent = `${fieldLabels[key]}为必填项`
        return false
      }

      function validateForm() {
        let firstInvalid = null
        let valid = true
        fieldKeys.forEach((key) => {
          const ok = validateField(key)
          if (!ok && !firstInvalid) firstInvalid = fields[key]
          if (!ok) valid = false
        })
        if (firstInvalid) firstInvalid.focus()
        return valid
      }

      function payload() {
        return {
          hostname: fields.hostname.value.trim(),
          hostport: fields.hostport.value.trim(),
          database: fields.database.value.trim(),
          charset: fields.charset.value.trim(),
          username: fields.username.value.trim(),
          password: fields.password.value
        }
      }

      function updateDb(type, msg) {
        const label = type === 'success' ? '通过' : type === 'warning' ? '注意' : '失败'
        const cls = type === 'success' ? 'ok' : type === 'warning' ? 'warn' : 'bad'
        $('dbResult').hidden = false
        $('dbResult').innerHTML = `<div class="summary-row"><div class="muted">${esc(msg)}</div><span class="pill ${cls}">${label}</span></div>`
      }

      function refresh() {
        const envOk = Boolean(s.env?.passed)
        const dbOk = Boolean(s.db?.can_install)
        $('step2NextBtn').disabled = !envOk
        $('runInstallBtn').disabled = !(envOk && dbOk && !s.installing)
        $('step3PrevBtn').disabled = s.installing
      }

      function resetDbResult() {
        s.db = null
        $('dbResult').hidden = true
        $('dbResult').innerHTML = ''
        refresh()
      }

      async function req(url, options = {}) {
        const res = await fetch(url, options)
        const data = await res.json().catch(() => null)
        if (!data || data.code !== 1) throw new Error(data?.msg || '请求失败，请稍后重试')
        return data.data || {}
      }

      async function checkEnv() {
        $('recheckEnvBtn').disabled = true
        setStatus('正在重新检查环境', '安装器会重新确认 PHP 版本、扩展和目录权限。')
        try {
          const result = await req('api.php?action=check-env')
          s.env = result
          renderEnv(result)
          log('环境检查完成', result.passed ? '所有环境项都已通过' : '仍有缺失项需要修复')
          setStatus(result.passed ? '环境已就绪' : '环境检查未通过', result.passed ? '现在可以继续填写数据库配置。' : '请先处理缺失项，再继续安装。')
        } catch (error) {
          $('envSummaryText').textContent = error.message
          badge($('envSummaryBadge'), 'bad', '失败')
          log('环境检查失败', error.message)
          setStatus('环境检查失败', error.message)
        } finally {
          $('recheckEnvBtn').disabled = false
          refresh()
        }
      }

      async function checkDb() {
        if (!validateForm()) {
          resetDbResult()
          updateDb('danger', '请先填写完整的数据库信息。')
          setStatus('数据库信息未填写完整', '请先补全所有必填项，再测试数据库连接。')
          return
        }
        $('checkDbBtn').disabled = true
        setStatus('正在检查数据库', '正在尝试连接数据库，并确认目标库是否为空。')
        try {
          const result = await req('api.php?action=check-db', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload()) })
          s.db = result
          updateDb(result.can_install ? 'success' : 'warning', result.message || '数据库检查完成')
          log('数据库检查完成', result.message || '数据库状态已确认')
          setStatus(result.can_install ? '数据库已就绪' : '数据库不可直接安装', result.message || '请确认数据库配置后再继续。')
        } catch (error) {
          s.db = null
          updateDb('danger', error.message)
          log('数据库检查失败', error.message)
          setStatus('数据库检查失败', error.message)
        } finally {
          $('checkDbBtn').disabled = false
          refresh()
        }
      }

      async function cleanup() {
        if (!s.token || s.cleaned) return
        s.cleaned = true
        badge($('cleanupBadge'), 'warn', '清理中')
        $('cleanupNote').innerHTML = '安装目录正在自动清理，当前页面会继续停留在这里，你可以稍后手工跳转。'
        try {
          await req('/install-cleanup.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ token: s.token }) })
          badge($('cleanupBadge'), 'ok', '已清理')
          $('cleanupNote').innerHTML = 'public/install 目录已自动删除。当前页面仍可继续查看，刷新后如果出现 404 属于正常现象。'
          log('安装目录已清理', '安装入口目录已自动删除')
        } catch (error) {
          badge($('cleanupBadge'), 'bad', '清理失败')
          $('cleanupNote').innerHTML = '系统已经安装成功，但 public/install 未能自动删除。请稍后手工删除该目录。'
          log('安装目录清理失败', error.message)
        }
      }

      async function runInstall() {
        if (!validateForm()) {
          resetDbResult()
          updateDb('danger', '请先填写完整的数据库信息。')
          setStatus('数据库信息未填写完整', '请先补全所有必填项，再执行安装。')
          return
        }
        s.installing = true
        refresh()
        $('checkDbBtn').disabled = true
        $('recheckEnvBtn').disabled = true
        setStatus('正在执行安装', '正在写入配置、导入 SQL 并锁定安装入口，请不要关闭页面。')
        log('开始安装', '安装器正在执行数据库初始化')
        try {
          const result = await req('api.php?action=run', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload()) })
          s.token = result.cleanup_token || ''
          $('adminUsername').textContent = result.admin?.username || 'admin'
          $('adminPassword').textContent = result.admin?.password || '123456'
          $('loginButton').href = result.links?.login || b.links?.login || '/#/auth/login'
          log('安装完成', '系统初始化已经完成，安装入口已被锁定')
          setStatus('安装已完成', '当前页面会停留在成功页，后台会尝试自动删除 public/install 目录。')
          $('cleanupNote').innerHTML = '系统已完成初始化。为了保留当前结果页，安装目录会在后台异步删除，页面不会自动跳转。'
          go(4)
          setTimeout(cleanup, 1400)
        } catch (error) {
          log('安装失败', error.message)
          setStatus('安装失败', error.message)
        } finally {
          s.installing = false
          refresh()
          $('checkDbBtn').disabled = false
          $('recheckEnvBtn').disabled = false
        }
      }

      $('step1NextBtn').addEventListener('click', () => go(2))
      $('step2PrevBtn').addEventListener('click', () => go(1))
      $('step2NextBtn').addEventListener('click', () => s.env?.passed && go(3))
      $('step3PrevBtn').addEventListener('click', () => !s.installing && go(2))
      $('recheckEnvBtn').addEventListener('click', checkEnv)
      $('checkDbBtn').addEventListener('click', checkDb)
      $('runInstallBtn').addEventListener('click', runInstall)
      fieldKeys.forEach((key) => {
        fields[key].addEventListener('blur', () => validateField(key))
        fields[key].addEventListener('input', () => {
          if (fields[key].value.trim() !== '') {
            clearFieldError(key)
          }
          resetDbResult()
        })
      })

      fillForm()
      renderEnv(s.env)
      setStatus(s.env?.passed ? '环境已就绪' : '环境检查未通过', s.env?.passed ? '先测试数据库连接，然后开始安装。' : '请先处理环境缺失项。')
      log('安装器已加载', s.env?.passed ? '环境检查已通过' : '环境检查存在未通过项')
      refresh()
      go(1)
    })()
  </script>
</body>
</html>
