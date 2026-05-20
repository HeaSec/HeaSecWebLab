<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-02-04
 * 团队: 天积安全 (HeavenlySecret)

 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '密码重置流程绕过靶场';
$rangeName = '密码重置流程绕过';
$showVersion = false;
$showResetButton = true;
$showSmsSimulator = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 1;
$levelTitle = '第一关：密码重置步骤链接可猜测';
$nextPage = 'resetstepbp2.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 检查并创建admin账号
function getOrCreateAdminUser($level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_resetstepbp_users WHERE level = ? AND username = 'admin'");
    $stmt->execute([$level]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row;
    }

    $password = generateRandomPassword(10);
    $userId = generateUserId();
    $phone = '11055557777'; // admin账号的手机号（使用保留号段，短信模拟器无法查看）

    $stmt = $pdo->prepare("INSERT INTO heasec_resetstepbp_users (level, username, password, user_id, phone, is_admin) VALUES (?, 'admin', ?, ?, ?, 1)");
    $stmt->execute([$level, $password, $userId, $phone]);

    return [
        'username' => 'admin',
        'password' => $password,
        'user_id' => $userId,
        'phone' => $phone
    ];
}

function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function generateUserId() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $userId = '';
    for ($i = 0; $i < 8; $i++) {
        $userId .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $userId;
}

function generatePasscode($level) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    $_SESSION['passcode_level' . $level] = $passcode;

    return $passcode;
}

// 获取数据库连接并创建admin账号
$pdo = HeaSec_Database::getConnection('heasec_logic');
$adminUser = getOrCreateAdminUser($currentLevel, $pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['resetstepbp_level1_logged_in']) && $_SESSION['resetstepbp_level1_logged_in'] === true;
$currentUser = isset($_SESSION['resetstepbp_level1_user']) ? $_SESSION['resetstepbp_level1_user'] : null;

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $stmt = $pdo->prepare("SELECT * FROM heasec_resetstepbp_users WHERE level = ? AND username = ?");
    $stmt->execute([$currentLevel, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
        $_SESSION['resetstepbp_level1_logged_in'] = true;
        $_SESSION['resetstepbp_level1_user'] = $user;
        $isLoggedIn = true;
        $currentUser = $user;

        if ($user['is_admin'] == 1) {
            generatePasscode($currentLevel);
        }
    } else {
        $loginError = '账号或密码错误';
    }
}

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['resetstepbp_level1_logged_in']);
    unset($_SESSION['resetstepbp_level1_user']);
    $isLoggedIn = false;
    $currentUser = null;
}

// 获取通关密码
$passcode = isset($_SESSION['passcode_level' . $currentLevel]) ? $_SESSION['passcode_level' . $currentLevel] : '';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <?php echo $isLoggedIn ? '用户信息' : '用户登录'; ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small>尝试通过密码重置功能重置admin账号密码登录admin账号获取通关密码</small>
                </span>
            </div>
            <div class="alert-dev-tip">
                <i class="fa fa-lightbulb-o"></i>
                <span>开发人员ps：密码重置分成三步，每一步都有验证，安全！</span>
            </div>

            <?php if (!$isLoggedIn): ?>
            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form" method="POST">
                <input type="hidden" name="action" value="login">
                <?php if (isset($loginError)): ?>
                <div class="alert-error" style="margin-bottom: 15px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($loginError); ?></span>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i> 账号
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入账号" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary" id="forgotPasswordBtn">
                        <i class="fa fa-key"></i> 忘记密码
                    </button>
                </div>
            </form>
            <?php else: ?>
            <!-- 已登录状态 -->
            <div class="user-info">
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-user"></i> 账号：</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-id-card"></i> 用户ID：</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['user_id']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-phone"></i> 手机号：</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['phone']); ?></span>
                </div>
                <?php if ($currentUser['is_admin'] == 1 && $passcode): ?>
                <div class="info-row highlight">
                    <span class="info-label"><i class="fa fa-trophy"></i> 通关密码：</span>
                    <span class="info-value passcode"><?php echo htmlspecialchars($passcode); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="tech-btn tech-btn-danger">
                        <i class="fa fa-sign-out"></i> 退出登录
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <br>

    <!-- 通关密码验证卡片 -->
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
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn" class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 忘记密码模态框（iframe方式） -->
<div id="forgotPasswordModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content" style="max-width: 500px;">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-key"></i> 重置密码</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body" style="padding: 0;">
            <iframe id="resetFrame" class="reset-iframe" src="about:blank" style="width: 100%; min-height: 400px; border: none;"></iframe>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/resetstepbp.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第一关
    document.addEventListener('DOMContentLoaded', function () {
        initResetstepbp(<?php echo $currentLevel; ?>);
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
