<?php
/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场 - 第二关
 * 版本: v1.0.0
 * 功能: INSERT注入+布尔盲注 — 留言板
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec Cuosi Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL不同语句注入靶场 - 第二关';
$rangeName = 'SQL不同语句注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 自定义重置处理：重置时删除所有密码配置文件
if (isset($_GET['action']) && in_array($_GET['action'], ['reset', 'init']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $passFiles = ['secret.php', 'secret2.php', 'secret3.php'];
    foreach ($passFiles as $file) {
        $f = __DIR__ . '/config/' . $file;
        if (file_exists($f)) {
            @unlink($f);
        }
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('cuosi');

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php?order_by=id&direction=ASC';
$nextBtnText = '下一关';

// 确保当前关卡的密码已生成
ensurePasswordExists($currentLevel);

// 加载已有留言列表
$pdo = HeaSec_Database::getConnection('heasec_sqli');
$msgStmt = $pdo->query("SELECT m.id, m.content, m.created_at, u.username FROM heasec_cuosi_messages m LEFT JOIN heasec_cuosi_users u ON m.user_id = u.id ORDER BY m.created_at DESC LIMIT 20");
$messages = $msgStmt ? $msgStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：留言板 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-comments"></i>
                <span>天积社区 — 留言板</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积社区平台 — 留言板。发布您的留言，与其他社区成员交流</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用SQL注入漏洞获取通关密码。提示：发布成功/失败返回会有不同响应，尝试逐字符猜解密码，密码存储在MySQL变量@password中</small>
                </div>
            </div>

            <!-- 已有留言列表 -->
            <div id="messageListArea" class="heasec-message-list">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="heasec-message-item">
                        <div class="heasec-message-header">
                            <span class="heasec-message-user"><i class="fa fa-user"></i> <?php echo htmlspecialchars($msg['username'] ?? '匿名'); ?></span>
                            <span class="heasec-message-time"><?php echo htmlspecialchars($msg['created_at']); ?></span>
                        </div>
                        <div class="heasec-message-content"><?php echo htmlspecialchars($msg['content']); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="heasec-message-empty">暂无留言</div>
                <?php endif; ?>
            </div>

            <!-- 留言发布表单 -->
            <div class="submit-section">
                <form id="messageForm" class="query-form">
                    <div class="form-group">
                        <label for="content" class="form-label">
                            <i class="fa fa-pencil"></i> 留言内容
                        </label>
                        <textarea id="content" name="content" class="tech-input tech-textarea" placeholder="请输入留言内容" rows="3" autocomplete="off"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="messageBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-paper-plane"></i> 发布留言
                        </button>
                    </div>
                </form>
            </div>

            <!-- 发布结果区域 -->
            <div id="messageResultArea" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 卡片二：通关验证 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i>
                <span>通关验证</span>
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

<script src="js/cuosi.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCuosi(<?php echo $currentLevel; ?>, <?php echo json_encode($commonBasePath); ?>);
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
