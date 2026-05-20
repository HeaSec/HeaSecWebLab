<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT密钥注入 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JWT密钥注入靶场';
$rangeName = 'JWT密钥注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 数据库初始化
try {
    require_once $commonBasePath . 'includes/database.php';
    $db = heasec_db('heasec_logic');
    // 测试连接
    $db->query('SELECT 1');

    // 引入配置和初始化用户
    require_once 'includes/config.php';
    require_once 'includes/init_users.php';
    require_once 'includes/jwt.php';

    // 初始化用户账号
    initializeUsers();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 引入星星系统组件资源 -->
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['css' => true, 'js' => false, 'congrats' => false]);
?>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 登录卡片 / 用户信息卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <span id="cardTitle">用户登录</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div id="taskHint" class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small>尝试登录admin用户账号</small>
                </span>
            </div>

            <!-- 登录错误提示 -->
            <div id="loginErrorArea" class="alert alert-error" style="display: none;">
                <i class="fa fa-times-circle"></i>
                <span id="loginErrorMsg"></span>
            </div>

            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fa fa-user"></i>
                        用户名
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa fa-lock"></i>
                        密码
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                            required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i>
                        登录
                    </button>
                </div>
            </form>

            <!-- 用户信息区域（登录后显示） -->
            <div id="userInfoArea" style="display: none;">
                <div class="user-info-panel">
                    <div class="user-info-row">
                        <span class="user-info-label">用户名：</span>
                        <span class="user-info-value" id="displayUsername"></span>
                    </div>
                    <div class="user-info-row">
                        <span class="user-info-label">角色：</span>
                        <span class="user-info-value" id="displayRole"></span>
                    </div>
                </div>

                <!-- 成就通知 -->
                <div id="achievementNotification" class="alert alert-success" style="display: none; margin-top: 15px;">
                </div>

                <!-- 提示信息（test用户显示） -->
                <div id="userHintArea" class="hint-panel" style="display: none;">
                    <i class="fa fa-lightbulb-o"></i>
                    <span>你当前是以普通用户身份登录，尝试获取管理员权限</span>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="button" id="logoutBtn" class="tech-btn tech-btn-secondary">
                        <i class="fa fa-sign-out"></i>
                        退出登录
                    </button>
                </div>
            </div>

            <!-- 文件上传区域（登录后显示） -->
            <div id="uploadArea" class="upload-section" style="display: none;">
                <hr style="margin: 20px 0; border-color: rgba(0,0,0,0.1);">
                <h4 style="margin-bottom: 15px;">
                    <i class="fa fa-upload"></i>
                    文件上传
                </h4>
                <form id="uploadForm">
                    <div class="file-input-wrapper">
                        <input type="file" id="fileInput" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.txt,.pdf,.doc,.docx">
                        <div class="file-input-display">
                            <div>
                                <i class="fa fa-file-text-o"></i>
                                <span class="file-name" id="fileNameDisplay">选择文件...</span>
                            </div>
                            <i class="fa fa-chevron-right" style="color: #999;"></i>
                        </div>
                    </div>
                    <p style="font-size: 12px; color: #999; margin: 8px 0;">
                        只允许上传图片和文档文件（.jpg、.png、.gif、.txt、.pdf等），最大10KB
                    </p>
                    <div class="form-actions" style="margin-top: 10px;">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-cloud-upload"></i>
                            上传文件
                        </button>
                    </div>
                </form>

                <!-- 已上传文件列表 -->
                <div class="upload-list" style="margin-top: 15px;">
                    <h5 style="font-size: 13px; color: #666; margin-bottom: 10px;">已上传文件：</h5>
                    <div id="uploadedList">
                        <div class="empty-state">
                            <i class="fa fa-inbox"></i>
                            <p>暂无上传文件</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 测试账号提示 -->
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1);">
                <p style="font-size: 12px; color: #999; text-align: center;">
                    测试账号：test / 123456
                </p>
            </div>
        </div>
    </div>

    <!-- 成就系统卡片 -->
    <?php
    // 引入成就卡片公共组件
    require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';

    // 获取成就数量和记录
    $starCount = 0;
    $records = [];
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_jwtkey_records");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $starCount = intval($result['count']);

        // 获取记录列表
        $stmt = $db->query("SELECT attack_type, success_count, last_success_at FROM heasec_jwtkey_records ORDER BY last_success_at DESC");
        $dbRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 转换记录格式
        foreach ($dbRecords as $record) {
            $typeName = getAttackTypeName($record['attack_type']);
            $records[] = [
                'name' => $typeName,
                'count' => $record['success_count'],
                'time' => $record['last_success_at']
            ];
        }
    } catch (Exception $e) {
        error_log('[HeaSec] Database error: ' . $e->getMessage());
        $starCount = 0;
        $records = [];
    }

    // 渲染成就卡片公共组件
    echo renderAchievementCard([
        'achievedCount' => $starCount,
        'customRecords' => $records,
        'recordsTitle' => '已解锁的漏洞利用方式',
        'rangeCode' => 'jwtkey',

        // 恭喜功能配置
        'congratsConfig' => [
            'messages' => [
                'partial' => '你已经掌握了 %d/3 种JWT秘钥注入方式！继续探索，发现更多漏洞！',
                'complete' => '太棒了！你已经掌握了3种JWT秘钥注入方式，成为了真正的JWT安全大师！'
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<!-- 引入前端脚本 -->
<script src="js/jwtkey.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化靶场
    document.addEventListener('DOMContentLoaded', function () {
        initJwtKey('<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>