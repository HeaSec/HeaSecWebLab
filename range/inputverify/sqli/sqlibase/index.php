<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec SQL注入练习靶场 v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = 'SQL注入练习';
$rangeName = 'SQL注入练习';
$showResetButton = true;
$version = 'v1.0.0';
$commonBasePath = '../../../common/';
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_sqlinject';
$useDatabase = true;
$resetUrl = 'api/reset.php';

define('HEASEC_RANGE_ACCESS', true);
require_once 'includes/bootstrap.php';

$config = sqlibase_get_config();

$vulnCardConfig = [
    'title'           => $config['ui']['title'],
    'rangeCode'       => $config['range_code'],
    'starCount'       => $config['ui']['star_count'],
    'scoreThresholds' => $config['ui']['score_thresholds'],
    'starTitles'      => $config['ui']['star_titles'],
    'vulnTypes'       => $config['ui']['vuln_types'],
    'submittedRecords' => [],
    'totalScore'      => 0,
    'maxScore'        => $config['ui']['max_score'],
    'vulnConfig'      => [
        'validateApiUrl' => 'api/validate-vuln.php',
        'submitMethod'   => 'POST',
    ],
];

require_once $commonBasePath . 'includes/header.php';
require_once $commonBasePath . 'components/vuln-card/includes/HeaSec_VulnCard.php';

if ($dbStatus === 'normal') {
    $pdo = sqlibase_get_pdo();
    $vulnCardConfig['submittedRecords'] = sqlibase_get_submitted_records();
    $vulnCardConfig['totalScore'] = sqlibase_get_total_score();
}

// 设置默认Cookie
$config = sqlibase_get_config();
if (!isset($_COOKIE[$config['cookie']['name']])) {
    $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $cookiePath = $scriptPath !== '' ? rtrim(dirname($scriptPath), '/') . '/' : '/';
    setcookie($config['cookie']['name'], $config['cookie']['default'], time() + $config['cookie']['lifetime'], $cookiePath, '', false, false);
}
?>
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">
<link rel="shortcut icon" href="<?php echo $commonBasePath; ?>favicon.ico">

