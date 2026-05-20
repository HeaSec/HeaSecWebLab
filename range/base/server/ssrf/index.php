<?php
/**
 * HeaSec天积安全团队 - SSRF漏洞靶场主页面
 * 版本: v1.0.0
 * 创建日期: 2026-04-06
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 模拟"天积识图"系统，展示SSRF漏洞的多种利用方式
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec SSRF漏洞 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'SSRF漏洞靶场';
$rangeName = 'SSRF漏洞';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('ssrf');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入靶场公共函数
require_once __DIR__ . '/includes/functions.php';

// 获取数据库连接并初始化数据
try {
    $pdo = HeaSec_Database::getConnection('heasec_base');

    // 获取或创建当前会话的进度
    $sessionId = session_id();
    $progress = getOrCreateProgress($pdo, $sessionId);
    $currentStep = (int)$progress['current_step'];

    // 获取秘密字符串
    $secret = getOrCreateSecret($pdo, $sessionId);

    // 获取步骤提示信息
    $rangeBasePath = __DIR__;
    $metadataUrl = generateMetadataUrl();
    list($taskText, $hintText) = getStepHints($progress, $rangeBasePath, $metadataUrl);

} catch (Exception $e) {
    $currentStep = 1;
    $secret = HeaSec_GetSecret(20);
    $taskText = '探索系统的SSRF漏洞，完成一系列挑战获取秘密';
    $hintText = '听说内网有一个元数据接口，也许能发现一些有趣的信息？';
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">
<!-- 引入秘密验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 第一个卡片：天积识图卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-eye"></i>
                天积识图 - AI图片识别
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 功能说明区域 -->
            <div class="alert alert-info">
                <div>
                    <i class="fa fa-info-circle"></i>
                    <strong>天积识图系统</strong>
                </div>
                <span class="alert-hint">
                    <small>输入图片URL，我们的AI将为您智能识别图片内容</small>
                </span>
            </div>

            <!-- 任务引导区域 -->
            <div class="step-guidance">
                <div class="alert alert-warning">
                    <div>
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>任务</strong>
                    </div>
                    <span class="alert-hint" id="stepTask">
                        <small><?php echo htmlspecialchars($taskText); ?></small>
                    </span>
                </div>

                <div class="alert alert-info" id="hintCard" style="margin-top: 10px; <?php echo empty($hintText) ? 'display:none;' : ''; ?>">
                    <div>
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>提示</strong>
                    </div>
                    <span class="alert-hint" id="stepHint">
                        <small><?php echo $hintText; ?></small>
                    </span>
                </div>
            </div>

            <!-- URL输入区域 -->
            <form id="ssrfFetchForm" class="tech-form" style="margin-top: 20px;">
                <div class="url-input-group">
                    <input type="text" id="ssrfUrl" class="tech-input"
                           placeholder="输入图片URL，如 https://example.com/image.png"
                           autocomplete="off">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-search"></i> 识别图片
                    </button>
                </div>
            </form>

            <!-- 结果展示区域 -->
            <div id="ssrfResultArea" class="result-area">
                <div class="result-placeholder">
                    <i class="fa fa-image"></i>
                    <p>试试输入一个图片URL开始体验</p>
                </div>
            </div>

            <!-- 请求历史区域 -->
            <div class="history-section">
                <div class="history-toggle" id="historyToggle">
                    <i class="fa fa-history"></i>
                    <span>请求历史</span>
                    <i class="fa fa-chevron-right toggle-icon"></i>
                </div>
                <div id="historyList" class="history-list">
                    <div style="text-align:center; color:#adb5bd; padding:10px; font-size:12px;">暂无请求记录</div>
                </div>
            </div>
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
        'inputPlaceholder' => '请输入20位的秘密字符串',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你完成了SSRF漏洞利用！',
        'successHint' => '你成功利用了SSRF漏洞的多种协议攻击方式，完成了四步攻击链。',
        'errorMessage' => '验证失败，这不是正确的秘密！',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功掌握了SSRF漏洞的多协议利用方式，包括内网访问、文件读取、端口探测和Redis未授权访问',
        'rangeCode' => 'ssrf'
    ]);
    ?>
</div>

<!-- 引入交互脚本 -->
<script src="js/ssrf.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initSsrfRange({
            currentStep: <?php echo $currentStep; ?>
        });
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
