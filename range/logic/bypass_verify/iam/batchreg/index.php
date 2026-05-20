<?php
/**
 * HeaSec天积安全团队 - 批量注册靶场
 * Batch Registration Range
 * 版本: v1.0.0
 * 创建日期: 2026-02-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 批量注册靶场 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '批量注册靶场';
$rangeName = '批量注册';
$showVersion = false;
$showResetButton = true;
$showSmsSimulator = true;  // 显示短信模拟器按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
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
HeaSec_InitRangeSession('batchreg');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 检查登录状态
$isLoggedIn = isset($_SESSION['batchreg_logged_in']) && $_SESSION['batchreg_logged_in'] === true;
$currentUser = isset($_SESSION['batchreg_user']) ? $_SESSION['batchreg_user'] : null;

// 处理登录请求
$loginError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    try {
        $pdo = HeaSec_Database::getConnection('heasec_logic');
        $stmt = $pdo->prepare("SELECT * FROM heasec_batchreg_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $password) {
            $_SESSION['batchreg_logged_in'] = true;
            $_SESSION['batchreg_user'] = $user;
            $isLoggedIn = true;
            $currentUser = $user;
        } else {
            $loginError = '账号或密码错误';
        }
    } catch (PDOException $e) {
        $loginError = '数据库错误，请稍后重试';
    }
}

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['batchreg_logged_in']);
    unset($_SESSION['batchreg_user']);
    $isLoggedIn = false;
    $currentUser = null;
}

// 获取用户总数和检查是否解锁秘密
$userCount = 0;
$secretUnlocked = false;
$secretValue = null;

try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->query("SELECT COUNT(*) FROM heasec_batchreg_users");
    $userCount = (int) $stmt->fetchColumn();

    // 当用户数量达到1000时解锁秘密
    if ($userCount >= 1000) {
        $secretUnlocked = true;
        $secretValue = HeaSec_GetSecret(20);
    }
} catch (PDOException $e) {
    // 数据库错误，使用默认值
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入密码验证卡片组件
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入密码验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 用户登录/信息卡片 -->
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
                    <small>身为一个专业的水军，我需要1000个账号</small>
                </span>
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
                        <button type="button" class="tech-btn tech-btn-secondary" id="registerBtn">
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
                        <span class="info-label"><i class="fa fa-phone"></i> 手机号：</span>
                        <span class="info-value"><?php echo htmlspecialchars($currentUser['phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-clock-o"></i> 注册时间：</span>
                        <span class="info-value"><?php echo htmlspecialchars($currentUser['created_at']); ?></span>
                    </div>
                </div>

                <!-- 系统统计 -->
                <div class="system-stats">
                    <div class="stats-row">
                        <i class="fa fa-users"></i>
                        <span>当前系统共有 <strong><?php echo $userCount; ?></strong> 名用户</span>
                    </div>
                    <?php if ($secretUnlocked): ?>
                        <div class="secret-unlocked">
                            <i class="fa fa-trophy"></i>
                            <span>恭喜！系统用户已突破1000，解锁秘密：<strong
                                    class="secret-value"><?php echo htmlspecialchars($secretValue); ?></strong></span>
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

    <!-- 秘密验证卡片 -->
    <?php
    echo renderSecretCard([
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'secretValue' => $secretUnlocked ? $secretValue : '',
        'inputPlaceholder' => '请输入20位的秘密字符串',
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'errorMessage' => '验证失败，这不是正确的秘密！',
        'emptyMessage' => '请输入秘密',
        'enableCongrats' => true,
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你理解了批量注册攻击的原理',
        'congratsButtonText' => '继续学习',
        'rangeCode' => 'batchreg',
        'showParticles' => true
    ]);
    ?>
</div>

<!-- 注册模态框 -->
<div id="registerModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content" style="max-width: 500px;">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-user-plus"></i> 注册新用户</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body">
            <form id="registerForm" class="tech-form">
                <div class="form-group">
                    <label for="reg_username" class="form-label">
                        <i class="fa fa-user"></i> 用户名
                    </label>
                    <input type="text" id="reg_username" name="username" class="tech-input" placeholder="请输入用户名"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="reg_nickname" class="form-label">
                        <i class="fa fa-id-card"></i> 昵称
                    </label>
                    <input type="text" id="reg_nickname" name="nickname" class="tech-input" placeholder="请输入昵称"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="reg_phone" class="form-label">
                        <i class="fa fa-phone"></i> 手机号
                    </label>
                    <input type="text" id="reg_phone" name="phone" class="tech-input" placeholder="请输入手机号"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="reg_captcha" class="form-label">
                        <i class="fa fa-image"></i> 图片验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="reg_captcha" name="captcha" class="tech-input" placeholder="请输入图片验证码"
                            autocomplete="off">
                        <img id="captchaImg" src="api/captcha.php" class="captcha-img"
                            onclick="this.src='api/captcha.php?t='+Math.random()"
                            style="cursor: pointer; height: 48px; border-radius: 6px;" title="点击刷新">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_sms_code" class="form-label">
                        <i class="fa fa-comment"></i> 短信验证码
                    </label>
                    <div class="captcha-row">
                        <input type="text" id="reg_sms_code" name="sms_code" class="tech-input" placeholder="请输入短信验证码"
                            autocomplete="off">
                        <button type="button" class="tech-btn tech-btn-orange" id="sendSmsBtn">
                            <i class="fa fa-paper-plane"></i> 获取验证码
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_password" class="form-label">
                        <i class="fa fa-lock"></i> 密码
                    </label>
                    <input type="password" id="reg_password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="reg_confirm_password" class="form-label">
                        <i class="fa fa-lock"></i> 确认密码
                    </label>
                    <input type="password" id="reg_confirm_password" name="confirm_password" class="tech-input"
                        placeholder="请再次输入密码" autocomplete="off">
                </div>
                <div id="registerResultArea" class="detection-result" style="display: none;"></div>
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

<!-- 注册成功模态框 -->
<div id="registerSuccessModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content" style="max-width: 400px; text-align: center;">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-check-circle" style="color: var(--heasec-success-color);"></i> 注册成功</h3>
        </div>
        <div class="heasec-modal-body">
            <div style="padding: 20px 0;">
                <i class="fa fa-check-circle"
                    style="font-size: 64px; color: var(--heasec-success-color); margin-bottom: 20px;"></i>
                <h4 style="margin: 0 0 10px 0; color: #333;">恭喜您，注册成功！</h4>
                <p style="color: #666; margin: 0;">现在您可以使用新账号登录系统了。</p>
            </div>
            <div class="form-actions" style="justify-content: center; margin-top: 20px;">
                <button type="button" class="tech-btn tech-btn-primary" id="registerSuccessBtn"
                    style="min-width: 120px;">
                    <i class="fa fa-check"></i> 确认
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/batchreg.js?v=<?php echo $version; ?>"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>