<?php
/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置凭证可猜测 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '密码重置凭证可猜测靶场';
$rangeName = '密码重置凭证可猜测';
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
$currentLevel = 3;
$levelTitle = '第三关：密码重置凭证可猜测（SHA256+时间戳）';
$nextPage = 'index.php';
$nextBtnText = '返回第一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetlink');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入星星系统组件（用于恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 检查并创建admin账号
function getOrCreateAdminUser($level, $pdo)
{
    $stmt = $pdo->prepare("SELECT * FROM heasec_resetlink_users WHERE level = ? AND username = 'admin'");
    $stmt->execute([$level]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row;
    }

    $password = generateRandomPassword(10);
    $userId = generateUserId();
    $phone = '11066668888';

    $stmt = $pdo->prepare("INSERT INTO heasec_resetlink_users (level, username, password, user_id, phone, is_admin) VALUES (?, 'admin', ?, ?, ?, 1)");
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

// 获取数据库连接并创建admin账号
$pdo = HeaSec_Database::getConnection('heasec_logic');
$adminUser = getOrCreateAdminUser($currentLevel, $pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['resetlink_level3_logged_in']) && $_SESSION['resetlink_level3_logged_in'] === true;
$currentUser = isset($_SESSION['resetlink_level3_user']) ? $_SESSION['resetlink_level3_user'] : null;

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $stmt = $pdo->prepare("SELECT * FROM heasec_resetlink_users WHERE level = ? AND username = ?");
    $stmt->execute([$currentLevel, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
        $_SESSION['resetlink_level3_logged_in'] = true;
        $_SESSION['resetlink_level3_user'] = $user;
        $isLoggedIn = true;
        $currentUser = $user;

        if ($user['is_admin'] == 1) {
            generatePasscode($currentLevel);
        }

        // 检查是否已添加好友
        if ($user['friend_added'] == 1 && !empty($user['friend_username'])) {
            $stmt = $pdo->prepare("SELECT username, user_id, phone FROM heasec_resetlink_users WHERE level = ? AND username = ?");
            $stmt->execute([$currentLevel, $user['friend_username']]);
            $friendUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($friendUser) {
                $_SESSION['resetlink_level3_user']['friend_info'] = $friendUser;
                // 同步更新$currentUser变量
                $currentUser = $_SESSION['resetlink_level3_user'];
            }
        }
    } else {
        $loginError = '账号或密码错误';
    }
}

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['resetlink_level3_logged_in']);
    unset($_SESSION['resetlink_level3_user']);
    $isLoggedIn = false;
    $currentUser = null;
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
                    <small>尝试通过密码重置功能重置admin账号密码登录admin账号获取通关密码（测试账号test/123456）</small>
                </span>
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(245, 158, 11, 0.2);">
                    <small style="color: #f59e0b;">开发人员ps：他们说不能用固定的token，我把账号、手机号和时间戳放在一起那啥了256下，这下每次都不一样了吧</small>
                    <!-- 提示：那啥……请问啥的拼音是什么？ -->
                </div>
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
                    <?php if ($currentUser['is_admin'] != 1): ?>
                        <button type="button" class="tech-btn tech-btn-secondary" id="addFriendBtn">
                            <i class="fa fa-user-plus"></i> 添加好友
                        </button>
                    <?php endif; ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="tech-btn tech-btn-danger">
                            <i class="fa fa-sign-out"></i> 退出登录
                        </button>
                    </form>
                </div>

                <!-- 添加好友按钮区域 -->
                <?php
                $friendInfoJson = '';
                $hasFriend = false;
                if (isset($currentUser['friend_info']) && $currentUser['friend_info']) {
                    $friendInfoJson = json_encode($currentUser['friend_info']);
                    $hasFriend = true;
                }
                ?>
                <?php if ($currentUser['is_admin'] != 1 && $hasFriend): ?>
                    <div id="addFriendSection" style="margin-top: 20px;"
                        data-friend-info="<?php echo htmlspecialchars($friendInfoJson); ?>">
                        <hr class="form-divider">
                        <h4><i class="fa fa-user-plus"></i> 好友信息</h4>
                        <!-- 好友信息展示区域 -->
                        <div id="friendInfoDisplay" style="display: none;">
                            <div class="friend-result">
                                <div class="info-row">
                                    <span class="info-label">账号：</span>
                                    <span class="info-value" id="friendUsernameDisplay"></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">用户ID：</span>
                                    <span class="info-value" id="friendUserIdDisplay"></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">手机号：</span>
                                    <span class="info-value" id="friendPhoneDisplay"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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

<!-- 忘记密码模态框 -->
<div id="forgotPasswordModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-key"></i> 重置密码</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body">
            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label for="reset_username" class="form-label">
                        <i class="fa fa-user"></i> 账号
                    </label>
                    <input type="text" id="reset_username" name="username" class="tech-input" placeholder="请输入需要重置密码的账号"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-paper-plane"></i> 发送重置链接
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary modal-cancel">取消</button>
                </div>
                <div id="resetResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 添加好友模态框 -->
<div id="addFriendModal" class="heasec-modal" style="display: none;">
    <div class="heasec-modal-content">
        <div class="heasec-modal-header">
            <h3><i class="fa fa-user-plus"></i> 添加好友</h3>
            <button type="button" class="heasec-modal-close">&times;</button>
        </div>
        <div class="heasec-modal-body">
            <form id="addFriendForm">
                <div class="form-group">
                    <label for="friend_username" class="form-label">
                        <i class="fa fa-user"></i> 好友账号
                    </label>
                    <input type="text" id="friend_username" name="username" class="tech-input" placeholder="请输入好友账号"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-search"></i> 搜索
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary modal-cancel">取消</button>
                </div>
                <div id="addFriendResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入星星系统组件资源（包含恭喜弹窗） -->
<?php
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<!-- 引入交互脚本 -->
<script src="js/resetlink.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第三关
    document.addEventListener('DOMContentLoaded', function () {
        initResetlink(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>