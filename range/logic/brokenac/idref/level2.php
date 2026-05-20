<?php
/**
 * HeaSec天积安全团队 - 水平越权基础靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 水平越权基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '水平越权基础靶场 - 第二关';
$rangeName = '水平越权基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 2;
$levelTitle = '第二关：手机号Base64编码';
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('idref');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入用户初始化
require_once __DIR__ . '/includes/user-init.php';

// 获取数据库连接并初始化用户数据
$pdo = HeaSec_Database::getConnection('heasec_logic');
initLevelUsers($currentLevel, $pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['idref_level2_logged_in']) && $_SESSION['idref_level2_logged_in'] === true;
$currentUser = isset($_SESSION['idref_level2_user']) ? $_SESSION['idref_level2_user'] : null;

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['idref_level2_logged_in']);
    unset($_SESSION['idref_level2_user']);
    $isLoggedIn = false;
    $currentUser = null;
}

// 准备用户标识数据（用于JS调用profile API）
$userIdentifiers = '';
if ($isLoggedIn && $currentUser) {
    $userIdentifiers = json_encode([
        'num_id' => isset($currentUser['num_id']) ? $currentUser['num_id'] : '',
        'phone' => $currentUser['phone'],
        'user_id' => isset($currentUser['user_id']) ? $currentUser['user_id'] : ''
    ]);
}
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
                    <small>管理员使用的是天积移动号段：1380591XXXX</small>
                </span>
            </div>

            <!-- 登录表单（始终存在于DOM中，通过CSS控制显示） -->
            <div id="loginSection" style="display: <?php echo !$isLoggedIn ? 'block' : 'none'; ?>;">
                <form id="loginForm" class="tech-form">
                    <div class="form-group">
                        <label for="account" class="form-label">
                            <i class="fa fa-user"></i> 账号
                        </label>
                        <input type="text" id="account" name="account" class="tech-input" placeholder="请输入账号" autocomplete="off">
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
                    </div>
                    <div id="loginResultArea" class="detection-result" style="display: none;"></div>
                    <!-- 测试账号提示 -->
                    <div class="test-account-hint" style="text-align: center; margin-top: 15px; color: #888; font-size: 13px;">
                        <small>测试账号：test / 123456</small>
                    </div>
                </form>
            </div>

            <!-- 已登录状态（始终存在于DOM中，通过CSS控制显示） -->
            <div id="userInfoSection" style="display: <?php echo $isLoggedIn ? 'block' : 'none'; ?>;">
                <div id="userInfoContainer" data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>" data-user-identifiers="<?php echo htmlspecialchars($userIdentifiers); ?>">
                    <div id="userInfoLoading" style="text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin"></i> 加载用户信息...
                    </div>
                    <div id="userInfoDisplay" style="display: none;"></div>
                </div>
                <div class="form-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="tech-btn tech-btn-danger">
                            <i class="fa fa-sign-out"></i> 退出登录
                        </button>
                    </form>
                </div>
            </div>
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

<!-- 引入交互脚本 -->
<script src="js/idref.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第二关
    document.addEventListener('DOMContentLoaded', function () {
        initIdref(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
