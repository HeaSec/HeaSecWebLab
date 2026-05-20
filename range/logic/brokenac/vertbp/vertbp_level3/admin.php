<?php
/**
 * HeaSec天积安全团队 - 垂直越权基础靶场 - 第三关管理页面
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 垂直越权基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '垂直越权基础靶场 - 第三关';
$rangeName = '垂直越权基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/../database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 3;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('vertbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入用户初始化
require_once __DIR__ . '/../includes/user-init.php';

// 获取数据库连接并初始化用户数据
$pdo = HeaSec_Database::getConnection('heasec_logic');
initVertbpLevelUsers($currentLevel, $pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['vertbp_level3_logged_in']) && $_SESSION['vertbp_level3_logged_in'] === true;
$currentUser = isset($_SESSION['vertbp_level3_user']) ? $_SESSION['vertbp_level3_user'] : null;

// 如果未登录，重定向到登录页面
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['vertbp_level3_logged_in']);
    unset($_SESSION['vertbp_level3_user']);
    header('Location: index.php');
    exit;
}

// 从Session中读取角色（安全）
$isAdmin = isset($_SESSION['vertbp_level3_user']['role']) && $_SESSION['vertbp_level3_user']['role'] === 'admin';

// 生成模拟路由器数据
$uptime_days = rand(1, 30);
$uptime_hours = rand(0, 23);
$uptime_minutes = rand(0, 59);
$online_devices = rand(3, 8);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="../css/style.css">
<!-- 引入恭喜弹窗组件样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css?v=<?php echo $version; ?>">

<!-- 靶场主要内容 -->
<div class="tech-container">

    <!-- 顶部导航和基本信息 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-wifi"></i> HeaSec-TJRouter-X1000 - 第三关
            </h3>
            <div class="header-actions">
                <form id="logoutForm" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="tech-btn tech-btn-outline"
                        style="padding: 4px 12px; font-size: 12px; height: 100%;">
                        <i class="fa fa-sign-out"></i> 退出登录
                    </button>
                </form>
            </div>
        </div>
        <div class="tech-card-body">
            <!-- 用户角色提示 -->
            <?php if (!$isAdmin): ?>
                <div class="role-notice">
                    <i class="fa fa-info-circle"></i>
                    <span>您当前是普通用户，仅可查看路由器状态（提示：三个关卡的接口地址一致）</span>
                </div>
            <?php else: ?>
                <div class="role-notice"
                    style="background: rgba(40,167,69,0.1); border-color: rgba(40,167,69,0.3); color: #155724;">
                    <i class="fa fa-user-circle"></i>
                    <span>欢迎您，管理员！您拥有路由器的完全控制权限。</span>
                </div>
            <?php endif; ?>

            <!-- 路由器状态内容 -->
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">设备名称</div>
                    <div class="status-value">HeaSec-TJRouter-X1000</div>
                </div>
                <div class="status-item">
                    <div class="status-label">固件版本</div>
                    <div class="status-value">v2.3.1</div>
                </div>
                <div class="status-item">
                    <div class="status-label">MAC地址</div>
                    <div class="status-value">00:1A:2B:3C:4D:5E</div>
                </div>
                <div class="status-item">
                    <div class="status-label">运行时间</div>
                    <div class="status-value"><?php echo $uptime_days; ?>天 <?php echo $uptime_hours; ?>小时
                        <?php echo $uptime_minutes; ?>分钟
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">在线设备数</div>
                    <div class="status-value"><?php echo $online_devices; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">WAN状态</div>
                    <div class="status-value status-connected">已连接</div>
                </div>
                <div class="status-item">
                    <div class="status-label">LAN状态</div>
                    <div class="status-value status-connected">已连接</div>
                </div>
            </div>

            <!-- 管理员操作区域（根据Session角色显示，前端无法绕过） -->
            <div class="admin-actions <?php echo $isAdmin ? 'visible' : ''; ?>">
                <div class="admin-actions-title">
                    <i class="fa fa-cog"></i>
                    <span>管理员功能</span>
                </div>
                <button class="tech-btn tech-btn-primary"
                    onclick="window.open('edit.php', '_blank', 'width=800,height=600')">
                    <i class="fa fa-wrench"></i> 修改配置
                </button>
            </div>
        </div>
    </div>

    <br>

    <!-- 通关密码验证卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3><i class="fa fa-trophy"></i> 通关验证</h3>
        </div>
        <div class="tech-card-body">
            <form id="passcodeForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="passcode">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                </div>
                <div id="passcodeResultArea" class="result-area"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="../js/vertbp.js?v=<?php echo $version; ?>"></script>
<!-- 引入恭喜弹窗组件JS -->
<script src="<?php echo $commonBasePath; ?>components/star-system/js/heasec-congrats-modal.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initVertbp(3, '<?php echo $commonBasePath; ?>');
        bindVertbpResetButton();
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>