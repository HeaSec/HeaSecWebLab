<?php
/**
 * HeaSec天积安全团队 - 短信模拟器自动化测试接口文档
 * SMS Simulator Automation API Documentation
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 本页面供自动化测试工具（curl / playwright 等）读取
 *   - 集中呈现"注册手机号、读取短信、提取验证码"所需的全部接口信息
 *   - 所有接口信息均以纯文本表格 + 代码块呈现，便于机器解析
 *   - 页面末尾提供结构化 JSON 规格（script#api-spec），供 AI 精确提取
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('Content-Type: text/html; charset=utf-8');
header('X-HeavenlySecret: HeaSec SMS Simulator API Doc v1.0.0');

// 公共组件基础路径（与 manage.php 保持一致）
$commonBasePath = '../../../common/';

// 根据当前请求自动推导 API 基础 URL（自适应部署位置：localhost / 任意域名 / 任意子目录）
$scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // 当前脚本所在目录
$apiBaseUrl = $scheme . '://' . $host . $scriptDir . '/api/';

// ============================================================
// 机器可读规格（JSON）：供 AI / 自动化工具直接提取
// ============================================================
$apiSpec = array(
    'component' => 'sms-simulator',
    'version' => '1.0.0',
    'purpose' => '自动化注册手机号、读取短信、提取验证码',
    'base_url' => $apiBaseUrl,
    'auth' => 'none（无需登录、无 CSRF、无 CORS 限制）',
    'http_status' => '统一返回 200，业务成败看响应体 success 字段',
    'response_shape' => array(
        'success' => 'boolean',
        'message' => 'string（失败时以 [HeaSec] 开头）',
        'data' => 'mixed（接口相关）',
        'timestamp' => 'integer（服务端 time()）',
    ),
    'endpoints' => array(
        array(
            'name' => '查看已注册手机号',
            'method' => 'GET',
            'path' => 'phone-list.php',
            'params' => '无',
            'returns' => 'data 为数组，每项含 id / phone_number / is_default / status / sms_count',
            'tip' => '后续接口需使用返回的 id',
        ),
        array(
            'name' => '注册手机号',
            'method' => 'POST',
            'path' => 'phone-add.php',
            'params' => array(
                'phone_number' => '必填，正则 ^1[3-9]\d{9}$，且不能以 110 开头',
            ),
            'returns' => 'data.id, data.phone_number',
            'tip' => '号已存在时返回“该手机号已存在”，可忽略后改用 phone-list 取 id',
        ),
        array(
            'name' => '读取短信',
            'method' => 'GET',
            'path' => 'sms-list.php',
            'params' => array(
                'phone_id' => '必填，int，手机号记录 id（注意：不是手机号字符串）',
            ),
            'returns' => 'data.sms_list 数组，最新短信在最前（sms_list[0] 为最新）',
            'tip' => '需先用 phone-list 取得目标手机号的 phone_id',
        ),
    ),
    'verification_code_regex' => array(
        '(?:验证码|校验码|激活码|动态码)(?:[：:]|是[：:]?|为[：:]?|\s+)\s*([0-9a-zA-Z]{4,32})',
        '【([0-9a-zA-Z]{4,32})】',
        '\[([0-9a-zA-Z]{4,32})\]',
    ),
    'php_regex_note' => 'PHP 用 preg_match 时正则末尾必须加 u 修饰符（如 /pattern/u），否则中文匹配失败；Python / JS 无需',
    'example_message' => '您的验证码是：1234，5分钟内有效。',
    'example_extracted_code' => '1234',
    'workflow' => array(
        '1. POST phone-add.php 注册手机号（若已存在，改用 GET phone-list.php 取已存在号的 id）',
        '2. 触发被测靶场向该手机号发送短信（由被测靶场自行调用，本组件不代发）',
        '3. GET sms-list.php?phone_id={id} 读取 data.sms_list[0].message_content',
        '4. 使用 verification_code_regex 从短信内容中提取验证码',
    ),
);
$apiSpecJson = json_encode($apiSpec, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="天积安全 (HeavenlySecret)">
    <meta name="keywords" content="短信模拟器,自动化测试,接口文档,验证码,HeaSec">
    <meta name="description" content="HeaSec短信模拟器自动化测试接口文档 - 供AI/自动化工具读取">
    <title>自动化测试接口文档 - 短信模拟器 - 天积安全</title>

    <!-- 引入Font Awesome图标库（与 manage.php 一致） -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/font-awesome.min.css">
    <!-- 引入tech-blue组件风格 -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/tech-blue/css/heasec-tech-blue.css">
    <!-- 引入短信模拟器组件样式（复用容器与背景） -->
    <link rel="stylesheet" href="css/heasec-sms-simulator.css?v=1.2.0">

    <!-- 文档页专属内联样式（不新增独立CSS文件，保持简单） -->
    <style>
        /* 文档卡片区 */
        .doc-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid #eef2f7;
        }
        .doc-card-header {
            background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
            color: #fff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .doc-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .doc-card-header i {
            font-size: 18px;
        }
        .doc-card-body {
            padding: 18px 20px;
            color: #333;
            line-height: 1.7;
            font-size: 14px;
        }
        /* 请求行：方法标签 + URL */
        .req-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .method-tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
        }
        .method-get { background: #28a745; }
        .method-post { background: #007BFF; }
        .url-text {
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
            color: #d63384;
            word-break: break-all;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
        }
        /* 参数表 */
        .param-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 13px;
        }
        .param-table th,
        .param-table td {
            border: 1px solid #e0e0e0;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        .param-table th {
            background: #f0f4f8;
            font-weight: 600;
            color: #495057;
        }
        .param-table code {
            background: #f5f5f5;
            padding: 1px 5px;
            border-radius: 3px;
            color: #c7254e;
            font-size: 12px;
        }
        /* 代码块 */
        .code-block {
            background: #1e1e2e;
            color: #e0e0e0;
            padding: 14px 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: Consolas, Monaco, monospace;
            font-size: 12.5px;
            line-height: 1.6;
            margin: 10px 0;
            white-space: pre;
        }
        .code-block .cmt { color: #6a9955; }
        .code-block .key { color: #569cd6; }
        .code-block .str { color: #ce9178; }
        .code-label {
            display: inline-block;
            font-size: 12px;
            color: #888;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .code-label i { margin-right: 4px; }
        /* 工作流步骤 */
        .workflow-step {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        .workflow-step:last-child { border-bottom: none; }
        .step-num {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #007BFF;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        /* 注意事项 */
        .note-item {
            background: #fff8e1;
            border-left: 4px solid #FFC107;
            padding: 10px 14px;
            margin-bottom: 8px;
            border-radius: 0 6px 6px 0;
            font-size: 13px;
        }
        .note-item.danger {
            background: #fdecea;
            border-left-color: #DC3545;
        }
        .note-item strong { color: #333; }
        /* 顶部信息条 */
        .doc-intro {
            background: rgba(0, 123, 255, 0.06);
            border: 1px solid rgba(0, 123, 255, 0.2);
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 13.5px;
            color: #495057;
        }
        .doc-intro code {
            background: #e7f1ff;
            padding: 2px 6px;
            border-radius: 3px;
            color: #007BFF;
            font-size: 12.5px;
        }
        .back-link {
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            opacity: 0.9;
            margin-left: auto;
        }
        .back-link:hover { opacity: 1; text-decoration: underline; }
        /* JSON 规格块 */
        .spec-json {
            max-height: 500px;
            overflow: auto;
        }
    </style>
</head>

<body class="heasec-sms-manager">
    <div class="heasec-sms-container">

        <!-- 页面头部 -->
        <div class="heasec-sms-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fa fa-book"></i>
                    <h2>自动化测试接口文档</h2>
                    <span class="version-badge">v1.0.0</span>
                </div>
            </div>
        </div>

        <!-- 简介 -->
        <div class="doc-intro">
            <i class="fa fa-info-circle" style="color:#007BFF;margin-right:6px;"></i>
            本页面供自动化测试工具（<code>curl</code> / <code>playwright</code> 等）读取，提供
            <strong>注册手机号</strong>、<strong>读取短信</strong>、<strong>提取验证码</strong>
            所需的全部接口信息。所有接口均<strong>无需鉴权</strong>，统一返回
            <code>{ success, message, data, timestamp }</code> 结构。页面末尾提供
            <strong>结构化 JSON 规格</strong>（<code>&lt;script type="application/json" id="api-spec"&gt;</code>），便于 AI 直接提取。
        </div>

        <!-- ⚠ 核心规则（必读） -->
        <div class="doc-card">
            <div class="doc-card-header" style="background:linear-gradient(135deg,#DC3545 0%,#a71d2a 100%);">
                <i class="fa fa-exclamation-triangle"></i>
                <h3>核心规则：先注册 → 再触发 → 才能看到短信</h3>
            </div>
            <div class="doc-card-body">
                <div class="note-item danger" style="margin-bottom:12px;">
                    <strong>必须按此顺序操作</strong>：
                    <ol style="margin:8px 0 0 20px;padding:0;line-height:1.9;">
                        <li>先在本模拟器<strong>注册手机号</strong>（<code>POST phone-add.php</code>，或使用已注册的默认号 <code>13866668888</code>）</li>
                        <li>再到<strong>被测靶场</strong>触发需要短信的功能（注册 / 登录 / 改密等），由靶场向该号下发短信</li>
                        <li>最后用 <code>GET sms-list.php</code> 才能读到短信</li>
                    </ol>
                </div>
                <div class="note-item danger">
                    <strong>为什么靶场提示“发送成功”却看不到短信？</strong>靶场返回的“发送成功”只代表短信请求被网关接受并记入<strong>发送日志</strong>；短信<strong>只有在手机号已注册且 <code>status=1</code> 时</strong>才会真正写入收件箱（<code>heasec_sms_message</code>）。未注册的号既不会收到短信，也不会在 <code>sms-list.php</code> 中出现。
                </div>
            </div>
        </div>

        <!-- 全局约定 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-globe"></i>
                <h3>全局约定</h3>
            </div>
            <div class="doc-card-body">
                <table class="param-table">
                    <tr><th style="width:160px;">项目</th><th>说明</th></tr>
                    <tr><td>接口基础 URL</td><td><code><?php echo htmlspecialchars($apiBaseUrl, ENT_QUOTES); ?></code><br><span style="color:#888;font-size:12px;">↑ 由当前部署位置自动推导，下方所有示例 URL 均自适应，无需手动改</span></td></tr>
                    <tr><td>鉴权</td><td>无（无需登录、无 CSRF Token、无 CORS 限制，任意 HTTP 客户端可直接调用）</td></tr>
                    <tr><td>HTTP 状态码</td><td>统一返回 <strong>200</strong>，业务成败由响应体 <code>success</code> 字段区分</td></tr>
                    <tr><td>响应结构</td><td><code>{ "success": bool, "message": string, "data": mixed, "timestamp": int }</code></td></tr>
                    <tr><td>错误消息</td><td>失败时 <code>message</code> 以 <code>[HeaSec]</code> 开头</td></tr>
                    <tr><td>响应头</td><td><code>HeavenlySecret: HeaSec-API-v1.0.0</code></td></tr>
                    <tr><td>请求体格式</td><td>POST 接口推荐 <code>Content-Type: application/json</code>，亦兼容表单 <code>x-www-form-urlencoded</code></td></tr>
                </table>
            </div>
        </div>

        <!-- 接口 1：查看已注册手机号 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-list"></i>
                <h3>接口 1：查看已注册手机号</h3>
            </div>
            <div class="doc-card-body">
                <div class="req-line">
                    <span class="method-tag method-get">GET</span>
                    <span class="url-text"><?php echo htmlspecialchars($apiBaseUrl . 'phone-list.php', ENT_QUOTES); ?></span>
                </div>
                <div class="code-label"><i class="fa fa-terminal"></i>参数</div>
                <div style="color:#888;font-size:13px;margin-bottom:8px;">无参数</div>

                <div class="code-label"><i class="fa fa-code"></i>curl 示例</div>
                <div class="code-block">curl -s "<?php echo htmlspecialchars($apiBaseUrl, ENT_QUOTES); ?>phone-list.php"</div>

                <div class="code-label"><i class="fa fa-reply"></i>响应示例</div>
                <div class="code-block">{
  "success": true,
  "message": "获取手机号列表成功",
  "data": [
    {
      "id": "1",
      "phone_number": "13866668888",
      "is_default": "1",
      "status": "1",
      "created_at": "2026-01-06 12:00:00",
      "updated_at": null,
      "sms_count": "3"
    }
  ],
  "timestamp": 1736227200
}</div>
                <div class="note-item">
                    <strong>关键字段</strong>：<code>id</code>（后续接口使用此 id，<strong>非手机号字符串</strong>）、<code>phone_number</code>、<code>is_default</code>（1=默认）、<code>sms_count</code>（该号短信总数）。
                </div>
            </div>
        </div>

        <!-- 接口 2：注册手机号 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-user-plus"></i>
                <h3>接口 2：注册手机号</h3>
            </div>
            <div class="doc-card-body">
                <div class="req-line">
                    <span class="method-tag method-post">POST</span>
                    <span class="url-text"><?php echo htmlspecialchars($apiBaseUrl . 'phone-add.php', ENT_QUOTES); ?></span>
                </div>
                <table class="param-table">
                    <tr><th>参数</th><th>必填</th><th>说明</th></tr>
                    <tr>
                        <td><code>phone_number</code></td>
                        <td>是</td>
                        <td>11 位手机号，须匹配 <code>^1[3-9]\d{9}$</code>，<strong>不能以 <code>110</code> 开头</strong>（保留号段）；不可与已注册号重复</td>
                    </tr>
                </table>

                <div class="code-label"><i class="fa fa-code"></i>curl 示例</div>
                <div class="code-block">curl -s -X POST "<?php echo htmlspecialchars($apiBaseUrl, ENT_QUOTES); ?>phone-add.php" \
  -H "Content-Type: application/json" \
  -d '{"phone_number":"13900139000"}'</div>

                <div class="code-label"><i class="fa fa-check-circle"></i>成功响应</div>
                <div class="code-block">{
  "success": true,
  "message": "添加手机号成功",
  "data": { "id": "5", "phone_number": "13900139000" },
  "timestamp": 1736227200
}</div>

                <div class="code-label"><i class="fa fa-exclamation-circle"></i>失败响应（号已存在）</div>
                <div class="code-block">{
  "success": false,
  "message": "[HeaSec] 该手机号已存在",
  "data": null,
  "timestamp": 1736227200
}</div>
            </div>
        </div>

        <!-- 接口 3：读取短信 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-envelope-open"></i>
                <h3>接口 3：读取指定手机号的短信</h3>
            </div>
            <div class="doc-card-body">
                <div class="req-line">
                    <span class="method-tag method-get">GET</span>
                    <span class="url-text"><?php echo htmlspecialchars($apiBaseUrl . 'sms-list.php?phone_id={id}', ENT_QUOTES); ?></span>
                </div>
                <table class="param-table">
                    <tr><th>参数</th><th>必填</th><th>说明</th></tr>
                    <tr>
                        <td><code>phone_id</code></td>
                        <td>是</td>
                        <td>手机号记录 <strong>id</strong>（int），来自 <code>phone-list</code> 返回的 <code>id</code> 字段。<strong>注意：不是手机号字符串</strong></td>
                    </tr>
                </table>

                <div class="code-label"><i class="fa fa-code"></i>curl 示例</div>
                <div class="code-block">curl -s "<?php echo htmlspecialchars($apiBaseUrl, ENT_QUOTES); ?>sms-list.php?phone_id=1"</div>

                <div class="code-label"><i class="fa fa-reply"></i>响应示例</div>
                <div class="code-block">{
  "success": true,
  "message": "获取短信列表成功",
  "data": {
    "phone_id": 1,
    "phone_number": "13866668888",
    "sms_list": [
      {
        "id": "10",
        "simulator_id": "1",
        "phone_number": "13866668888",
        "sender": "shop",
        "message_content": "您的验证码是：1234，5分钟内有效。",
        "is_read": "0",
        "created_at": "2026-07-03 10:00:00",
        "read_at": null
      }
    ],
    "unread_count": 1,
    "total_count": 1
  },
  "timestamp": 1736227200
}</div>
                <div class="note-item">
                    <strong>取最新短信</strong>：<code>data.sms_list[0].message_content</code>（列表按 <code>created_at DESC</code> 排序，最新在最前，最多 100 条）。<strong>没有</strong>专门的“取最新验证码”接口，需从短信内容中自行正则提取。
                </div>
            </div>
        </div>

        <!-- 端到端工作流 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-random"></i>
                <h3>端到端工作流（注册 → 读短信 → 取验证码）</h3>
            </div>
            <div class="doc-card-body">
                <div class="workflow-step">
                    <div class="step-num">1</div>
                    <div>
                        <strong>注册 / 定位手机号</strong><br>
                        <code>POST phone-add.php</code> 注册一个新号（如 <code>13900139000</code>），或调 <code>GET phone-list.php</code> 找已存在号的 <code>id</code>。
                    </div>
                </div>
                <div class="workflow-step">
                    <div class="step-num">2</div>
                    <div>
                        <strong>触发靶场发短信</strong> <span style="color:#DC3545;font-weight:600;">（前提：步骤 1 已完成，手机号必须已注册）</span><br>
                        到<strong>被测靶场</strong>触发需要短信的功能，由靶场内部调用 <code>HeaSec_SmsSender::sendQuick()</code> 向该号下发验证码。<strong>本组件不代靶场发短信</strong>。若手机号未先注册，靶场即便提示“发送成功”，步骤 3 也读不到短信。
                    </div>
                </div>
                <div class="workflow-step">
                    <div class="step-num">3</div>
                    <div>
                        <strong>读取最新短信</strong><br>
                        <code>GET sms-list.php?phone_id={id}</code>，取 <code>data.sms_list[0].message_content</code>。
                    </div>
                </div>
                <div class="workflow-step">
                    <div class="step-num">4</div>
                    <div>
                        <strong>正则提取验证码</strong><br>
                        对短信内容套用下方“验证码提取规则”，得到验证码字符串。
                    </div>
                </div>
            </div>
        </div>

        <!-- 验证码提取 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-key"></i>
                <h3>验证码提取规则</h3>
            </div>
            <div class="doc-card-body">
                <p style="margin-top:0;">后端<strong>不单独返回验证码字段</strong>，需从 <code>message_content</code> 自行提取。以下正则按优先级匹配，命中即取捕获组 1（长度 4–32，数字与字母）：</p>
                <table class="param-table">
                    <tr><th style="width:140px;">场景</th><th>正则</th><th>示例命中</th></tr>
                    <tr>
                        <td>中文前缀</td>
                        <td><code>(?:验证码|校验码|激活码|动态码)(?:[：:]|是[：:]?|为[：:]?|\s+)\s*([0-9a-zA-Z]{4,32})</code></td>
                        <td><code>您的验证码是：1234…</code> → <strong>1234</strong></td>
                    </tr>
                    <tr>
                        <td>中文括号</td>
                        <td><code>【([0-9a-zA-Z]{4,32})】</code></td>
                        <td><code>验证码【654321】</code> → <strong>654321</strong></td>
                    </tr>
                    <tr>
                        <td>英文括号</td>
                        <td><code>\[([0-9a-zA-Z]{4,32})\]</code></td>
                        <td><code>code [AB12CD]</code> → <strong>AB12CD</strong></td>
                    </tr>
                    <tr>
                        <td>英文前缀</td>
                        <td><code>(?:verification code|vcode|code)(?:[：:]|\s+is|\s+)\s*([0-9a-zA-Z]{4,32})</code></td>
                        <td><code>verification code: 9988</code> → <strong>9988</strong></td>
                    </tr>
                </table>
                <div class="note-item">
                    <strong>shop 靶场实测示例</strong>：短信 <code>您的验证码是：1234，5分钟内有效。</code> → 命中“中文前缀”规则 → 提取结果 <code>1234</code>。
                </div>

                <div class="note-item danger">
                    <strong>⚠ PHP 实现必读</strong>：PCRE 默认按字节匹配，处理含中文的正则时<strong>必须加 <code>u</code> 修饰符</strong>（PCRE_UTF8），否则中文规则会匹配失败。Python（<code>re</code> 默认 Unicode）/ JS 无此问题。
                </div>
                <div class="code-label"><i class="fa fa-code"></i>PHP 提取示例</div>
                <div class="code-block">// 注意正则末尾的 u 修饰符 —— 处理中文必需
$msg = "您的验证码是：1234，5分钟内有效。";
preg_match('/(?:验证码|校验码|激活码|动态码)(?:[：:]|是[：:]?|为[：:]?|\s+)\s*([0-9a-zA-Z]{4,32})/u', $msg, $m);
$code = isset($m[1]) ? $m[1] : null;   // 结果："1234"</div>
            </div>
        </div>

        <!-- 注意事项 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-exclamation-triangle"></i>
                <h3>注意事项（易踩坑）</h3>
            </div>
            <div class="doc-card-body">
                <div class="note-item danger">
                    <strong>① 先注册才能收到短信</strong>：未注册的手机号，靶场发送后<strong>只进发送日志、不进收件箱</strong>，<code>sms-list.php</code> 查不到（详见顶部“核心规则”）。
                </div>
                <div class="note-item danger">
                    <strong>② phone_id ≠ 手机号字符串</strong>：<code>sms-list.php</code> 只接受数字 <code>phone_id</code>，必须先用 <code>phone-list.php</code> 查出 <code>id</code>。
                </div>
                <div class="note-item danger">
                    <strong>③ phone-add 校验严格</strong>：<code>phone-add.php</code> 要求 <code>^1[3-9]\d{9}$</code> 且禁 <code>110</code> 开头；而靶场侧的 <code>send-sms.php</code> 校验更宽松（<code>^1\d{10}$</code>）。<strong>自动注册的号必须通过 phone-add 的严格校验</strong>，否则收不到短信。
                </div>
                <div class="note-item">
                    <strong>④ 110 号段保留</strong>：以 <code>110</code> 开头的号码为系统保留（模拟攻击者无法控制的目标号），不可注册。
                </div>
                <div class="note-item">
                    <strong>⑤ 默认号</strong>：库初始化后默认存在一个 <code>13866668888</code>（<code>is_default=1</code>），可直接用于测试。
                </div>
            </div>
        </div>

        <!-- 机器可读 JSON 规格 -->
        <div class="doc-card">
            <div class="doc-card-header">
                <i class="fa fa-code"></i>
                <h3>机器可读规格（JSON）</h3>
                <a href="manage.php" class="back-link"><i class="fa fa-arrow-left"></i> 返回管理后台</a>
            </div>
            <div class="doc-card-body">
                <p style="margin-top:0;">AI / 自动化工具可从页面源码的 <code>&lt;script type="application/json" id="api-spec"&gt;</code> 标签内直接提取以下 JSON（亦可复制下方文本解析）：</p>
                <div class="code-block spec-json"><?php echo htmlspecialchars($apiSpecJson, ENT_QUOTES); ?></div>
            </div>
        </div>

    </div>

    <!-- 机器可读规格（隐藏 JSON 块）：供 curl 抓取 HTML 后正则提取 -->
    <script type="application/json" id="api-spec"><?php echo $apiSpecJson; ?></script>
</body>

</html>