<div class="range-container heasec-sqlibase-layout">
    <div class="heasec-sqlibase-main">

        <!-- ====== 区域A: 用户交互区 (置顶, 双态切换) ====== -->
        <div class="heasec-user-section">

            <!-- 未登录面板 -->
            <div id="guestPanel" class="heasec-panel-stack">
                <div class="tech-card">
                    <div class="tech-card-header">
                        <h3><i class="fa fa-sign-in"></i> 用户中心</h3>
                    </div>
                    <div class="tech-card-body">
                        <div class="alert alert-warning heasec-inline-alert">
                            <div>
                                <strong>提示</strong>
                            </div>
                            <span class="alert-hint">欢迎来到天积科技资讯平台！本平台提供科技资讯浏览、搜索、用户登录、意见反馈等功能。平台中存在多个SQL注入漏洞，请尝试使用各项功能，通过抓包分析请求参数，找到所有漏洞并提交。</span>
                        </div>
                        <form id="loginForm" class="tech-form">
                            <div class="form-group">
                                <label class="form-label" for="loginUsername"><i class="fa fa-user"></i> 用户名</label>
                                <div class="input-wrapper">
                                    <input type="text" id="loginUsername" name="username" class="tech-input" placeholder="请输入用户名">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="loginPassword"><i class="fa fa-lock"></i> 密码</label>
                                <div class="input-wrapper">
                                    <input type="password" id="loginPassword" name="password" class="tech-input" placeholder="请输入密码">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="tech-btn tech-btn-primary"><i class="fa fa-sign-in"></i> 登录</button>
                                <button type="button" class="tech-btn tech-btn-info" id="btnShowRegister"><i class="fa fa-user-plus"></i> 注册新用户</button>
                            </div>
                        </form>
                        <div id="loginMessage" class="heasec-sqlibase-message"></div>
                    </div>
                </div>
            </div>

            <!-- 已登录面板 -->
            <div id="userPanel" class="heasec-panel-stack is-hidden">
                <div class="tech-card">
                    <div class="tech-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3><i class="fa fa-user-circle"></i> 个人中心</h3>
                        <button type="button" id="btnLogout" class="tech-btn tech-btn-secondary" style="padding: 5px 12px; font-size: 13px; height: auto;"><i class="fa fa-sign-out"></i> 退出登录</button>
                    </div>
                    <div class="tech-card-body">
                        <div class="alert alert-warning heasec-inline-alert">
                            <div>
                                <strong>提示</strong>
                            </div>
                            <span class="alert-hint">欢迎回来！平台中存在多个SQL注入漏洞，请在使用各项功能时注意通过抓包分析请求参数。</span>
                        </div>
                        <div class="tech-info-panel">
                            <p>欢迎，<strong id="userInfo"></strong></p>
                        </div>
                    </div>
                </div>

                <div class="tech-card">
                    <div class="tech-card-header">
                        <h3><i class="fa fa-comment"></i> 意见反馈</h3>
                    </div>
                    <div class="tech-card-body">
                        <form id="feedbackForm" class="tech-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label" for="feedbackCategory"><i class="fa fa-tag"></i> 反馈分类</label>
                                <div class="input-wrapper">
                                    <select id="feedbackCategory" name="category_id" class="tech-input heasec-select">
                                        <option value="">请选择分类</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="feedbackContent"><i class="fa fa-pencil"></i> 反馈内容</label>
                                <div class="input-wrapper">
                                    <textarea id="feedbackContent" name="content" class="tech-input heasec-textarea" rows="4" placeholder="请输入反馈内容"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-camera"></i> 截图上传（可选）</label>
                                <div class="input-wrapper">
                                    <input type="file" name="screenshot" accept="image/jpeg,image/png,image/gif" class="tech-input">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="tech-btn tech-btn-primary"><i class="fa fa-paper-plane"></i> 提交反馈</button>
                            </div>
                        </form>
                        <div id="feedbackMessage" class="heasec-sqlibase-message"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== 区域B: 主内容区 ====== -->
        <div class="heasec-content-section">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3><i class="fa fa-newspaper-o"></i> 资讯浏览</h3>
                </div>
                <div class="tech-card-body">
                    <div class="heasec-sqlibase-search">
                        <select id="searchCategory" class="tech-input heasec-select">
                            <option value="">选择分类搜索...</option>
                        </select>
                        <button id="btnSearch" class="tech-btn tech-btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    </div>
                    <div id="articleList"></div>
                    <div id="articleDetail" class="is-hidden">
                        <button id="btnBack" class="tech-btn tech-btn-secondary"><i class="fa fa-arrow-left"></i> 返回列表</button>
                        <h2 id="detailTitle" class="heasec-sqlibase-detail-title"></h2>
                        <div class="heasec-sqlibase-meta" id="detailMeta"></div>
                        <div id="detailContent" class="heasec-sqlibase-detail-content"></div>
                    </div>
                    <div id="searchResults" class="is-hidden">
                        <button id="btnClearSearch" class="tech-btn tech-btn-secondary"><i class="fa fa-times"></i> 清除搜索</button>
                        <div id="searchResultsList"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== 区域C: 系统工具区 (Tab切换) ====== -->
        <div class="heasec-tools-section">
            <div class="tech-card">
                <div class="tech-card-header heasec-tab-header">
                    <h3><i class="fa fa-cog"></i> 系统设置</h3>
                    <div class="heasec-tab-nav">
                        <button type="button" class="heasec-tab-btn active" data-tab="preferences">
                            <i class="fa fa-sliders"></i> 偏好设置
                        </button>
                        <button type="button" class="heasec-tab-btn" data-tab="statistics">
                            <i class="fa fa-bar-chart"></i> 访问统计
                        </button>
                    </div>
                </div>
                <div class="tech-card-body">
                    <div id="tabPreferences" class="heasec-tab-content active">
                        <div class="tech-info-panel" id="preferencesInfo">
                            <div class="heasec-data-grid">
                                <div><span>每页显示</span><strong id="prefPerPage">-</strong></div>
                                <div><span>主题风格</span><strong id="prefTheme">-</strong></div>
                            </div>
                        </div>
                        <div id="preferencesEdit" style="margin-top: 16px;">
                            <form id="preferencesForm" class="tech-form">
                                <div class="form-group">
                                    <label class="form-label"><i class="fa fa-list"></i> 每页显示</label>
                                    <div class="input-wrapper">
                                        <select id="prefPerPageSelect" class="tech-input heasec-select">
                                            <option value="5">5条</option>
                                            <option value="10">10条</option>
                                            <option value="20">20条</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fa fa-paint-brush"></i> 主题风格</label>
                                    <div class="input-wrapper">
                                        <select id="prefThemeSelect" class="tech-input heasec-select">
                                            <option value="blue">蓝色</option>
                                            <option value="green">绿色</option>
                                            <option value="dark">深色</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="tech-btn tech-btn-primary"><i class="fa fa-save"></i> 保存偏好</button>
                                </div>
                            </form>
                            <div id="prefMessage" class="heasec-sqlibase-message"></div>
                        </div>
                    </div>
                    <div id="tabStatistics" class="heasec-tab-content is-hidden">
                        <div class="tech-info-panel">
                            <div class="heasec-data-grid">
                                <div><span>总访客数</span><strong id="totalVisits">-</strong></div>
                                <div><span>总访问量</span><strong id="totalHits">-</strong></div>
                                <div><span>今日访客</span><strong id="todayVisits">-</strong></div>
                            </div>
                            <div id="visitorInfo" style="display:none; margin-top:15px; padding:12px; background:#f0f4ff; border-radius:8px; border:1px solid #d0d7ff;">
                                <div style="font-size:13px; color:#5a6fd8; margin-bottom:6px;"><i class="fa fa-user-circle"></i> 最近访客识别</div>
                                <div style="font-size:12px; color:#495057; word-break:break-all;" id="visitorDetail"></div>
                            </div>
                            <div id="visitError" style="display:none; margin-top:15px; padding:12px; background:#fff3f3; border-radius:8px; border:1px solid #ffd0d0;">
                                <div style="font-size:13px; color:#dc3545; margin-bottom:6px;"><i class="fa fa-exclamation-triangle"></i> 系统提示</div>
                                <div style="font-size:12px; color:#495057; word-break:break-all;" id="visitErrorDetail"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 右侧漏洞挖掘卡片 -->
    <div class="heasec-sqlibase-side">
        <?php echo renderVulnCard($vulnCardConfig, $commonBasePath); ?>
    </div>
