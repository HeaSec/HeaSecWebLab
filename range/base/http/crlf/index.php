<?php
/**
 * HeaSec天积安全团队 - CRLF注入靶场
 * 版本: v1.0.0
 * 创建日期: 2026-03-28
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec CRLF注入 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'CRLF注入靶场';
$rangeName = 'CRLF注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('crlf');
HeaSec_ValidateSession();

/**
 * 标记heasec用户为已完成通关
 *
 * @param string $passcode 通关密码
 * @param string $payload 通关payload
 * @param PDO $pdo 数据库连接
 */
function markHeasecUserCompleted($passcode, $payload, $pdo) {
    $stmt = $pdo->prepare('UPDATE heasec_crlf_users SET passcode = ?, payload = ?, completed_at = NOW() WHERE username = ?');
    $stmt->execute([$passcode, $payload, 'heasec']);
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_base');

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['crlf_user_id']);
    unset($_SESSION['crlf_username']);
    header('Location: index.php');
    exit;
}

// 检查登录状态
$isLoggedIn = isset($_SESSION['crlf_user_id']) && !empty($_SESSION['crlf_user_id']);
$userData = null;
$passcode = null;

if ($isLoggedIn) {
    // 获取用户信息
    $stmt = $pdo->prepare('SELECT * FROM heasec_crlf_users WHERE id = ?');
    $stmt->execute([$_SESSION['crlf_user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $passcode = $userData['passcode'];
    }
}

// 获取当前会话的秘密字符串
$secret = HeaSec_GetSecret(20);

// ============================================================
// 处理CRLF注入逻辑（漏洞模拟点）
// 模拟场景：服务器将GET参数username拼接到X-User-Name响应头中
// 真实漏洞原理：X-User-Name: <用户输入>\r\n
// 当用户输入包含\r\n时，会注入额外的响应头
// ============================================================
$xUserName = '';

if (isset($_GET['username']) && !empty($_GET['username'])) {
    $username = $_GET['username'];

    // 检测是否包含换行符
    $hasCR = strpos($username, "\r") !== false;
    $hasLF = strpos($username, "\n") !== false;

    if ($hasCR || $hasLF) {
        // 检测非法换行符格式（只允许标准的\r\n，不允许单独的\r或\n）
        $testString = str_replace("\r\n", '', $username);
        $hasIllegalChar = (strpos($testString, "\r") !== false || strpos($testString, "\n") !== false);

        if ($hasIllegalChar) {
            // 返回500错误，提示请求不符合HTTP协议规范
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html><body>';
            echo '<h1>500 Internal Server Error</h1>';
            echo '<p>请求不符合HTTP协议规范：检测到非法换行符</p>';
            echo '<p>HTTP协议要求使用 CRLF (\\r\\n) 作为换行符</p>';
            echo '<p><a href="?">返回重试</a></p>';
            echo '</body></html>';
            exit;
        }

        // 按CRLF分割用户输入，第一段是真正的username值，后续段是注入的头部
        // 模拟：X-User-Name: 第一段\r\n注入头1\r\n注入头2...
        $injectedRefresh = false;

        // 检测双CRLF（\r\n\r\n），表示头部结束，之后是响应体
        $doubleCRLFPos = strpos($username, "\r\n\r\n");

        if ($doubleCRLFPos !== false) {
            // 存在双CRLF：分离 头部注入部分 和 响应体
            $headerPart = substr($username, 0, $doubleCRLFPos);
            $bodyPart = substr($username, $doubleCRLFPos + 4);

            // 按CRLF分割头部注入部分
            $headerLines = explode("\r\n", $headerPart);

            // 第一段作为X-User-Name值（模拟拼接到响应头中）
            $headerValue = $headerLines[0];
            header('X-User-Name: ' . $headerValue, false);
            // 后续段作为注入的响应头
            for ($i = 1; $i < count($headerLines); $i++) {
                $line = trim($headerLines[$i]);
                if (empty($line)) continue;
                if (strpos($line, ':') !== false) {
                    if (preg_match('/^Refresh:\s*\d+;\s*url=https?:\/\/[^\/]*baidu\.com/i', $line)) {
                        $injectedRefresh = true;
                    }
                    header($line, false);
                }
            }

            // 如果有响应体，输出响应体（不输出原来的页面内容）
            if (!empty($bodyPart)) {
                if (!preg_match('/Content-Type:/i', $headerPart)) {
                    header('Content-Type: text/html; charset=UTF-8');
                }
                echo $bodyPart;
                exit;
            }
        } else {
            // 只有单CRLF，处理头部注入
            $lines = explode("\r\n", $username);

            // 第一段作为X-User-Name值
            $headerValue = $lines[0];
            header('X-User-Name: ' . $headerValue, false);
            // 后续段作为注入的响应头
            for ($i = 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) continue;
                if (strpos($line, ':') !== false) {
                    if (preg_match('/^Refresh:\s*\d+;\s*url=https?:\/\/[^\/]*baidu\.com/i', $line)) {
                        $injectedRefresh = true;
                    }
                    header($line, false);
                }
            }
        }

        // 更新X-User-Name的值（使用CRLF前的第一段）
        $xUserName = isset($headerValue) ? $headerValue : '';

        // 如果检测到成功注入Refresh头跳转到百度，标记heasec用户为通关
        if ($injectedRefresh) {
            $payload = $_SERVER['REQUEST_URI'];
            markHeasecUserCompleted($secret, $payload, $pdo);

            if ($isLoggedIn) {
                $stmt = $pdo->prepare('SELECT * FROM heasec_crlf_users WHERE id = ?');
                $stmt->execute([$_SESSION['crlf_user_id']]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                $passcode = $userData['passcode'];
            }
        }
    } else {
        // 正常的用户名（不含CRLF），同样设置X-User-Name
        header('X-User-Name: ' . $username, false);
        $xUserName = $username;
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">

<!-- 引入密码验证卡片组件脚本 -->
<?php require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php'; ?>
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 用户登录/信息卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <?php echo $isLoggedIn ? '欢迎，' . htmlspecialchars($userData['username']) : '用户登录'; ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <?php if (!$isLoggedIn): ?>
                <!-- 未登录状态 -->
                <div class="detection-result">
                    <div class="alert alert-info">
                        <div>
                            <i class="fa fa-info-circle"></i>
                            <strong>请构建一个请求链接，点击后会自动跳转到百度</strong>
                        </div>
                    </div>
                </div>

                <form id="loginForm" class="tech-form">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <i class="fa fa-user"></i> 用户名
                        </label>
                        <input type="text" id="username" name="username" class="tech-input"
                               placeholder="请输入用户名"
                               autocomplete="off" style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fa fa-key"></i> 密码
                        </label>
                        <input type="password" id="password" name="password" class="tech-input"
                               placeholder="请输入密码" autocomplete="off" style="width: 100%;">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-sign-in"></i> 登录
                        </button>
                    </div>
                    <div id="loginResultArea" class="result-area"></div>
                </form>

                <div style="margin-top: 15px; text-align: center; color: #888; font-size: 12px;">
                    测试账号：heasec / 123456
                </div>
            <?php else: ?>
                <!-- 已登录状态 -->
                <div class="detection-result">
                    <div class="alert alert-warning">
                        <div>
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>想一想，有哪些响应头可以实现页面跳转？在什么样的情况下才能生效？</strong>
                        </div>
                    </div>
                </div>

                <div class="tech-info-panel no-animation">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">用户名</span>
                            <span class="info-value"><?php echo htmlspecialchars($userData['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">登录状态</span>
                            <span class="info-value" style="color: #4CAF50;">已登录</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($passcode)): ?>
                    <!-- 已通关，显示通关密码 -->
                    <div class="alert alert-success" style="margin-top: 20px;">
                        <div>
                            <i class="fa fa-check-circle"></i>
                            <strong>恭喜！你已成功通关</strong>
                        </div>
                        <p class="alert-hint" style="margin-top: 10px;">
                            通关密码：<code style="font-size: 16px; font-weight: bold; color: #2e7d32;"><?php echo htmlspecialchars($passcode); ?></code>
                        </p>
                        <?php if (!empty($userData['completed_at'])): ?>
                        <p class="alert-hint" style="margin-top: 8px;">
                            <i class="fa fa-clock-o"></i> 通关时间：<?php echo htmlspecialchars($userData['completed_at']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($userData['payload'])): ?>
                        <p class="alert-hint" style="margin-top: 8px;">
                            <i class="fa fa-code"></i> 通关Payload：<br>
                            <code style="font-size: 12px; word-break: break-all; background: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 4px;"><?php echo htmlspecialchars($userData['payload']); ?></code>
                        </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- 未通关提示 -->
                    <div class="alert alert-warning" style="margin-top: 20px;">
                        <div>
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>尚未通关</strong>
                        </div>
                        <p class="alert-hint" style="margin-top: 10px;">
                            完成任务后，通关密码将显示在这里
                        </p>
                    </div>
                <?php endif; ?>

                <div class="form-actions" style="margin-top: 20px;">
                    <a href="?action=logout" class="tech-btn tech-btn-secondary">
                        <i class="fa fa-sign-out"></i> 退出登录
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 秘密验证区域 -->
    <?php
    echo renderSecretCard([
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'inputLabel' => '输入你发现的秘密',
        'inputPlaceholder' => '请输入20位的秘密字符串',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => 'CRLF注入可以导致钓鱼攻击、XSS、缓存投毒、会话固定等攻击，也可用于设置特定请求头绕过安全措施',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你理解了CRLF注入漏洞的原理和危害',
        'rangeCode' => 'crlf'
    ]);
    ?>
</div>

<!-- 交互脚本 -->
<script>
// 从PHP安全传递X-User-Name值到JS（使用json_encode防止XSS）
const heasecXUserName = <?php echo json_encode($xUserName); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // 从响应头传递的值安全填充用户名字段（通过input.value赋值天然防XSS）
    const usernameInput = document.getElementById('username');
    if (usernameInput && heasecXUserName) {
        usernameInput.value = heasecXUserName;
    }

    // 登录表单处理
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const resultArea = document.getElementById('loginResultArea');

            if (!username || !password) {
                resultArea.innerHTML = '<div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> 请输入用户名和密码</div>';
                return;
            }

            // 显示加载状态
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 登录中...';
            submitBtn.disabled = true;

            fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username: username, password: password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultArea.innerHTML = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + data.message + '</div>';
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    resultArea.innerHTML = '<div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> ' + data.message + '</div>';
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                resultArea.innerHTML = '<div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> 登录请求失败</div>';
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
