<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '会话安全靶场 - 第三关';
$rangeName = '会话安全';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$resetUrl = 'api/reset.php';

// 当前关卡配置
$currentLevel = 3;
$levelTitle = '第三关：登前登后都一样';
$taskHint = '目标：通过会话固定获取通关密码，请先尝试为用户指定会话ID，再使用该会话ID登录即可获取通关密码';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 引入公共函数
require_once 'includes/functions.php';

// 引入数据库组件（需要在会话初始化前获取连接）
require_once $commonBasePath . 'includes/HeaSec_Database.php';
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 确保通关密码已生成
getOrCreatePasscode($currentLevel, $pdo);

// ========== 处理GET参数（在会话初始化之前/期间） ==========

// 处理sid参数
$sidParam = isset($_GET['sid']) ? $_GET['sid'] : '';
$targetSessionIdFromSid = '';
if (!empty($sidParam) && preg_match('/^[a-z0-9]+$/', $sidParam)) {
    $targetSessionIdFromSid = $sidParam;
}

// 处理url参数（具备真实的重定向业务功能）
$urlParam = isset($_GET['url']) ? $_GET['url'] : '';
$targetSessionIdFromUrl = '';
$urlForRedirect = '';
$urlHasCRLF = false;

if (!empty($urlParam)) {
    // URL解码处理
    $decodedUrl = $urlParam;
    if (strpos($decodedUrl, '%0d%0a') !== false) {
        $decodedUrl = urldecode($decodedUrl);
    }

    // 检测CRLF字符
    $hasCR = strpos($decodedUrl, "\r") !== false;
    $hasLF = strpos($decodedUrl, "\n") !== false;

    if ($hasCR || $hasLF) {
        // 校验CRLF格式：只允许标准的\r\n对（参考CRLF靶场）
        $testString = str_replace("\r\n", '', $decodedUrl);
        if (strpos($testString, "\r") !== false || strpos($testString, "\n") !== false) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html><body>';
            echo '<h1>500 Internal Server Error</h1>';
            echo '<p>请求不符合HTTP协议规范</p>';
            echo '</body></html>';
            exit;
        }

        $urlHasCRLF = true;
        $urlForRedirect = '';
        $bodyPart = '';

        // 检测双CRLF（头部与正文分隔）
        $doubleCRLFPos = strpos($decodedUrl, "\r\n\r\n");
        $headerLines = [];

        if ($doubleCRLFPos !== false) {
            // 分离头部注入部分和响应体
            $headerPart = substr($decodedUrl, 0, $doubleCRLFPos);
            $bodyPart = substr($decodedUrl, $doubleCRLFPos + 4);
            $allLines = explode("\r\n", $headerPart);
            $urlForRedirect = $allLines[0];
            $headerLines = array_slice($allLines, 1);
        } else {
            // 仅单CRLF，只有头部注入
            $allLines = explode("\r\n", $decodedUrl);
            $urlForRedirect = $allLines[0];
            $headerLines = array_slice($allLines, 1);
        }

        // 处理注入的HTTP头部，通过header()函数输出
        foreach ($headerLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (strpos($line, ':') !== false) {
                // 提取Set-Cookie中的session ID用于日志记录
                if (preg_match('/^Set-Cookie:\s*HEASEC_RANGE_SESSION_SESSION=([a-zA-Z0-9]+)/i', $line, $cookieMatch)) {
                    $targetSessionIdFromUrl = $cookieMatch[1];
                }
                header($line, false);
            }
        }

        // 记录参数到数据库（在重定向前完成）
        if (!empty($targetSessionIdFromUrl)) {
            logRequestParam($currentLevel, 'url', $urlParam, $targetSessionIdFromUrl, $pdo);
        }

        // 输出Location头（真实的业务功能：登录后重定向）
        header('Location: ' . $urlForRedirect);

        // 如果有注入的正文，输出
        if (!empty($bodyPart)) {
            echo $bodyPart;
        }
        exit;
    } else {
        // 无CRLF，存储URL用于登录后重定向
        $urlForRedirect = $decodedUrl;
    }
}

// 处理username参数
$usernameParam = isset($_GET['username']) ? $_GET['username'] : '';
$targetSessionIdFromUsername = '';
if (!empty($usernameParam)) {
    // 用正则提取目标会话ID
    if (preg_match('/HEASEC_RANGE_SESSION_SESSION=([a-zA-Z0-9]+)/', $usernameParam, $xssMatch)) {
        $targetSessionIdFromUsername = $xssMatch[1];
    }
}

