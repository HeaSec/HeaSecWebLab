<?php
/**
 * HeaSec天积安全团队 - 回显型命令注入靶场 - 第二关（字符黑名单过滤绕过）
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec EchoRCE Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = '回显型命令注入 - 第二关';
$rangeName = '回显型命令注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

$commonBasePath = '../../../common/';

$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('echo_rce');

require_once $commonBasePath . 'includes/header.php';

require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

require_once 'includes/functions.php';
initEchoRceSession();
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-network-wired"></i>
                <span>天积网络诊断工具</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统的网络诊断工具支持输入IP地址进行连通性检测，系统会调用ping命令对目标地址进行探测并返回结果。注意：系统已增加安全检测机制</span>
            </div>

            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    绕过安全过滤执行系统命令 <code>dir</code>（Windows）或 <code>ls</code>（Linux）。提示：上一关的方法可能不再奏效了
                </div>
            </div>

            <div class="input-section">
                <div class="input-group">
                    <input type="text" id="ipInput" class="tech-input" placeholder="请输入目标IP地址，如 127.0.0.1" autocomplete="off">
                    <button type="button" id="pingBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-play-circle"></i> 开始诊断
                    </button>
                </div>
            </div>

            <div id="outputArea" style="display: none;"></div>

            <div id="levelStatusArea" style="display: none;"></div>
        </div>
    </div>
</div>

<script src="js/echo_rce.js?v=<?php echo $version; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initEchoRCE(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
});
</script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>
