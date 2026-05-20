<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 用户覆盖 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '用户覆盖靶场';
$rangeName = '用户覆盖';
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

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('useroverride');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

/**
 * 生成随机密码
 */
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * 生成随机手机号后4位
 */
function generatePhoneSuffix() {
    return sprintf('%04d', mt_rand(0, 9999));
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 20) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 初始化用户数据
 */
function initializeUsers($pdo) {
    // 检查是否已有用户数据
    $stmt = $pdo->query("SELECT COUNT(*) FROM heasec_useroverride_users");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        return;
    }

    // 创建目标用户 wangdajie
    $stmt = $pdo->prepare("INSERT INTO heasec_useroverride_users (username, phone, password, is_admin) VALUES (?, ?, ?, 0)");
    $stmt->execute(['wangdajie', '11005911234', generateRandomPassword(10)]);

    // 创建3个干扰用户
    $interferenceUsers = ['zhangdajie', 'leidajie', 'chendajie'];
    $usedSuffixes = ['1234']; // wangdajie已使用的后缀

    foreach ($interferenceUsers as $username) {
        do {
            $suffix = generatePhoneSuffix();
        } while (in_array($suffix, $usedSuffixes));
        $usedSuffixes[] = $suffix;

        $phone = '1100591' . $suffix;
        $stmt->execute([$username, $phone, generateRandomPassword(10)]);
    }

    // 创建管理员
    $adminUsernames = ['admin', 'system', 'manager', 'root'];
    $adminUsername = $adminUsernames[array_rand($adminUsernames)];

    do {
        $adminSuffix = generatePhoneSuffix();
    } while (in_array($adminSuffix, $usedSuffixes));

    $adminPhone = '1100591' . $adminSuffix;
    $adminSecret = generateRandomString(20);

    $stmt = $pdo->prepare("INSERT INTO heasec_useroverride_users (username, phone, password, is_admin, secret) VALUES (?, ?, ?, 1, ?)");
    $stmt->execute([$adminUsername, $adminPhone, generateRandomPassword(10), $adminSecret]);
}

// 获取数据库连接并初始化用户
try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    initializeUsers($pdo);
} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Database error: ' . $e->getMessage());
}

// 检查登录状态
$isLoggedIn = isset($_SESSION['useroverride_logged_in']) && $_SESSION['useroverride_logged_in'] === true;
$currentUser = null;
$needAdminVerify = false;
$adminVerified = isset($_SESSION['useroverride_admin_verified']) && $_SESSION['useroverride_admin_verified'] === true;