// ========== 会话初始化 ==========

// 如果有sid参数，先关闭可能存在的会话，然后设置会话ID
if (!empty($targetSessionIdFromSid)) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    session_id($targetSessionIdFromSid);
}

// 初始化靶场会话
initRangeSession($currentLevel);

// ========== 处理无CRLF的URL重定向（业务功能） ==========

if (!empty($urlForRedirect) && !$urlHasCRLF) {
    // 用户已登录时执行重定向
    $preLoggedIn = isset($_SESSION['session_user_id_level3'])
        && isset($_SESSION['session_logged_in_level3'])
        && $_SESSION['session_logged_in_level3'] === true;
    if ($preLoggedIn) {
        header('Location: ' . $urlForRedirect);
        exit;
    }
}

// ========== 记录参数到数据库 ==========

if (!empty($targetSessionIdFromSid)) {
    logRequestParam($currentLevel, 'sid', $sidParam, $targetSessionIdFromSid, $pdo);
}

if (!empty($targetSessionIdFromUsername)) {
    logRequestParam($currentLevel, 'username', $usernameParam, $targetSessionIdFromUsername, $pdo);
}

// ========== 引入公共组件 ==========

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// ========== 检查登录状态 ==========

$isLoggedIn = false;
$userData = null;
$passcode = null;

$userId = isset($_SESSION['session_user_id_level3']) ? $_SESSION['session_user_id_level3'] : null;
$loggedIn = isset($_SESSION['session_logged_in_level3']) && $_SESSION['session_logged_in_level3'] === true;

if ($userId && $loggedIn) {
    $user = getUserById($userId, $currentLevel, $pdo);
    if ($user) {
        $isLoggedIn = true;
        $userData = [
            'username' => $user['username'],
            'realname' => $user['realname']
        ];

        // 检查当前会话ID是否在参数记录中
        $currentSessionId = session_id();
        if (isSessionIdInParamLogs($currentSessionId, $currentLevel, $pdo)) {
            $passcode = getOrCreatePasscode($currentLevel, $pdo);
        }
    }
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 用户登录/信息卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-shield"></i>
                <span id="mainCardTitle"><?php echo $isLoggedIn ? '用户信息' : $levelTitle ; ?></span>
            </h3>
            <button type="button" class="header-logout-btn" id="logoutBtn" style="<?php echo $isLoggedIn ? 'display:inline-flex;' : 'display:none;'; ?>">
                <i class="fa fa-sign-out"></i> 退出登录
            </button>
        </div>
        <div class="tech-card-body">
            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small><?php echo htmlspecialchars($taskHint); ?></small>
                </span>
            </div>

            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form" <?php echo $isLoggedIn ? 'style="display:none;"' : ''; ?>>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i> 用户名
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off" value="<?php echo $usernameParam; ?>">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                </div>
                <div id="loginErrorArea" class="alert-error" style="display: none; margin-bottom: 15px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span id="loginErrorMsg"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                </div>
            </form>

            <!-- 退出提示消息 -->
            <div id="logoutMsgArea" class="alert-logout" style="display: none;"></div>

            <!-- 用户信息区域（登录后显示） -->
            <div id="userInfoArea" style="<?php echo $isLoggedIn ? 'display:block;' : 'display:none;'; ?>">
                <div class="user-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-user"></i> 用户名：</span>
                        <span class="info-value" id="displayUsername"><?php echo $isLoggedIn ? htmlspecialchars($userData['username']) : ''; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-id-card"></i> 姓名：</span>
                        <span class="info-value" id="displayRealname"><?php echo $isLoggedIn ? htmlspecialchars($userData['realname']) : ''; ?></span>
                    </div>
                </div>

                <!-- 通关密码显示区域 -->
                <div id="passcodeDisplay" class="passcode-display" style="<?php echo $passcode ? 'display:flex;' : 'display:none;'; ?>">
                    <i class="fa fa-key"></i>
                    <span class="passcode-label">通关密码：</span>
                    <span class="passcode-value" id="displayPasscode"><?php echo $passcode ? htmlspecialchars($passcode) : ''; ?></span>
                </div>
            </div>

            <!-- 测试账号提示 -->
            <div class="test-account-hint">
                <i class="fa fa-info-circle"></i> 测试账号：test / 123456
            </div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i> 通关验证
            </h3>
        </div>
        <div class="tech-card-body">
            <form id="verifyForm" class="tech-form">
                <div class="form-group">
                    <label for="passcode" class="form-label">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/session.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initSessionRange(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
