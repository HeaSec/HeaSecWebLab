<?php
/**
 * HeaSec天积安全团队 - HTTP协议解析靶场
 * 版本: v1.0.0
 * 创建日期: 2025-10-31
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec HTTP协议解析 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');
header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// 引入HTTP请求解析类
require_once __DIR__ . '/http_parser.php';

// 引入内容管理类
require_once __DIR__ . '/includes/HeaSec_ContentManager.php';

// 创建HTTP解析器实例
$httpParser = new HeaSec_HttpParser();

// 启动输出缓冲
ob_start();

// 设置页面变量
$pageTitle = 'HTTP协议解析靶场';
$rangeName = 'HTTP协议解析';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';


// 处理表单提交
$message = '';
$firstName = '';
$lastName = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['first_name']) && isset($_GET['last_name'])) {
    $firstName = trim($_GET['first_name']);
    $lastName = trim($_GET['last_name']);

    if (!empty($firstName) && !empty($lastName)) {
        $message = $lastName . $firstName . '，你好！欢迎访问天积WEB安全靶场';
    } else {
        $message = '请填写用户姓名';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name']) && isset($_POST['last_name'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);

    if (!empty($firstName) && !empty($lastName)) {
        $message = $lastName . $firstName . '，你好！欢迎访问天积WEB安全靶场';
    } else {
        $message = '请填写用户姓名';
    }
}






// 获取请求信息
$requestInfo = $httpParser->getHttpRequestInfo();

// 获取基本响应信息
$responseInfo = $httpParser->getHttpResponseInfo();

// 计算实际响应内容长度并设置Content-Length响应头
ob_start();



// 在页面开始时就设置初始内容长度，确保解析时能获取到值
$GLOBALS['actual_content_length'] = HeaSec_ContentManager::getCurrentContentLength();

// 注册页面结束时的处理函数
register_shutdown_function('HeaSec_ContentManager::handlePageOutput');

// 加载JSON模板文件
function loadJsonTemplate($filename)
{
    $filepath = __DIR__ . '/json/' . $filename;
    if (file_exists($filepath)) {
        $json = file_get_contents($filepath);
        return json_decode($json, true);
    }
    return null;
}

// 加载所有模板
$requestLineTemplates = loadJsonTemplate('request_line_templates.json');
$requestHeaderTemplates = loadJsonTemplate('request_header_templates.json');
$statusLineTemplates = loadJsonTemplate('status_line_templates.json');
$responseHeaderTemplates = loadJsonTemplate('response_header_templates.json');

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入样式文件 - 蓝紫渐变风 -->
<link rel="stylesheet" href="css/style_blue_purple_gradient.css">

<!-- 引入星星系统组件 -->
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
// 引入星星系统CSS和JS资源，包含恭喜弹窗功能
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<!-- 靶场主要内容 -->


<div class="heasec-container">
    <!-- HTTP请求触发区域 -->
    <div class="heasec-section">
        <div class="heasec-card">
            <div class="heasec-card-header">
                <h3>HTTP请求触发</h3>
            </div>
            <div class="heasec-card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <form id="httpRequestForm" class="heasec-form" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">姓：</label>
                            <input type="text" id="firstName" name="first_name" class="form-control name-input"
                                value="<?php echo htmlspecialchars($firstName); ?>" placeholder="请输入您的姓">
                        </div>
                        <div class="form-group">
                            <label for="lastName">名：</label>
                            <input type="text" id="lastName" name="last_name" class="form-control name-input"
                                value="<?php echo htmlspecialchars($lastName); ?>" placeholder="请输入您的名">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="method" value="get" class="btn btn-primary">
                            发送GET请求
                        </button>
                        <button type="submit" name="method" value="post" class="btn btn-secondary">
                            发送POST请求
                        </button>
                    </div>

                    <!-- HTTP状态码测试按钮 -->
                    <div class="status-test-buttons">
                        <h4 style="margin-bottom: 12px; font-size: 16px; color: #4a5568;">HTTP状态码测试</h4>
                        <div class="form-actions">
                            <button type="button" onclick="testRedirect()" class="btn btn-redirect">
                                触发302跳转
                            </button>
                            <button type="button" onclick="test404()" class="btn btn-error">
                                触发404错误
                            </button>
                            <button type="button" onclick="test500()" class="btn btn-server-error">
                                触发500错误
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- WEB应用架构与HTTP协议基础区域 -->
    <div class="heasec-section">
        <div class="heasec-card">
            <div class="heasec-card-header">
                <h3>WEB应用基本架构介绍</h3>
            </div>
            <div class="heasec-card-body">
                
                <!-- 1. WEB应用的基本架构 -->
                <div class="architecture-container">
                    <h4 class="sub-section-title">1. WEB应用的基本架构</h4>
                    <p class="principle-intro">
                        现代WEB应用通常采用<strong>三层架构（或多层架构）</strong>模式。这种架构将应用分为表示层、业务逻辑层和数据访问层，这种模块化的设计使得应用具有高度的灵活性和解耦性。
                    </p>

                    <!-- 三层架构示意图 -->
                    <div class="interaction-diagram">
                        <!-- 客户端 -->
                        <div class="diagram-node client-node">
                            <div class="node-icon">💻</div>
                            <div class="node-name">客户端<br><small>(表示层)</small></div>
                            <div class="node-tech-stack">
                                <span class="tech-tag">HTML</span>
                                <span class="tech-tag">CSS</span>
                                <span class="tech-tag">JS</span>
                                <span class="tech-tag">Vue/React</span>
                            </div>
                            <div class="node-examples">常见: Chrome, App端等</div>
                        </div>

                        <!-- 交互：客户端 <-> 应用服务器 -->
                        <div class="diagram-flow">
                            <div class="flow-step request-step">
                                <div class="step-label">HTTP 请求</div>
                                <div class="step-arrow right-arrow">
                                    <div class="arrow-line"></div>
                                    <div class="arrow-head"></div>
                                </div>
                            </div>
                            <div class="flow-step response-step">
                                <div class="step-arrow left-arrow">
                                    <div class="arrow-head"></div>
                                    <div class="arrow-line"></div>
                                </div>
                                <div class="step-label">HTTP 响应</div>
                            </div>
                        </div>

                        <!-- 应用服务器 -->
                        <div class="diagram-node server-node">
                            <div class="node-icon">⚙️</div>
                            <div class="node-name">应用服务器<br><small>(逻辑层)</small></div>
                            <div class="node-tech-stack">
                                <span class="tech-tag server">PHP</span>
                                <span class="tech-tag server">Java</span>
                                <span class="tech-tag server">Python</span>
                                <span class="tech-tag server">Node.js</span>
                            </div>
                            <div class="node-examples">常见: Apache, Nginx, IIS</div>
                        </div>

                        <!-- 交互：应用服务器 <-> 数据库服务器 -->
                        <div class="diagram-flow">
                            <div class="flow-step request-step">
                                <div class="step-label">SQL 查询/写入</div>
                                <div class="step-arrow right-arrow">
                                    <div class="arrow-line" style="background: linear-gradient(90deg, #ed8936, #9f7aea);"></div>
                                    <div class="arrow-head" style="border-left-color: #9f7aea;"></div>
                                </div>
                            </div>
                            <div class="flow-step response-step">
                                <div class="step-arrow left-arrow">
                                    <div class="arrow-head" style="border-right-color: #ed8936;"></div>
                                    <div class="arrow-line" style="background: linear-gradient(90deg, #4A90E2, #ed8936);"></div>
                                </div>
                                <div class="step-label">返回数据结果</div>
                            </div>
                        </div>

                        <!-- 数据库服务器 -->
                        <div class="diagram-node database-node">
                            <div class="node-icon">🗄️</div>
                            <div class="node-name">数据库服务器<br><small>(数据层)</small></div>
                            <div class="node-tech-stack">
                                <span class="tech-tag db">SQL</span>
                                <span class="tech-tag db">NoSQL</span>
                            </div>
                            <div class="node-examples">常见: MySQL, SQL Server</div>
                        </div>
                    </div>

                    <!-- 架构详情 -->
                    <div class="principle-features">
                        <div class="feature-item">
                            <div class="feature-title">💻 客户端 (表示层)</div>
                            <div class="feature-desc">用户直接交互的界面，负责展示数据和收集用户输入。前端通常通过浏览器渲染页面的 <strong>HTML/CSS</strong>，并通过 <strong>JavaScript</strong> 处理动态交互效果及数据拉取请求。</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-title">⚙️ 应用服务器 (逻辑层)</div>
                            <div class="feature-desc">WEB应用的核心大脑。常见的软件有 <strong>Apache, IIS, Tomcat</strong> 等。后端开发语言（如 <strong>PHP, Java, Python</strong> 等）在此处理业务逻辑、身份校验并协调前后端与数据库通信。</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-title">🗄️ 数据库服务器 (数据层)</div>
                            <div class="feature-desc">持久化存储和管理所有应用数据。常见的关系型数据库有 <strong>MySQL, SQL Server, Oracle</strong> 等；此外还有 <strong>Redis, MongoDB</strong> 等非关系型数据库配合使用。</div>
                        </div>
                    </div>
                </div>

                <div class="section-separator" style="margin: 35px 0;"></div>

                <!-- 2. HTTP协议概述 -->
                <div class="http-principle-container">
                    <h4 class="sub-section-title">2. HTTP协议概述</h4>
                    <p class="principle-intro" style="margin-bottom: 20px;">
                        在上述架构图中，客户端与服务器之间的数据通信主要依赖<strong>HTTP（超文本传输协议）</strong>。它基于<strong>客户端/服务器（C/S）架构</strong>和<strong>请求-响应模式</strong>工作，是整个万维网运作的最底层基础。
                    </p>

                    <div class="principle-features">
                        <div class="feature-item">
                            <div class="feature-title">🌐 无状态协议</div>
                            <div class="feature-desc">HTTP协议本身是无状态的，即服务器不会主动去保留两次请求之间的任何联系或信息。为了识别用户和维持状态，现代WEB一般结合使用 Cookie 和 Session 机制。</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-title">🔄 请求-响应模型</div>
                            <div class="feature-desc">典型的交互过程非常清晰：客户端向服务器发起一条特定的HTTP请求，服务器端处理完请求后，向客户端返回包含结果的某一条HTTP响应，一问一答即完成一次交互。</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-title">🔗 简单快速灵活</div>
                            <div class="feature-desc">在客户端请求服务时，大多只需传输特定的请求方法及目标路径即可。这使得HTTP通信具备了体量小、响应快的特征，并允许在请求中携带传输多种形式类别的数据资源。</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- HTTP请求解析区域 -->
    <div class="heasec-section">
        <div class="heasec-card">
            <div class="heasec-card-header">
                <h3>HTTP请求解析</h3>
            </div>
            <div class="heasec-card-body">
                <!-- 原始请求 -->
                <div class="request-section">
                    <h4>原始请求</h4>
                    <div class="code-block">
                        <div class="line-label">请求行：</div>
                        <div class="code-line request-line">
                            <span
                                class="line-content"><?php echo htmlspecialchars($requestInfo['request_line']); ?></span>
                        </div>
                        <div class="section-separator"></div>
                        <div class="code-line">
                            <span class="line-label">请求头：</span>
                        </div>
                        <?php foreach ($requestInfo['headers'] as $name => $value): ?>
                            <div class="code-line header-line <?php echo $name; ?>">
                                <span class="header-name"><?php echo htmlspecialchars($name); ?>:</span>
                                <span class="header-value"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="section-separator"></div>
                        <div class="code-line">
                            <span class="line-label">请求体：</span>
                        </div>
                        <div class="code-line body-line">
                            <?php if (!empty($requestInfo['body_display'])): ?>
                                <?php echo htmlspecialchars($requestInfo['body_display']); ?>
                            <?php else: ?>
                                <em>本次请求未包含请求体</em>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 请求行解析 -->
                <div class="collapsible-section" id="requestLineSection">
                    <h4 class="collapsible-header" onclick="toggleSection('requestLineSection')">
                        <span class="toggle-text">请求行解析</span>
                        <div class="toggle-btn"></div>
                    </h4>
                    <div class="collapsible-content">
                        <p>请求行是HTTP请求中的第一行，包含了请求方法、请求URL和HTTP版本部分，以空格符（%20）分隔，以回车换行符结尾（%0d%0a）。</p>

                        <table class="heasec-table">
                            <thead>
                                <tr>
                                    <th>字段</th>
                                    <th>作用说明</th>
                                    <th>内容解析</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>请求方法</td>
                                    <td>
                                        <div class="main-desc">
                                            指定客户端请求的操作类型，请求方法定义了客户端希望服务器收到请求后要执行的"动作"。
                                        </div>

                                        <div class="section-title">常用请求方法：</div>
                                        <ul>
                                            <li><strong>GET</strong>：请求获取资源，不修改服务器状态</li>
                                            <li><strong>POST</strong>：提交数据到服务器，可能会修改服务器状态</li>
                                            <li><strong>PUT</strong>：更新服务器上的资源，要求客户端提供完整的资源表示</li>
                                            <li><strong>DELETE</strong>：请求删除服务器上的资源</li>
                                            <li><strong>HEAD</strong>：与GET方法类似，仅返回响应头信息，不返回实体内容</li>
                                            <li><strong>OPTIONS</strong>：请求获取服务器支持的请求方法和其他选项</li>
                                            <li><strong>TRACE</strong>：回显服务器收到的请求，主要用于诊断和调试</li>
                                            <li><strong>CONNECT</strong>：用于建立隧道连接，通常用于HTTPS代理</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <strong>当前值：</strong><?php echo htmlspecialchars($requestInfo['method']); ?><br>
                                        <strong>解析：</strong><br>
                                        <?php
                                        if ($requestLineTemplates && isset($requestLineTemplates['templates']['methods'][$requestInfo['method']])) {
                                            echo nl2br(htmlspecialchars($requestLineTemplates['templates']['methods'][$requestInfo['method']]['analysis']));
                                        } else {
                                            echo "本次请求中使用的请求方法为" . htmlspecialchars($requestInfo['method']) . "。";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>请求路径</td>
                                    <td>
                                        <div class="main-desc">
                                            请求路径定义了客户端请求的资源位置，服务器根据该路径定位和处理请求。
                                        </div>

                                        <div class="section-title">基本格式说明：</div>
                                        <ul>
                                            <li><strong>绝对路径</strong>：以/开头的完整路径</li>
                                            <li><strong>查询参数</strong>：以?分隔路径和参数</li>
                                            <li><strong>多参数分隔</strong>：多个参数用&分隔</li>
                                            <li><strong>参数格式</strong>：key=value形式</li>
                                            <li><strong>URL编码</strong>：特殊字符转换为%XX格式
                                                <ul style="margin-top: 5px; margin-bottom: 0;">
                                                    <li><strong>空格</strong>：%20</li>
                                                    <li><strong>&符号</strong>：%26</li>
                                                    <li><strong>=符号</strong>：%3D</li>
                                                    <li><strong>?符号</strong>：%3F</li>
                                                    <li><strong>/符号</strong>：%2F</li>
                                                    <li><strong>中文字符</strong>：%E4%B8%AD%E6%96%87（先转换为UTF-8，再URL编码）</li>
                                                </ul>
                                            </li>
                                        </ul>


                                    </td>
                                    <td>
                                        <strong>当前值：</strong><?php echo htmlspecialchars($requestInfo['uri']); ?><br>
                                        <strong>解析说明：</strong><br>
                                        <?php
                                        // 智能解析请求路径
                                        function parseRequestPathSmart($uri)
                                        {
                                            $uri = trim($uri, '/');

                                            // 分离路径和参数
                                            if (strpos($uri, '?') !== false) {
                                                list($path, $queryString) = explode('?', $uri, 2);
                                            } else {
                                                $path = $uri;
                                                $queryString = '';
                                            }

                                            // 解析资源路径部分
                                            $pathInfo = '';
                                            if (empty($path)) {
                                                $pathInfo = "本次请求的资源路径为根目录下的资源；";
                                            } else {
                                                // 检查是否包含具体文件
                                                $pathParts = explode('/', $path);
                                                $lastPart = end($pathParts);

                                                if (strpos($lastPart, '.') !== false) {
                                                    // 包含具体文件
                                                    $pathInfo = "本次请求的资源路径为" . htmlspecialchars($path) . "文件；";
                                                } else {
                                                    // 目录路径
                                                    $pathInfo = "本次请求的资源路径为" . htmlspecialchars($path) . "目录下的资源，没有指定具体的资源文件，因此服务器会返回该目录下的默认资源，如index.php文件；";
                                                }
                                            }

                                            // 解析参数部分
                                            $paramInfo = '';
                                            if (!empty($queryString)) {
                                                parse_str($queryString, $params);
                                                if (!empty($params)) {
                                                    $paramCount = count($params);
                                                    $paramNames = array_keys($params);
                                                    $paramValues = array_values($params);

                                                    $paramInfo .= "?" . htmlspecialchars($queryString) . "表示传递了" . $paramCount . "个参数，";
                                                    $paramInfo .= "分别是" . implode("和", array_map('htmlspecialchars', $paramNames));
                                                    $paramInfo .= "，对应的值分别是" . implode("和", array_map('htmlspecialchars', $paramValues)) . "。";
                                                }
                                            } else {
                                                $paramInfo = "本次请求路径中没有传递任何参数。";
                                            }

                                            // 在资源路径和参数说明之间添加换行
                                            return $pathInfo . "<br>" . $paramInfo;
                                        }

                                        echo $httpParser->parseRequestPathSmart($requestInfo['uri']);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>HTTP版本</td>
                                    <td>
                                        <div class="main-desc">
                                            HTTP版本字段指定了客户端和服务器之间通信的规则，常用值为HTTP/1.1，HTTP/2等。
                                        </div>

                                        <div class="section-title">主流HTTP版本：</div>
                                        <ul>
                                            <li><strong>HTTP/1.0</strong>：每次请求都需要建立新的TCP连接</li>
                                            <li><strong>HTTP/1.1</strong>：支持持久连接，性能更好</li>
                                            <li><strong>HTTP/2</strong>：基于二进制协议，支持多路复用</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <strong>当前值：</strong><?php echo htmlspecialchars($requestInfo['protocol']); ?><br>
                                        <strong>解析：</strong><br>
                                        <?php
                                        if ($requestLineTemplates && isset($requestLineTemplates['templates']['versions'][$requestInfo['protocol']])) {
                                            echo nl2br(htmlspecialchars($requestLineTemplates['templates']['versions'][$requestInfo['protocol']]['analysis']));
                                        } else {
                                            echo "本次请求中使用的HTTP版本为" . htmlspecialchars($requestInfo['protocol']) . "。";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 请求头解析 -->
                <div class="collapsible-section" id="requestHeaderSection">
                    <h4 class="collapsible-header" onclick="toggleSection('requestHeaderSection')">
                        <span class="toggle-text">请求头解析</span>
                        <div class="toggle-btn"></div>
                    </h4>
                    <div class="collapsible-content">
                        <p>请求头是HTTP请求中的第二行开始连续多行，包含了关于请求的元数据信息。每行一个字段，以key-value格式表示。最后一个请求头后以两个回车换行符结尾（%0d%0a）用于和请求体分隔。以下是本次请求中的请求头中常见的字段解析：
                        </p>

                        <table class="heasec-table">
                            <thead>
                                <tr>
                                    <th>字段</th>
                                    <th>作用说明</th>
                                    <th>内容解析</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 按需求文档要求的顺序显示请求头字段
                                $commonHeaders = array('Accept-Charset', 'Accept-Language', 'Cache-Control', 'Connection', 'Cookie', 'Content-Length', 'Content-Type', 'Date', 'Host', 'Referer', 'User-Agent', 'X-Forwarded-For');
                                foreach ($commonHeaders as $headerName):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($headerName); ?></td>
                                        <td>
                                            <?php
                                            // 根据不同的请求头字段提供格式化的说明
                                            switch ($headerName) {
                                                case 'Accept-Charset':
                                                    echo '<div class="main-desc">
                                                告知服务器客户端可以接受的字符编码。
                                            </div>
                                            <div class="section-title">常用字符编码：</div>
                                            <ul>
                                                <li><strong>UTF-8</strong>：Unicode编码，支持多语言字符</li>
                                                <li><strong>ISO-8859-1</strong>：拉丁字母编码</li>
                                                <li><strong>GBK</strong>：汉字内码扩展规范</li>
                                                <li><strong>GB2312</strong>：简体中文字符集</li>
                                            </ul>';
                                                    break;

                                                case 'Accept-Language':
                                                    echo '<div class="main-desc">
                                                告知服务器用户使用的语言，服务器可根据该信息返回相应的语言版本内容。语言代码可以有多个，每个之间用逗号分隔。q表示语言质量因子，值范围0-1，值越大表示越优先。
                                            </div>
                                            <div class="section-title">常用语言代码：</div>
                                            <ul>
                                                <li><strong>zh-CN</strong>：中文（简体）</li>
                                                <li><strong>zh-TW</strong>：中文（繁体）</li>
                                                <li><strong>en-US</strong>：英文（美国）</li>
                                                <li><strong>ja</strong>：日文</li>
                                            </ul>';
                                                    break;


                                                case 'Cookie':
                                                    echo '<div class="main-desc">
                                                告知服务器客户端之前发送过的cookie信息，服务器可根据该信息进行会话跟踪。
                                            </div>
                                            <div class="section-title">Cookie用途：</div>
                                            <ul>
                                                <li><strong>会话跟踪</strong>：识别用户会话状态</li>
                                                <li><strong>用户认证</strong>：保存登录状态</li>
                                                <li><strong>个性化设置</strong>：存储用户偏好</li>
                                                <li><strong>统计分析</strong>：记录用户行为数据</li>
                                            </ul>';
                                                    break;

                                                case 'Host':
                                                    echo '<div class="main-desc">
                                                告知服务器请求的目标主机名和端口号。
                                            </div>
                                            <div class="section-title">主机格式：</div>
                                            <ul>
                                                <li><strong>域名</strong>：example.com</li>
                                                <li><strong>域名+端口</strong>：example.com:8080</li>
                                                <li><strong>IP地址</strong>：192.168.1.1</li>
                                            </ul>';
                                                    break;

                                                case 'User-Agent':
                                                    echo '<div class="main-desc">
                                                告知服务器客户端使用的浏览器和操作系统信息。
                                            </div>
                                            <div class="section-title">组成部分：</div>
                                            <ul>
                                                <li><strong>浏览器</strong>：Chrome、Firefox、Safari等</li>
                                                <li><strong>操作系统</strong>：Windows、macOS、Linux等</li>
                                                <li><strong>设备类型</strong>：桌面、移动设备、平板等</li>
                                                <li><strong>渲染引擎</strong>：WebKit、Gecko、Blink等</li>
                                            </ul>';
                                                    break;

                                                case 'Cache-Control':
                                                    echo '<div class="main-desc">
                                                指定请求和响应的缓存机制。
                                            </div>
                                            <div class="section-title">常用缓存指令：</div>
                                            <ul>
                                                <li><strong>no-cache</strong>：不使用缓存，每次请求都返回最新资源</li>
                                                <li><strong>no-store</strong>：不存储缓存</li>
                                                <li><strong>max-age=秒</strong>：缓存最大有效时间，超过后需要返回最新资源</li>
                                            </ul>';
                                                    break;

                                                case 'Content-Type':
                                                    echo '<div class="main-desc">
                                                告知服务器请求体的媒体类型。
                                            </div>
                                            <div class="section-title">常用媒体类型：</div>
                                            <ul>
                                                <li><strong>application/x-www-form-urlencoded</strong>：表单数据</li>
                                                <li><strong>multipart/form-data</strong>：文件上传</li>
                                                <li><strong>application/json</strong>：JSON数据</li>
                                                <li><strong>text/plain</strong>：纯文本</li>
                                                <li><strong>text/html</strong>：HTML文档</li>
                                                <li><strong>application/xml</strong>：XML数据</li>
                                            </ul>';
                                                    break;

                                                default:
                                                    if ($requestHeaderTemplates && isset($requestHeaderTemplates['templates'][$headerName])) {
                                                        echo htmlspecialchars($requestHeaderTemplates['templates'][$headerName]['description']);
                                                    } else {
                                                        echo '<div class="description-main">
                                                    HTTP请求头字段：' . htmlspecialchars($headerName) . '
                                                </div>';
                                                    }
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if (isset($requestInfo['headers'][$headerName])) {
                                                // 请求头存在的情况
                                                echo '<strong>当前值：</strong>' . htmlspecialchars($requestInfo['headers'][$headerName]) . '<br>';
                                                echo '<strong>解析：</strong><br>';

                                                $headerValue = $requestInfo['headers'][$headerName];
                                                $analysis = '';

                                                if ($requestHeaderTemplates && isset($requestHeaderTemplates['templates'][$headerName]['common_values'])) {
                                                    foreach ($requestHeaderTemplates['templates'][$headerName]['common_values'] as $template) {
                                                        if (isset($template['pattern'])) {
                                                            // 使用正则匹配
                                                            if (preg_match('/' . $template['pattern'] . '/i', $headerValue)) {
                                                                $analysis = $template['analysis'];
                                                                break;
                                                            }
                                                        } elseif (isset($template['current_value']) && $template['current_value'] === $headerValue) {
                                                            $analysis = $template['analysis'];
                                                            break;
                                                        }
                                                    }
                                                }

                                                if (empty($analysis)) {
                                                    // 使用智能解析函数
                                                    switch ($headerName) {
                                                        case 'Accept-Charset':
                                                            $analysis = $httpParser->parseAcceptCharset($headerValue);
                                                            break;
                                                        case 'Accept-Language':
                                                            $analysis = $httpParser->parseAcceptLanguage($headerValue);
                                                            break;
                                                        case 'Cache-Control':
                                                            $analysis = $httpParser->parseCacheControl($headerValue);
                                                            break;
                                                        case 'Cookie':
                                                            $analysis = $httpParser->parseCookie($headerValue);
                                                            break;
                                                        case 'Content-Length':
                                                            $analysis = $httpParser->parseContentLength($headerValue, $requestInfo['method']);
                                                            break;
                                                        case 'Content-Type':
                                                            $analysis = $httpParser->parseContentType($headerValue, $requestInfo['method']);
                                                            break;
                                                        case 'Date':
                                                            $analysis = $httpParser->parseDate($headerValue);
                                                            break;
                                                        case 'Host':
                                                            $analysis = $httpParser->parseHost($headerValue);
                                                            break;
                                                        case 'Referer':
                                                            $analysis = $httpParser->parseReferer($headerValue);
                                                            break;
                                                        case 'User-Agent':
                                                            $analysis = $httpParser->parseUserAgent($headerValue);
                                                            break;
                                                        case 'X-Forwarded-For':
                                                            $analysis = $httpParser->parseXForwardedFor($headerValue);
                                                            break;
                                                        default:
                                                            $analysis = "本次请求头" . htmlspecialchars($headerName) . "的值为：" . htmlspecialchars($headerValue) . "。";
                                                    }
                                                }

                                                echo $analysis;
                                            } else {
                                                // 请求头不存在的情况 - 智能解析
                                                echo '<div>';
                                                echo '<strong>当前值：</strong>本次请求未包含此字段<br>';
                                                echo '<strong>解析：</strong>';

                                                // 根据不同的头部字段调用智能解析函数
                                                switch ($headerName) {
                                                    case 'Accept-Charset':
                                                        echo $httpParser->parseAcceptCharset('');
                                                        break;
                                                    case 'Accept-Language':
                                                        echo $httpParser->parseAcceptLanguage('');
                                                        break;
                                                    case 'Cache-Control':
                                                        echo $httpParser->parseCacheControl('');
                                                        break;
                                                    case 'Connection':
                                                        echo $httpParser->parseConnection('');
                                                        break;
                                                    case 'Content-Length':
                                                        echo $httpParser->parseContentLength('', $requestInfo['method']);
                                                        break;
                                                    case 'Content-Type':
                                                        echo $httpParser->parseContentType('', $requestInfo['method']);
                                                        break;
                                                    case 'Date':
                                                        echo $httpParser->parseDate('');
                                                        break;
                                                    case 'Host':
                                                        echo $httpParser->parseHost('');
                                                        break;
                                                    case 'Referer':
                                                        echo $httpParser->parseReferer('');
                                                        break;
                                                    case 'User-Agent':
                                                        echo $httpParser->parseUserAgent('');
                                                        break;
                                                    case 'X-Forwarded-For':
                                                        echo $httpParser->parseXForwardedFor('');
                                                        break;
                                                    default:
                                                        echo '本次请求未包含' . htmlspecialchars($headerName) . '请求头字段。' . $httpParser->getHeaderIntelligentAnalysis($headerName, 'request');
                                                        break;
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 请求体解析 -->
                <div class="collapsible-section" id="requestBodySection">
                    <h4 class="collapsible-header" onclick="toggleSection('requestBodySection')">
                        <span class="toggle-text">请求体解析</span>
                        <div class="toggle-btn"></div>
                    </h4>
                    <div class="collapsible-content">
                        <p>请求体是HTTP请求中的最后一部分，包含了请求的实体内容。请求体的格式和内容类型由请求头中的Content-Type字段指定。</p>

                        <div class="analysis-content">
                            <?php
                            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
                            echo $httpParser->parseRequestBody($requestInfo['body'], $contentType);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HTTP响应解析区域 -->
    <div class="heasec-section">
        <div class="heasec-card">
            <div class="heasec-card-header">
                <h3>HTTP响应解析</h3>
            </div>
            <div class="heasec-card-body">
                <!-- 原始响应 -->
                <div class="response-section">
                    <h4>原始响应</h4>
                    <div class="code-block">
                        <div class="line-label">状态行：</div>
                        <div class="code-line status-line">
                            <span
                                class="line-content"><?php echo htmlspecialchars($responseInfo['status_line']); ?></span>
                        </div>
                        <div class="section-separator"></div>
                        <div class="code-line">
                            <span class="line-label">响应头：</span>
                        </div>
                        <?php foreach ($responseInfo['headers'] as $header): ?>
                            <?php list($name, $value) = explode(':', $header, 2); ?>
                            <div class="code-line header-line <?php echo htmlspecialchars(trim($name)); ?>">
                                <span class="header-name"><?php echo htmlspecialchars(trim($name)); ?>:</span>
                                <span class="header-value"><?php echo htmlspecialchars(trim($value)); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="section-separator"></div>
                        <div class="code-line">
                            <span class="line-label">响应体：</span>
                        </div>
                        <div class="code-line response-body-line">
                            <em>[响应体内容 - HTML文档格式，包含当前页面的所有内容]</em>
                        </div>
                    </div>
                </div>

                <!-- 可展开的解析部分 -->
                <div class="collapsible-section" id="statusLineSection">
                    <h4 class="collapsible-header" onclick="toggleSection('statusLineSection')">
                        <span class="toggle-text">状态行解析</span>
                        <div class="toggle-btn"></div>
                    </h4>
                    <div class="collapsible-content">
                        <p>状态行是HTTP响应中的第一行，包含了服务器对请求的处理结果。状态行由HTTP版本、状态码和状态描述三部分组成，以空格符（%20）分隔，以回车换行符结尾（%0d%0a）。以下是本次响应中的状态行中常见的字段解析：
                        </p>

                        <table class="heasec-table">
                            <thead>
                                <tr>
                                    <th>字段</th>
                                    <th>作用说明</th>
                                    <th>内容解析</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>HTTP版本</td>
                                    <td>定义了客户端和服务器之间通信的规则，常用值为HTTP/1.1，HTTP/2等。</td>
                                    <td>
                                        <strong>当前值：</strong><?php echo htmlspecialchars($responseInfo['protocol']); ?><br>
                                        <strong>解析：</strong><br>
                                        <?php
                                        echo $httpParser->parseHttpVersion($responseInfo['protocol']);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>状态码</td>
                                    <td>
                                        <div class="main-desc">
                                            状态码表示服务器对请求的处理结果，由三位数字组成，第一位数字表示响应类别。
                                        </div>
                                        <div class="section-title">分类说明：</div>
                                        <ul>
                                            <li><strong>1xx</strong>：信息性状态码，表示请求已被接收，继续处理。</li>
                                            <li><strong>2xx</strong>：成功状态码，表示请求已成功被服务器接收、理解并接受。</li>
                                            <li><strong>3xx</strong>：重定向状态码，表示需要客户端采取进一步操作才能完成请求。</li>
                                            <li><strong>4xx</strong>：客户端错误状态码，表示客户端可能发生了错误，妨碍了服务器的处理。</li>
                                            <li><strong>5xx</strong>：服务器错误状态码，表示服务器在处理请求时发生了错误。</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <strong>当前值：</strong><?php echo htmlspecialchars($responseInfo['status_code']); ?><br>
                                        <strong>解析：</strong><br>
                                        <?php
                                        echo $httpParser->parseStatusCode($responseInfo['status_code']);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>状态描述</td>
                                    <td>
                                        <div class="main-desc">
                                            状态描述是对状态码的简短文字说明，帮助用户理解响应结果。
                                        </div>
                                        <div class="section-title">常用值：</div>
                                        <ul>
                                            <li><strong>OK</strong>：表示请求已成功处理，是状态码200的默认描述。</li>
                                            <li><strong>Found</strong>：对应状态码302，表示资源临时移动到新位置。</li>
                                            <li><strong>Forbidden</strong>：对应状态码403，表示服务器拒绝提供服务。</li>
                                            <li><strong>Not Found</strong>：对应状态码404，表示请求的资源不存在。</li>
                                            <li><strong>Internal Server Error</strong>：对应状态码500，表示服务器内部错误。</li>
                                            <li><strong>Bad Gateway</strong>：对应状态码502，表示网关或代理服务器错误。</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <strong>当前值：</strong><?php echo htmlspecialchars($responseInfo['status_phrase']); ?><br>
                                        <strong>解析：</strong><br>
                                        <?php
                                        echo $httpParser->parseStatusPhrase($responseInfo['status_phrase']);
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="collapsible-section" id="responseHeaderSection">
                    <h4 class="collapsible-header" onclick="toggleSection('responseHeaderSection')">
                        <span class="toggle-text">响应头解析</span>
                        <div class="toggle-btn"></div>
                    </h4>
                    <div class="collapsible-content">
                        <p>响应头是HTTP响应中的第二行开始连续多行，包含了关于响应的元数据信息。每行一个字段，以key-value格式表示。最后一个响应头后以两个回车换行符结尾（%0d%0a）用于和响应体分隔。以下是本次响应中的响应头中常见的字段解析：
                        </p>

                        <table class="heasec-table">
                            <thead>
                                <tr>
                                    <th>字段</th>
                                    <th>作用说明</th>
                                    <th>内容解析</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $responseHeaders = array();
                                foreach ($responseInfo['headers'] as $header) {
                                    list($name, $value) = explode(':', $header, 2);
                                    $responseHeaders[trim($name)] = trim($value);
                                }

                                $commonResponseHeaders = array('Content-Type', 'Content-Length', 'Set-Cookie', 'Location', 'Server', 'X-Powered-By', 'Date', 'Cache-Control', 'Last-Modified', 'Allow', 'Refresh');
                                foreach ($commonResponseHeaders as $headerName):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($headerName); ?></td>
                                        <td>
                                            <?php
                                            // 为Cache-Control字段提供详细的作用说明
                                            if ($headerName === 'Cache-Control') {
                                                echo '<div class="main-desc">
                                                    指定响应的缓存策略，控制浏览器和代理服务器如何缓存响应内容。
                                                </div>
                                                <div class="section-title">常用缓存指令：</div>
                                                <ul>
                                                    <li><strong>public</strong>：响应可被任何缓存（包括浏览器和代理服务器）缓存</li>
                                                    <li><strong>private</strong>：响应只能被用户浏览器缓存，不能被代理服务器缓存</li>
                                                    <li><strong>no-cache</strong>：缓存前必须向服务器验证有效性，可缓存但必须重新验证</li>
                                                    <li><strong>no-store</strong>：完全禁止缓存，任何地方都不能存储响应内容</li>
                                                    <li><strong>max-age=秒</strong>：响应缓存的有效时间，超过时间后重新请求</li>
                                                    <li><strong>s-maxage=秒</strong>：代理服务器的缓存时间，覆盖max-age</li>
                                                    <li><strong>must-revalidate</strong>：缓存过期后必须向服务器验证，不能使用过期缓存</li>
                                                    <li><strong>immutable</strong>：内容永远不会改变，可无限期缓存</li>
                                                </ul>';
                                            } elseif ($responseHeaderTemplates && isset($responseHeaderTemplates['templates'][$headerName])) {
                                                echo htmlspecialchars($responseHeaderTemplates['templates'][$headerName]['description']);
                                            } else {
                                                echo "HTTP响应头字段：" . htmlspecialchars($headerName);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if (isset($responseHeaders[$headerName])) {
                                                // 响应头存在的情况
                                                echo '<strong>当前值：</strong>' . htmlspecialchars($responseHeaders[$headerName]) . '<br>';
                                                echo '<strong>解析：</strong><br>';

                                                $headerValue = $responseHeaders[$headerName];
                                                $analysis = '';

                                                if ($responseHeaderTemplates && isset($responseHeaderTemplates['templates'][$headerName]['common_values'])) {
                                                    foreach ($responseHeaderTemplates['templates'][$headerName]['common_values'] as $template) {
                                                        if (isset($template['current_value']) && strpos($headerValue, $template['current_value']) !== false) {
                                                            $analysis = $template['analysis'];
                                                            break;
                                                        }
                                                    }
                                                }

                                                if (empty($analysis)) {
                                                    // 使用智能解析函数
                                                    switch ($headerName) {
                                                        case 'Content-Type':
                                                            $analysis = $httpParser->parseResponseContentType($headerValue);
                                                            break;
                                                        case 'Content-Length':
                                                            // 动态获取当前的内容长度
                                                            $currentLength = HeaSec_ContentManager::getCurrentContentLength();

                                                            // 如果当前获取的长度比之前记录的长度大，则更新
                                                            if (!isset($GLOBALS['actual_content_length']) || $currentLength > $GLOBALS['actual_content_length']) {
                                                                $GLOBALS['actual_content_length'] = $currentLength;
                                                            }

                                                            $actualLength = $GLOBALS['actual_content_length'];
                                                            $analysis = $httpParser->parseResponseContentLength($headerValue, $actualLength);
                                                            break;
                                                        case 'Set-Cookie':
                                                            $analysis = $httpParser->parseSetCookie($headerValue);
                                                            break;
                                                        case 'Location':
                                                            $analysis = $httpParser->parseLocation($headerValue);
                                                            break;
                                                        case 'Server':
                                                            $analysis = $httpParser->parseServer($headerValue);
                                                            break;
                                                        case 'X-Powered-By':
                                                            $analysis = $httpParser->parseXPoweredBy($headerValue);
                                                            break;
                                                        case 'Date':
                                                            $analysis = $httpParser->parseResponseDate($headerValue);
                                                            break;
                                                        case 'Cache-Control':
                                                            $analysis = $httpParser->parseResponseCacheControl($headerValue);
                                                            break;
                                                        case 'Allow':
                                                            $analysis = $httpParser->parseAllow($headerValue);
                                                            break;
                                                        case 'Refresh':
                                                            $analysis = $httpParser->parseRefresh($headerValue);
                                                            break;
                                                        default:
                                                            $analysis = "本次响应头" . htmlspecialchars($headerName) . "的值为：" . htmlspecialchars($headerValue) . "。";
                                                    }
                                                }

                                                echo $analysis;
                                            } else {
                                                // 响应头不存在的情况 - 智能解析
                                                echo '<div>';

                                                // Content-Length字段特殊处理
                                                if ($headerName === 'Content-Length') {
                                                    // 动态获取当前的内容长度
                                                    $currentLength = HeaSec_ContentManager::getCurrentContentLength();

                                                    // 如果当前获取的长度比之前记录的长度大，则更新
                                                    if (!isset($GLOBALS['actual_content_length']) || $currentLength > $GLOBALS['actual_content_length']) {
                                                        $GLOBALS['actual_content_length'] = $currentLength;
                                                    }

                                                    $actualLength = $GLOBALS['actual_content_length'];
                                                    echo '<strong>当前值：</strong>' . $actualLength . '【由于靶场网站功能原因，该值为服务端提前计算，可能与实际情况不同】<br>';
                                                    echo '<strong>解析：</strong>';
                                                    echo $httpParser->parseResponseContentLength('', $actualLength);
                                                } else {
                                                    echo '<strong>当前值：</strong>本次响应未包含此字段<br>';
                                                    echo '<strong>解析：</strong>';

                                                    // 根据不同的响应头字段调用智能解析函数
                                                    switch ($headerName) {
                                                        case 'Content-Type':
                                                            echo $httpParser->parseResponseContentType('');
                                                            break;
                                                        case 'Set-Cookie':
                                                            echo $httpParser->parseSetCookie('');
                                                            break;
                                                        case 'Location':
                                                            echo $httpParser->parseLocation('');
                                                            break;
                                                        case 'Server':
                                                            echo $httpParser->parseServer('');
                                                            break;
                                                        case 'X-Powered-By':
                                                            echo $httpParser->parseXPoweredBy('');
                                                            break;
                                                        case 'Date':
                                                            echo $httpParser->parseResponseDate('');
                                                            break;
                                                        case 'Allow':
                                                            echo $httpParser->parseAllow('');
                                                            break;
                                                        case 'Refresh':
                                                            echo $httpParser->parseRefresh('');
                                                            break;
                                                        case 'Last-Modified':
                                                            echo $httpParser->parseLastModified('');
                                                            break;
                                                        case 'ETag':
                                                            echo $httpParser->parseETag('');
                                                            break;
                                                        case 'Expires':
                                                            echo $httpParser->parseExpires('');
                                                            break;
                                                        case 'Cache-Control':
                                                            echo $httpParser->parseResponseCacheControl('');
                                                            break;
                                                        case 'Content-Encoding':
                                                            echo $httpParser->parseContentEncoding('');
                                                            break;
                                                        case 'Content-Disposition':
                                                            echo $httpParser->parseContentDisposition('');
                                                            break;
                                                        case 'Access-Control-Allow-Origin':
                                                            echo $httpParser->parseAccessControlAllowOrigin('');
                                                            break;
                                                        case 'Vary':
                                                            echo $httpParser->parseVary('');
                                                            break;
                                                        default:
                                                            echo '本次响应未包含' . htmlspecialchars($headerName) . '响应头字段。' . $httpParser->getHeaderIntelligentAnalysis($headerName, 'response');
                                                            break;
                                                    }
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="collapsible-section" id="responseBodySection">
                    <h4 class="collapsible-header" onclick="toggleSection('responseBodySection')">
                        <span class="toggle-text">响应体解析</span>
                        <div class="toggle-btn"></div>
                    </h4>
                    <div class="collapsible-content">
                        <p>响应体是HTTP响应中的最后一部分，包含了服务器返回给客户端的实际内容。</p>

                        <div class="analysis-content">
                            <?php
                            // 获取Content-Type
                            $responseContentType = '';
                            foreach ($responseInfo['headers'] as $header) {
                                if (strpos(strtolower($header), 'content-type:') === 0) {
                                    $responseContentType = substr($header, 13);
                                    $responseContentType = trim($responseContentType);
                                    break;
                                }
                            }
                            echo $httpParser->parseResponseBody('HTML响应体内容', $responseContentType);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 完成学习卡片 -->
    <div class="heasec-section">
        <div class="heasec-card mastery-card">
            <div class="heasec-card-header">
                <h3><i class="fa fa-graduation-cap"></i> 完成学习</h3>
            </div>
            <div class="heasec-card-body">
                <div class="mastery-content">
                    <div class="mastery-text">
                        <p>如果你已经掌握了HTTP的基本知识，包括请求方法、请求头、状态码等重要概念，可以点击右侧按钮标记你的学习成果，继续你的WEB安全学习之旅！。</p>
                    </div>
                    <div class="mastery-actions">
                        <button type="button" class="heasec-mastery-btn" onclick="showMasteryCongrats()">
                            <i class="fa fa-check-circle"></i>
                            我已掌握
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        section.classList.toggle('expanded');
    }

    // 默认收起所有可展开区域
    document.addEventListener('DOMContentLoaded', function () {
        // 所有区域默认收起，不添加expanded类
        // 用户可以手动点击展开感兴趣的区域
    });

    // 表单提交处理
    document.getElementById('httpRequestForm').addEventListener('submit', function (e) {
        const submitButton = e.submitter;
        if (submitButton && submitButton.value === 'get') {
            // GET请求，允许正常提交
            return true;
        } else if (submitButton && submitButton.value === 'post') {
            // POST请求，修改form method和action
            e.preventDefault();
            this.method = 'post';
            // 设置action为当前页面路径，去除所有查询参数
            this.action = window.location.pathname;
            this.submit();
            return false;
        }
    });

    // HTTP状态码测试函数
    function testRedirect() {
        // 直接打开重定向测试页面，让服务端处理302跳转
        const redirectUrl = window.location.origin + window.location.pathname.replace('index.php', 'redirect-302.php') + '?timestamp=' + Date.now();
        window.open(redirectUrl, '_blank');
    }

    function test404() {
        // 新开标签页访问404错误页面
        const notFoundUrl = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + 'nonexistent-page-' + Date.now() + '.html';
        window.open(notFoundUrl, '_blank');
    }

    function test500() {
        // 新开标签页访问500错误页面
        const errorUrl = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + 'trigger-500-error.php?timestamp=' + Date.now();
        window.open(errorUrl, '_blank');
    }

    // 显示掌握恭喜消息
    function showMasteryCongrats() {
        // 检查HeaSecCongratsModal是否可用
        if (typeof HeaSecCongratsModal !== 'undefined') {
            HeaSecCongratsModal.show({
                title: '🎉 恭喜你掌握了一个新技能',
                message: '你理解了HTTP的基本知识，包括HTTP请求方法、请求头、状态码等重要概念。这些知识是你学习网络安全的重要基础！',
                buttonText: '太棒了！',
                showParticles: true,
                particleCount: 15,
                animationDuration: 3000,

                // 启用下一靶场功能
                enableNextRangeButton: true,
                rangeCode: 'httpxyjx',          // 当前靶场代码
                nextRangeApiUrl: '<?php echo $commonBasePath; ?>api/next-range.php',

                // 自动更新学习状态
                updateLearningStatus: true,
                updateStatusApiUrl: '<?php echo $commonBasePath; ?>api/update-learning-status.php',
                learningStatus: '已掌握',     // '已掌握' 或 '学习中'

                // 回调函数
                onClose: function () {
                    console.log('恭喜消息弹窗已关闭');
                },
                onContinue: function () {
                    console.log('用户选择继续学习');
                }
            });
        } else {
            // 降级处理：如果星星系统未加载，显示简单的alert
            alert('🎉 恭喜你掌握了HTTP基础知识！\n\n你已经理解了HTTP的基本知识，包括请求方法、请求头、状态码等重要概念。这些知识是你学习WEB安全的重要基础！');
        }
    }

</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>

<!-- 引入自定义重置处理器 - 在公共组件之后加载以覆盖默认行为 -->
<script src="assets/js/reset_handler.js"></script>