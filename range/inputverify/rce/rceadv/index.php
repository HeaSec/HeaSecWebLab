<?php
/**
 * HeaSec天积安全团队 - 命令执行实战靶场
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 命令执行实战 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '命令执行实战靶场';
$rangeName = '命令执行实战';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;

// 引入公共头部（header.php 会处理数据库检测和初始化）
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件和靶场函数
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/includes/functions.php';

// 操作系统检测
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$osType = $isWindows ? 'Windows' : 'Linux';

// Linux 环境前置自检（检测工具与权限是否满足成就完成条件）
$envCheck = getEnvironmentCheck();

// 成就名称映射（用于环境警告展示）
$achievementNames = [
    'reverse_shell' => '成就一（反弹shell）',
    'create_user'   => '成就二（系统渗透）',
    'open_port'     => '成就三（计划任务）'
];

// 初始化业务数据
$achievedCount = 0;
$records = [];
$progressHint = '';
$achievementData = [
    'reverse_shell' => false,
    'create_user' => false,
    'open_port' => false
];

try {
    $pdo = HeaSec_Database::getConnection('heasec_inputverify');
    $achievementData = getAchievementStatus($pdo);
    $achievedCount = $achievementData['achieved_count'];
    $records = $achievementData['records'];
    $progressHint = generateProgressHint($achievedCount);
} catch (Exception $e) {
    $progressHint = '';
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 左侧：诊断工具 + 成就验证 -->
    <div class="rceadv-left-col">

        <!-- 卡片一：天积网络诊断中心 -->
        <div class="tech-card">
            <div class="tech-card-header">
                <h3>
                    <i class="fa fa-terminal"></i>
                    天积网络诊断中心
                </h3>
            </div>
            <div class="tech-card-body">
                <!-- 功能说明 -->
                <div class="alert alert-info">
                    <div>
                        <i class="fa fa-info-circle"></i>
                        <strong>天积网络诊断中心</strong>
                    </div>
                    <span class="alert-hint">
                        <small>天积网络诊断中心提供Ping网络连通性检测工具，输入目标地址即可检测网络状态。</small>
                    </span>
                </div>

                <!-- 任务引导 -->
                <div id="taskHint" class="alert alert-warning">
                    <div>
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>任务目标</strong>
                    </div>
                    <span class="alert-hint">
                        <small>利用网络诊断工具中的命令注入漏洞完成以下实战操作，每完成一项即可解锁对应成就。</small>
                    </span>
                    <ul class="task-list">
                        <li><strong>【系统信息】</strong>当前运行环境：<?php echo htmlspecialchars($osType); ?></li>
                        <li><strong>【成就一】</strong>反弹shell — 通过命令注入建立反弹shell连接</li>
                        <li><strong>【成就二】</strong>系统渗透 — 创建指定的系统管理员用户</li>
                        <li><strong>【成就三】</strong>计划任务 — <?php echo $isWindows ? '通过计划任务开启RDP服务' : '通过计划任务启动WEB服务'; ?></li>
                        <li><strong>【提示】</strong>你需要在本地搭建监听服务器来接收反弹shell连接</li>
                        <?php if ($envCheck['needed'] && (!empty($envCheck['missing_tools']) || $envCheck['is_root'] === false)): ?>
                        <li><strong>【环境检测】</strong>当前处于不完整的Linux环境
                            <?php if (!empty($envCheck['missing_tools'])): ?>
                            （缺失命令：<?php echo htmlspecialchars(implode('、', $envCheck['missing_tools'])); ?>）
                            <?php endif; ?>
                            <?php if ($envCheck['is_root'] === false): ?>
                            （Web进程非root权限）
                            <?php endif; ?>
                            ，以下成就可能无法完成：
                        </li>
                        <?php foreach ($envCheck['issues'] as $issueType => $issueDesc): ?>
                            <?php if ($issueDesc !== null): ?>
                        <li><strong>【<?php echo $achievementNames[$issueType]; ?>】</strong><?php echo htmlspecialchars($issueDesc); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li>建议将靶场部署到具备完整工具链（crontab、python、useradd、ss/netstat）和root权限的Linux环境。</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- 输入区域 -->
                <div class="submit-section">
                    <input type="text" id="hostInput" placeholder="输入目标地址（如 127.0.0.1）" autocomplete="off">
                    <button type="button" id="execBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-play"></i> 开始诊断
                    </button>
                </div>

                <!-- 输出展示区域 -->
                <div id="outputArea" class="terminal-output"></div>
            </div>
        </div>

        <!-- 卡片二：成就验证 -->
        <div class="tech-card">
            <div class="tech-card-header">
                <h3>
                    <i class="fa fa-check-circle"></i>
                    成就验证
                </h3>
            </div>
            <div class="tech-card-body">

                <!-- 成就一：反弹shell -->
                <div class="achievement-verify-item">
                    <div class="verify-header">
                        <span class="verify-title">成就一：反弹shell</span>
                        <span class="verify-status" id="status-reverse_shell">
                            <?php if ($achievementData['reverse_shell']): ?>
                                <i class="fa fa-check-circle" style="color: #28a745;"></i>
                            <?php else: ?>
                                <i class="fa fa-lock" style="color: #6c757d;"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <p class="verify-desc">通过命令注入漏洞触发反弹shell连接到你的服务器。<br>
                    完成反弹shell后，在下方输入你监听服务器的IP和端口进行验证。<br>
                    系统将检测靶场服务器是否有到该IP:PORT的活跃出站连接来确认反弹shell已建立。</p>
                    <div class="verify-hint">
                        <?php if ($isWindows): ?>
                        <small>Windows提示：可使用PowerShell反弹shell<br>
                        <strong>重要：</strong>注入的反弹shell命令必须在后台运行，否则会导致诊断工具超时无响应。<br>
                        Windows: 使用 start /B 前缀</small>
                        <?php else: ?>
                        <small>Linux提示：可使用bash反弹shell；注意系统shell不支持 /dev/tcp，反弹命令需用 <code>bash -c "..."</code> 包裹执行<br>
                        <strong>重要：</strong>注入的后台命令若继承诊断工具的标准输出管道，会导致诊断接口永久挂起，必须在后台运行并脱离标准流。<br>
                        Linux: 形如 <code>bash -c "反弹命令" &lt;/dev/null &gt;/dev/null 2&gt;&amp;1 &amp;</code>（末尾 &amp; 放后台，重定向用于断开标准流继承）</small>
                        <?php endif; ?>
                    </div>
                    <div class="verify-input-row">
                        <div class="verify-field">
                            <label>监听IP</label>
                            <input type="text" id="shellIp" placeholder="如 192.168.1.100" autocomplete="off">
                        </div>
                        <div class="verify-field">
                            <label>监听PORT</label>
                            <input type="text" id="shellPort" placeholder="如 8888" autocomplete="off">
                        </div>
                        <button type="button" class="tech-btn tech-btn-success verify-btn" data-type="reverse_shell">
                            <i class="fa fa-check"></i> 验证
                        </button>
                    </div>
                    <div class="verify-result" id="result-reverse_shell"></div>
                </div>

                <!-- 成就二：系统渗透 -->
                <div class="achievement-verify-item">
                    <div class="verify-header">
                        <span class="verify-title">成就二：系统渗透</span>
                        <span class="verify-status" id="status-create_user">
                            <?php if ($achievementData['create_user']): ?>
                                <i class="fa fa-check-circle" style="color: #28a745;"></i>
                            <?php else: ?>
                                <i class="fa fa-lock" style="color: #6c757d;"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <p class="verify-desc">通过命令注入漏洞在系统中创建以下管理员用户：<br>
                    用户名: <code>heasec</code> &nbsp; 密码: <code>heasec@666</code><br>
                    要求具有管理员/sudo权限。完成后点击验证按钮检查用户是否创建成功。</p>
                    <div class="verify-actions">
                        <button type="button" class="tech-btn tech-btn-success verify-btn" data-type="create_user">
                            <i class="fa fa-check"></i> 验证
                        </button>
                    </div>
                    <div class="verify-result" id="result-create_user"></div>
                </div>

                <!-- 成就三：端口开放 -->
                <div class="achievement-verify-item">
                    <div class="verify-header">
                        <span class="verify-title">成就三：计划任务</span>
                        <span class="verify-status" id="status-open_port">
                            <?php if ($achievementData['open_port']): ?>
                                <i class="fa fa-check-circle" style="color: #28a745;"></i>
                            <?php else: ?>
                                <i class="fa fa-lock" style="color: #6c757d;"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <p class="verify-desc">通过命令注入漏洞创建计划任务实现服务持久化：<br>
                    <?php if ($isWindows): ?>
                    Windows: 使用schtasks创建一个名为 <code>HeaSecRDP</code> 的计划任务，用于启动TermService（RDP服务）。
                    <?php else: ?>
                    Linux: 使用crontab创建一个包含 <code>HeaSecWeb</code> 标识的定时任务，任务内容为使用python启动WEB服务（命令示例：<code>python3 -m http.server 8888</code>）。
                    <?php endif; ?><br>
                    完成后点击验证按钮，系统将检查该计划任务是否存在。</p>
                    <div class="verify-actions">
                        <button type="button" class="tech-btn tech-btn-success verify-btn" data-type="open_port">
                            <i class="fa fa-check"></i> 验证
                        </button>
                    </div>
                    <div class="verify-result" id="result-open_port"></div>
                </div>

            </div>
        </div>

    </div>

    <!-- 右侧：成就系统 -->
    <div class="rceadv-right-col">
        <?php
        $recordGroups = [[
            'title'   => '已完成成就',
            'icon'    => 'fa fa-trophy',
            'records' => $records,
            'hint'    => $progressHint
        ]];

        require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
        echo renderAchievementCard([
            'title'              => '实战成就',
            'achievedCount'      => $achievedCount,
            'thresholds'         => [1, 2, 3],
            'titles'             => ['渗透新手', '渗透能手', '渗透专家'],
            'recordGroups'       => $recordGroups,
            'recordLabel'        => '成就',
            'rangeCode'          => 'rceadv',
            'congratsConfig'     => [
                'messages' => [
                    'partial_title'  => '成就解锁！',
                    'complete_title' => '恭喜你成为渗透专家！',
                    'partial'        => '太棒了！你已完成 %d/3 个实战挑战！继续完成剩余挑战吧！',
                    'complete'       => '太棒了！你已掌握命令执行漏洞的三大核心利用方向：权限获取、权限维持和持久化控制！',
                    'buttonText'     => '继续学习'
                ]
            ]
        ], $commonBasePath);
        ?>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/rceadv.js?v=<?php echo $version; ?>"></script>
<script>
window.HeaSecRceAdvInitCount = <?php echo intval($achievedCount); ?>;
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
