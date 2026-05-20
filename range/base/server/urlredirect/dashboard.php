<?php
/**
 * HeaSec天积安全团队 - URL任意跳转靶场 - 请求记录页面
 * 版本: v1.0.0
 * 创建日期: 2026-04-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec URL任意跳转 Dashboard v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '请求记录';
$rangeName = 'URL任意跳转';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理和数据库
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('urlredirect');
HeaSec_ValidateSession();

// 检查登录状态，未登录跳转到index.php
if (!isset($_SESSION['urlredirect_user_id']) || empty($_SESSION['urlredirect_user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['urlredirect_user_id'];
$username = isset($_SESSION['urlredirect_username']) ? $_SESSION['urlredirect_username'] : '';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_base');

// 分页配置
$pageSize = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

// 查询总记录数
$stmt = $pdo->prepare('SELECT COUNT(*) FROM heasec_urlredirect_requests WHERE user_id = ?');
$stmt->execute([$userId]);
$totalRequests = intval($stmt->fetchColumn());
$totalPages = max(1, ceil($totalRequests / $pageSize));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $pageSize;

// 分页查询请求记录
$stmt = $pdo->prepare('SELECT * FROM heasec_urlredirect_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
$stmt->execute([$userId, $pageSize, $offset]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查询成就数据（已使用的不同绕过类型数）
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT bypass_type) as type_count FROM heasec_urlredirect_achievements WHERE user_id = ?');
$stmt->execute([$userId]);
$typeCount = intval($stmt->fetchColumn());

// 查询成就详情
$stmt = $pdo->prepare('SELECT * FROM heasec_urlredirect_achievements WHERE user_id = ? ORDER BY first_success_at ASC');
$stmt->execute([$userId]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 统计信息（基于全部记录）
$stmt = $pdo->prepare('SELECT COUNT(*) FROM heasec_urlredirect_requests WHERE user_id = ? AND is_valid = 1');
$stmt->execute([$userId]);
$successRequests = intval($stmt->fetchColumn());

// 计算解锁下一颗星还需的类型数
$progressHint = '';
if ($typeCount < 5) {
    $nextThreshold = null;
    foreach ([1, 3, 5] as $t) {
        if ($typeCount < $t) {
            $nextThreshold = $t;
            break;
        }
    }
    if ($nextThreshold !== null) {
        $need = $nextThreshold - $typeCount;
        $progressHint = '再发现 ' . $need . ' 种类型即可解锁下一颗星星';
    }
}

// 构建recordGroups（按绕过类型分组）
$recordGroups = [];
if (!empty($achievements)) {
    $groupRecords = [];
    foreach ($achievements as $ach) {
        $groupRecords[] = [
            'name' => $ach['bypass_type'],
            'desc' => $ach['bypass_desc'],
            'count' => intval($ach['success_count'])
        ];
    }
    $recordGroups[] = [
        'title' => '绕过方式记录',
        'icon' => 'fa fa-shield',
        'records' => $groupRecords,
        'hint' => $progressHint
    ];
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 请求记录卡片 -->
    <div class="tech-card tech-card-main">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-list-alt"></i>
                请求记录
            </h3>
            <a href="api/logout.php" class="tech-btn tech-btn-logout">
                <i class="fa fa-sign-out"></i> 退出登录
            </a>
        </div>
        <div class="tech-card-body">

            <!-- 用户信息独立区域 -->
            <div class="user-info-section">
                <div class="user-info-item">
                    <i class="fa fa-user"></i>
                    <span class="user-info-label">用户名：</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>

            <?php if (empty($requests)): ?>
                <div class="alert alert-info">
                    <div>
                        <i class="fa fa-info-circle"></i>
                        <strong>暂无请求记录</strong>
                    </div>
                    <p style="margin-top: 8px; font-size: 13px;">前往 <a href="index.php" style="color: #007BFF;">登录页面</a> 构造URL进行测试</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="request-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>提交的URL</th>
                                <th>解析域名</th>
                                <th style="width: 60px;">是否通过</th>
                                <th>绕过类型</th>
                                <th style="width: 130px;">提交时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $index = $totalRequests - $offset;
                            foreach ($requests as $req):
                                $isValid = $req['is_valid'] == 1;
                                $rowClass = $isValid ? 'row-success' : 'row-muted';
                                $urlDisplay = mb_strlen($req['raw_url']) > 50 ? mb_substr($req['raw_url'], 0, 50) . '...' : $req['raw_url'];
                            ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><?php echo $index--; ?></td>
                                    <td>
                                        <span title="<?php echo htmlspecialchars($req['raw_url']); ?>">
                                            <?php echo htmlspecialchars($urlDisplay); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($req['parsed_host'] ?: '-'); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($isValid): ?>
                                            <span class="badge badge-success"><i class="fa fa-check"></i> 通过</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><i class="fa fa-times"></i> 未通过</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($req['bypass_type'])): ?>
                                            <span class="badge badge-bypass"><?php echo htmlspecialchars($req['bypass_type']); ?></span>
                                            <div class="bypass-desc"><?php echo htmlspecialchars($req['bypass_desc']); ?></div>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #666;">
                                        <?php echo htmlspecialchars($req['created_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 统计信息 -->
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $totalRequests; ?></span>
                        <span class="stat-label">总提交次数</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $successRequests; ?></span>
                        <span class="stat-label">成功次数</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $typeCount; ?></span>
                        <span class="stat-label">绕过类型数</span>
                    </div>
                </div>

                <!-- 分页导航 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?p=1" class="page-btn"><i class="fa fa-angle-double-left"></i></a>
                        <a href="<?php echo '?p=' . ($currentPage - 1); ?>" class="page-btn"><i class="fa fa-angle-left"></i></a>
                    <?php else: ?>
                        <span class="page-btn disabled"><i class="fa fa-angle-double-left"></i></span>
                        <span class="page-btn disabled"><i class="fa fa-angle-left"></i></span>
                    <?php endif; ?>

                    <?php
                    // 显示页码（最多显示7个页码）
                    $startPage = max(1, $currentPage - 3);
                    $endPage = min($totalPages, $startPage + 6);
                    if ($endPage - $startPage < 6) {
                        $startPage = max(1, $endPage - 6);
                    }
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <?php if ($p == $currentPage): ?>
                            <span class="page-btn active"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="?p=<?php echo $p; ?>" class="page-btn"><?php echo $p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?php echo '?p=' . ($currentPage + 1); ?>" class="page-btn"><i class="fa fa-angle-right"></i></a>
                        <a href="?p=<?php echo $totalPages; ?>" class="page-btn"><i class="fa fa-angle-double-right"></i></a>
                    <?php else: ?>
                        <span class="page-btn disabled"><i class="fa fa-angle-right"></i></span>
                        <span class="page-btn disabled"><i class="fa fa-angle-double-right"></i></span>
                    <?php endif; ?>

                    <span class="page-info">共 <?php echo $totalRequests; ?> 条 / <?php echo $totalPages; ?> 页</span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 成就系统卡片 -->
    <?php
    require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
    echo renderAchievementCard([
        'title' => '成就系统',
        'achievedCount' => $typeCount,
        'thresholds' => [1, 3, 5],
        'titles' => ['初学者', '探索者', '大师'],
        'rangeCode' => 'urlredirect',
        'recordGroups' => $recordGroups,
        'recordsTitle' => '成功记录',
        'recordLabel' => '绕过类型',
        'congratsConfig' => [
            'messages' => [
                'partial' => '你已经掌握了 %d 种URL跳转绕过方式！继续尝试更多绕过技巧',
                'complete' => '太棒了！你已经掌握了5种URL跳转绕过方式，完成本靶场全部成就！'
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
