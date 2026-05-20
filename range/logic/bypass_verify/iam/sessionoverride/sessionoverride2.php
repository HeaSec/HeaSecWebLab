<?php
/**
 * HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置会话覆盖 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '密码重置会话覆盖靶场';
$rangeName = '密码重置会话覆盖';
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
$currentLevel = 2;
$levelTitle = '第二关：验证状态未正确重置';
$nextPage = 'index.php';
$nextBtnText = '返回第一关';
$isFinalLevel = true;


// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('sessionoverride');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入星级系统组件（用于恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 检查并创建admin账号
function getOrCreateAdminUser($level, $pdo)
{
    $stmt = $pdo->prepare("SELECT * FROM heasec_sessionoverride_users WHERE level = ? AND username = 'admin'");
    $stmt->execute([$level]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row;
    }

    $password = generateRandomPassword(10);
    $userId = generateUserId();
    $phone = '11066668888';

    $stmt = $pdo->prepare("INSERT INTO heasec_sessionoverride_users (level, username, password, user_id, phone, is_admin) VALUES (?, 'admin', ?, ?, ?, 1)");
    $stmt->execute([$level, $password, $userId, $phone]);

    return [
        'username' => 'admin',
        'password' => $password,
        'user_id' => $userId,
        'phone' => $phone
    ];
}

function generateRandomPassword($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function generateUserId()
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $userId = '';
    for ($i = 0; $i < 8; $i++) {
        $userId .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $userId;
}

function generatePasscode($level)
{
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
$isLoggedIn = isset($_SESSION['sessionoverride_level2_logged_in']) && $_SESSION['sessionoverride_level2_logged_in'] === true;
$currentUser = isset($_SESSION['sessionoverride_level2_user']) ? $_SESSION['sessionoverride_level2_user'] : null;

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $stmt = $pdo->prepare("SELECT * FROM heasec_sessionoverride_users WHERE level = ? AND username = ?");
    $stmt->execute([$currentLevel, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
        $_SESSION['sessionoverride_level2_logged_in'] = true;
        $_SESSION['sessionoverride_level2_user'] = $user;
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
    unset($_SESSION['sessionoverride_level2_logged_in']);
    unset($_SESSION['sessionoverride_level2_user']);
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
<!-- 引入恭喜弹窗组件样式 -->
<?php echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]); ?>

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
                    <small>尝试通过密码重置功能重置admin账号密码登录admin账号获取通关密码（测试账号test/123456）</small>
                </span>
            </div>
            <div class="alert-dev-tip">
                <i class="fa fa-lightbulb-o"></i>
                <span>开发人员ps：这次我分了两步，先验证手机再重置密码，万无一失</span>
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
                        <input type="text" id="username" name="username" class="tech-input" placeholder="请输入账号"
                            autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                            autocomplete="off">
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
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn"
                        class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 忘记密码模态框（两步式） -->
<div id="forgotPasswordModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-key"></i> 重置密码</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body">
            <!-- 第一步：手机验证 -->
            <form id="verifyPhoneForm" class="step-form" data-step="1">
                <div class="step-indicator">
                    <span class="step active">1. 手机验证</span>
                    <span class="step">2. 重置密码</span>
                </div>
                <div class="form-group">
                    <label for="verify_username" class="form-label">
                        <i class="fa fa-user"></i> 账号
                    </label>
                    <input type="text" id="verify_username" name="username" class="tech-input" placeholder="请输入账号"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="verify_phone" class="form-label">
                        <i class="fa fa-phone"></i> 手机号
                    </label>
                    <input type="text" id="verify_phone" name="phone" class="tech-input" placeholder="请输入手机号"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="verify_captcha" class="form-label">
                        <i class="fa fa-shield"></i> 验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="verify_captcha" name="captcha" class="tech-input" placeholder="请输入验证码"
                            autocomplete="off" maxlength="6">
                        <button type="button" class="tech-btn tech-btn-orange" id="sendCodeBtn">
                            <i class="fa fa-paper-plane"></i> 获取验证码
                        </button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-arrow-right"></i> 下一步
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary modal-cancel">取消</button>
                </div>
                <div id="verifyPhoneResultArea" class="detection-result" style="display: none;"></div>
            </form>

            <!-- 第二步：重置密码 -->
            <form id="resetPasswordForm" class="step-form" data-step="2" style="display: none;">
                <div class="step-indicator">
                    <span class="step completed">1. 手机验证</span>
                    <span class="step active">2. 重置密码</span>
                </div>
                <div class="form-group">
                    <label for="reset_password" class="form-label">
                        <i class="fa fa-lock"></i> 新密码
                    </label>
                    <input type="password" id="reset_password" name="password" class="tech-input" placeholder="请输入新密码"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="reset_confirm_password" class="form-label">
                        <i class="fa fa-lock"></i> 确认密码
                    </label>
                    <input type="password" id="reset_confirm_password" name="confirm_password" class="tech-input"
                        placeholder="请再次输入新密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary" id="backToStep1Btn">
                        <i class="fa fa-arrow-left"></i> 返回上一步
                    </button>
                </div>
                <div id="resetPasswordResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>


<!-- 引入交互脚本 -->
<script src="js/sessionoverride.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第二关
    document.addEventListener('DOMContentLoaded', function () {
        initSessionOverride(<?php echo $currentLevel; ?>, true, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>