if ($isLoggedIn) {
    $userId = $_SESSION['useroverride_user_id'];
    $stmt = $pdo->prepare("SELECT * FROM heasec_useroverride_users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // 检查是否是管理员且需要二次验证
    if ($currentUser && $currentUser['is_admin'] == 1 && !$adminVerified) {
        $needAdminVerify = true;
    }
}

// 获取秘密字符串（用于secret-card组件）
$secret = '';
if ($currentUser && $currentUser['is_admin'] == 1 && $adminVerified && !empty($currentUser['secret'])) {
    $secret = $currentUser['secret'];
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入密码验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 第一个卡片：用户登录卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <?php echo $isLoggedIn ? '用户信息' : '用户登录'; ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <?php if (!$isLoggedIn): ?>
            <!-- 未登录状态 -->
            <!-- 顶部提示 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small>请登录手机号为11005911234的账号</small>
                </span>
            </div>

            <!-- 登录方式切换Tab -->
            <div class="login-tabs">
                <button type="button" class="login-tab active" data-tab="username">
                    <i class="fa fa-user"></i> 用户名登录
                </button>
                <button type="button" class="login-tab" data-tab="phone">
                    <i class="fa fa-mobile"></i> 手机号登录
                </button>
            </div>

            <!-- 用户名+密码登录表单 -->
            <form id="usernameLoginForm" class="tech-form login-form active">
                <div id="usernameLoginResult" class="detection-result" style="display: none;"></div>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i> 用户名
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                </div>
                <!-- 图片验证码（错误5次后显示） -->
                <div id="usernameCaptchaGroup" class="form-group" style="display: none;">
                    <label for="usernameCaptcha" class="form-label">
                        <i class="fa fa-image"></i> 图片验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="usernameCaptcha" name="captcha" class="tech-input" placeholder="请输入验证码" maxlength="4">
                        <img id="usernameCaptchaImg" src="api/captcha.php" alt="验证码" class="captcha-img" onclick="this.src='api/captcha.php?t='+Date.now()">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary" id="registerBtn">
                        <i class="fa fa-user-plus"></i> 注册新用户
                    </button>
                </div>
            </form>

            <!-- 手机号+短信验证码登录表单 -->
            <form id="phoneLoginForm" class="tech-form login-form">
                <div id="phoneLoginResult" class="detection-result" style="display: none;"></div>
                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fa fa-mobile"></i> 手机号
                    </label>
                    <input type="text" id="phone" name="phone" class="tech-input" placeholder="请输入手机号" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="smsCode" class="form-label">
                        <i class="fa fa-shield"></i> 短信验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="smsCode" name="sms_code" class="tech-input" placeholder="请输入验证码" maxlength="6">
                        <button type="button" class="tech-btn tech-btn-orange" id="sendSmsCodeBtn">
                            <i class="fa fa-paper-plane"></i> 获取验证码
                        </button>
                    </div>
                </div>
                <!-- 图片验证码（错误5次后显示） -->
                <div id="phoneCaptchaGroup" class="form-group" style="display: none;">
                    <label for="phoneCaptcha" class="form-label">
                        <i class="fa fa-image"></i> 图片验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="phoneCaptcha" name="captcha" class="tech-input" placeholder="请输入验证码" maxlength="4">
                        <img id="phoneCaptchaImg" src="api/captcha.php" alt="验证码" class="captcha-img" onclick="this.src='api/captcha.php?t='+Date.now()">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary" id="registerBtn2">
                        <i class="fa fa-user-plus"></i> 注册新用户
                    </button>
                </div>
            </form>

            <?php else: ?>
            <!-- 已登录状态 -->
            <div class="user-info">
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-user"></i> 用户名：</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-mobile"></i> 手机号：</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-clock-o"></i> 注册时间：</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['created_at']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fa fa-star"></i> 用户角色：</span>
                    <span class="info-value"><?php echo $currentUser['is_admin'] == 1 ? '管理员' : '普通用户'; ?></span>
                </div>

                <?php if ($currentUser['is_admin'] == 1 && $adminVerified && !empty($currentUser['secret'])): ?>
                <!-- 管理员已验证，显示秘密 -->
                <div class="info-row highlight">
                    <span class="info-label"><i class="fa fa-trophy"></i> 秘密字符串：</span>
                    <span class="info-value passcode"><?php echo htmlspecialchars($currentUser['secret']); ?></span>
                </div>
                <?php elseif ($currentUser['username'] === 'wangdajie'): ?>
                <!-- 目标用户登录，显示攻击提示 -->
                <div class="alert alert-info" style="margin-top: 15px;">
                    <div>
                        <i class="fa fa-info-circle"></i>
                        <strong>恭喜！你已成功覆盖目标账号。接下来试试覆盖管理员账号？</strong>
                    </div>
                    <p class="alert-hint" style="margin-top: 8px;">
                        <small>提示：管理员手机号使用天积移动前缀110，地区编码0591</small>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="button" class="tech-btn tech-btn-danger" id="logoutBtn">
                    <i class="fa fa-sign-out"></i> 退出登录
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <br>

    <!-- 第二个卡片：秘密验证卡片 -->
    <?php
    require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
    echo renderSecretCard([
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'inputLabel' => '输入你发现的秘密',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你完成了用户覆盖攻击！',
        'successHint' => '你成功利用了用户覆盖漏洞，覆盖了目标用户的密码并获取了管理员的秘密。',
        'errorMessage' => '验证失败，这不是正确的秘密！',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功掌握了用户覆盖漏洞的利用方式',
        'rangeCode' => 'useroverride'
    ]);
    ?>
</div>

<!-- 注册模态框 -->
<div id="registerModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-user-plus"></i> 注册新用户</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body">
            <form id="registerForm" class="tech-form">
                <div id="registerResult" class="detection-result" style="display: none;"></div>
                <div class="form-group">
                    <label for="regUsername" class="form-label">
                        <i class="fa fa-user"></i> 用户名
                    </label>
                    <input type="text" id="regUsername" name="username" class="tech-input" placeholder="3-20位字母数字下划线" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="regPhone" class="form-label">
                        <i class="fa fa-mobile"></i> 手机号
                    </label>
                    <input type="text" id="regPhone" name="phone" class="tech-input" placeholder="请输入11位手机号" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="regCaptcha" class="form-label">
                        <i class="fa fa-image"></i> 图片验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="regCaptcha" name="captcha" class="tech-input" placeholder="请输入验证码" maxlength="4">
                        <img id="regCaptchaImg" src="api/captcha.php" alt="验证码" class="captcha-img" onclick="this.src='api/captcha.php?t='+Date.now()">
                    </div>
                </div>
                <div class="form-group">
                    <label for="regPassword" class="form-label">
                        <i class="fa fa-lock"></i> 密码
                    </label>
                    <input type="password" id="regPassword" name="password" class="tech-input" placeholder="请输入密码（至少6位）" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="regConfirmPassword" class="form-label">
                        <i class="fa fa-lock"></i> 确认密码
                    </label>
                    <input type="password" id="regConfirmPassword" name="confirm_password" class="tech-input" placeholder="请再次输入密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 注册
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary modal-cancel">
                        <i class="fa fa-times"></i> 取消
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 管理员二次验证模态框 -->
<div id="adminVerifyModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-shield"></i> 管理员验证</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body">
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>安全验证</strong>
                </div>
                <span class="alert-hint">
                    <small>管理员账号需要完成短信验证才能登录</small>
                </span>
            </div>
            <form id="adminVerifyForm" class="tech-form">
                <div id="adminVerifyResult" class="detection-result" style="display: none;"></div>
                <div class="form-group">
                    <label for="adminCode" class="form-label">
                        <i class="fa fa-shield"></i> 短信验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="adminCode" name="code" class="tech-input" placeholder="请输入4位验证码" maxlength="4">
                        <button type="button" class="tech-btn tech-btn-orange" id="sendAdminCodeBtn">
                            <i class="fa fa-paper-plane"></i> 获取验证码
                        </button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 验证
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary modal-cancel">
                        <i class="fa fa-times"></i> 取消
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/useroverride.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化靶场
        initUseroverride({
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            needAdminVerify: <?php echo $needAdminVerify ? 'true' : 'false'; ?>
        });
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
