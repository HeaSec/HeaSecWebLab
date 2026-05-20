<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第一关管理页面 (admin.php)
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 此文件是第一关的管理页面之一
 * 只有当数据库中random_path为'admin.php'时才能访问
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 未授权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 当前文件名
$currentFileName = basename(__FILE__);
$currentLevel = 1;

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入必要组件
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/includes/config-init.php';
require_once __DIR__ . '/includes/access-control.php';

// 初始化靶场会话
HeaSec_InitRangeSession('noauth');

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 检查路径是否为当前关卡的随机路径
$config = checkNoauthAccess($currentLevel, $currentFileName, $pdo);

if (!$config) {
    // 路径不匹配，显示404
    showNoauth404($commonBasePath);
}

// 生成路由器数据
$routerData = generateRouterData();

// 设置页面变量
$pageTitle = 'HeaSec Router - 管理面板';
$rangeName = '未授权访问';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 下一关信息
$nextPage = 'noauth_level2/index.php';
$nextBtnText = '第二关';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">

    <!-- 管理面板卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-wifi"></i> HeaSec Router - 管理面板 (第一关)
            </h3>
            <div class="header-actions">
                <a href="index.php?action=logout" class="tech-btn tech-btn-outline"
                    style="padding: 4px 12px; font-size: 12px; height: 100%;">
                    <i class="fa fa-sign-out"></i> 退出登录
                </a>
            </div>
        </div>
        <div class="tech-card-body">
            <!-- 状态指示器 -->
            <div class="status-indicator" style="margin-bottom: 20px;">
                <span class="dot connected"></span>
                <span style="color: #28a745;">系统运行正常</span>
            </div>

            <!-- 路由器状态内容 -->
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">设备名称</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['device_name']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">固件版本</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['firmware_version']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">MAC地址</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['mac_address']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">运行时间</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['uptime']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">在线设备数</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['online_devices']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">WAN状态</div>
                    <div class="status-value status-connected">
                        <?php echo htmlspecialchars($routerData['wan_status']); ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">LAN状态</div>
                    <div class="status-value status-connected">
                        <?php echo htmlspecialchars($routerData['lan_status']); ?>
                    </div>
                </div>
            </div>

            <!-- 配置信息区域（包含通关密码） -->
            <div class="config-section">
                <h4><i class="fa fa-key"></i> 系统配置</h4>
                <div class="config-item highlight">
                    <span class="config-label"><i class="fa fa-lock"></i> 通关密码</span>
                    <span class="config-value"><?php echo htmlspecialchars($config['passcode']); ?></span>
                </div>
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
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn"
                        class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="passcodeResultArea" class="result-area"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/noauth.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initNoauth(1, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>