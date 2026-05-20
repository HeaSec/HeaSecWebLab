<?php
/**
 * HeaSec天积安全团队 - SOAP与XML安全靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = 'SOAP与XML安全 - 第三关';
$rangeName = 'SOAP与XML安全';
$showVersion = false;
$showResetButton = true;
$resetUrl = 'api/reset.php';
$version = 'v1.0.0';

$commonBasePath = '../../../common/';

$currentLevel = 3;
$levelTitle = '第三关：商品查询系统 - 怎么查内部数据呢？';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once __DIR__ . '/includes/functions.php';

$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

ensureDataFile($currentLevel);

// 确保SSRF token已生成
ensureSsrfToken();

// 动态生成内部API地址
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$internalApiUrl = $scheme . '://' . $_SERVER['HTTP_HOST']
    . dirname($_SERVER['SCRIPT_NAME']) . '/lcapi/internal.php';

require_once $commonBasePath . 'includes/header.php';

require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-shield"></i>
                <span id="mainCardTitle"><?php echo htmlspecialchars($levelTitle); ?></span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积云商品查询系统，无需登录即可使用。内部管理API地址：<?php echo htmlspecialchars($internalApiUrl); ?>（仅允许服务器本地访问）</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务目标</strong>
                </div>
                <span><small>通过SSRF攻击访问内部API获取通关密码。提示：虽然是SOAP协议，但你依然可以在报文前加入DOCTYPE声明，实现外部实体注入。</small></span>
            </div>

            <!-- 商品搜索表单 -->
            <form id="searchForm" class="tech-form">
                <div class="form-group">
                    <label for="search-keyword" class="form-label">
                        <i class="fa fa-search"></i> 关键词
                    </label>
                    <input type="text" id="search-keyword" name="keyword" class="tech-input" placeholder="请输入搜索关键词" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-search"></i> 搜索
                    </button>
                </div>
            </form>

            <!-- 操作结果区域 -->
            <div id="resultArea" style="display: none;"></div>

            <!-- 搜索结果区域 -->
            <div id="searchResults" class="search-results" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
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
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script src="js/soapxml.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initSoapXmlRange(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