</div>

<!-- 注册模态框 -->
<div id="registerModal" class="heasec-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-medium">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa fa-user-plus"></i> 注册新用户</h3>
            <button type="button" class="modal-close heasec-modal-close">&times;</button>
        </div>
        <div class="modal-content">
            <form id="registerForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="regUsername"><i class="fa fa-user"></i> 用户名</label>
                    <div class="input-wrapper">
                        <input type="text" id="regUsername" name="username" class="tech-input" maxlength="20" placeholder="4-20位字母、数字或下划线" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="regPassword"><i class="fa fa-lock"></i> 密码</label>
                    <div class="input-wrapper">
                        <input type="password" id="regPassword" name="password" class="tech-input" maxlength="20" placeholder="6-20位密码" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="regName"><i class="fa fa-address-card"></i> 姓名</label>
                    <div class="input-wrapper">
                        <input type="text" id="regName" name="name" class="tech-input" maxlength="50" placeholder="请输入您的姓名" autocomplete="off">
                    </div>
                </div>
                <div class="heasec-modal-message" id="registerModalMessage"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="tech-btn tech-btn-secondary modal-cancel"><i class="fa fa-times"></i> 取消</button>
            <button type="submit" form="registerForm" class="tech-btn tech-btn-primary"><i class="fa fa-user-plus"></i> 注册</button>
        </div>
    </div>
</div>

<script>
    window.HeaSec = window.HeaSec || {};
    window.HeaSec.team = {
        cnName: '天积安全',
        enName: 'HeavenlySecret',
        shortName: 'HeaSec',
        slogan: '日积寸功，乐享安全'
    };
</script>
<script src="js/sqlibase.js?v=<?php echo $version; ?>"></script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>
