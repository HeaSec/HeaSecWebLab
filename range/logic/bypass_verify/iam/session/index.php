<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '会话安全靶场 - 第一关';
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
$currentLevel = 1;
$levelTitle = '第一关：会话不死，只是凋零';
$taskHint = '只有经历过退出的会话，才能看到通关密码';
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件（仅用于获取函数定义）
require_once $commonBasePath . 'includes/session_manager.php';

// 引入公共函数（自定义会话初始化）
require_once 'includes/functions.php';

// 初始化靶场会话
initRangeSession($currentLevel);

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 确保通关密码已生成
getOrCreatePasscode($currentLevel, $pdo);

// 检查是否已登录
$isLoggedIn = false;
$userData = null;
$passcode = null;

$userId = isset($_SESSION['session_user_id_level1']) ? $_SESSION['session_user_id_level1'] : null;
$loggedIn = isset($_SESSION['session_logged_in_level1']) && $_SESSION['session_logged_in_level1'] === true;
$logoutMarked = isset($_SESSION['session_logout_marked_level1']) && $_SESSION['session_logout_marked_level1'] === true;

if ($userId && $loggedIn) {
    $user = getUserById($userId, $currentLevel, $pdo);
    if ($user) {
        $isLoggedIn = true;
        $userData = [
            'username' => $user['username'],
            'realname' => $user['realname']
        ];

        // 如果会话有退出标记，显示通关密码
        if ($logoutMarked) {
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
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off">
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
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn" class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
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
