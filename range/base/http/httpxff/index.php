<?php
/**
 * HeaSec天积安全团队 - HTTP 代理IP请求头靶场
 * 版本: v1.0.0
 * 创建日期: 2025-11-07
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec HTTP 代理IP请求头 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'HTTP 代理IP请求头靶场';
$rangeName = 'HTTP 代理IP请求头靶场';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;  // 此靶场使用数据库

// 注意：数据库状态检查已移至公共组件header.php中自动处理

// 此靶场不需要会话管理功能

// 引入公共头部（包含自动数据库检查）
require_once $commonBasePath . 'includes/header.php';

// 获取数据库连接的统一函数
function getHttpXFFDatabase()
{
    static $db = null;
    if ($db === null) {
        global $commonBasePath;
        require_once $commonBasePath . 'includes/database.php';
        $db = heasec_db('heasec_base');
    }
    return $db;
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件（用于覆盖和扩展） -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 引入星星系统组件资源（CSS样式） -->
<?php
// 直接引入星星系统核心组件的CSS样式
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);
?>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- HTTP请求头检测区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-search"></i>
                源IP检测
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 子区域1：智能解析源IP -->
            <h4>当前源IP：</h4>
            <div class="tech-info-panel" style="animation: none;">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-value">
                            <code>
                                <?php
                                // 获取源IP的逻辑
                                $sourceIP = '';
                                $effectiveHeader = '';

                                // 按优先级顺序检查请求头
                                $headerPriority = [
                                    'HTTP_X_FORWARDED_FOR',
                                    'HTTP_X_REAL_IP',
                                    'HTTP_X_CLIENT_IP',
                                    'HTTP_X_CLUSTER_CLIENT_IP',
                                    'HTTP_X_ORIGINATING_IP',
                                    'HTTP_X_REMOTE_IP',
                                    'HTTP_X_REMOTE_ADDR',
                                    'HTTP_X_FORWARDED',
                                    'HTTP_FORWARDED_FOR',
                                    'HTTP_FORWARDED',
                                    'HTTP_TRUE_CLIENT_IP',
                                    'HTTP_CLIENT_IP',
                                    'HTTP_ALI_CDN_REAL_IP',
                                    'HTTP_CDN_SRC_IP',
                                    'HTTP_CDN_REAL_IP',
                                    'HTTP_CF_CONNECTING_IP',
                                    'HTTP_WL_PROXY_CLIENT_IP',
                                    'HTTP_PROXY_CLIENT_IP'
                                ];

                                foreach ($headerPriority as $header) {
                                    if (!empty($_SERVER[$header])) {
                                        $ip = trim($_SERVER[$header]);
                                        // 验证IPV4格式
                                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                            $sourceIP = $ip;
                                            // 找到对应的显示名称
                                            $headerMap = [
                                                'HTTP_X_FORWARDED_FOR' => 'X-Forwarded-For',
                                                'HTTP_X_REAL_IP' => 'X-Real-IP',
                                                'HTTP_X_CLIENT_IP' => 'X-Client-IP',
                                                'HTTP_X_CLUSTER_CLIENT_IP' => 'X-Cluster-Client-IP',
                                                'HTTP_X_ORIGINATING_IP' => 'X-Originating-IP',
                                                'HTTP_X_REMOTE_IP' => 'X-Remote-IP',
                                                'HTTP_X_REMOTE_ADDR' => 'X-Remote-Addr',
                                                'HTTP_X_FORWARDED' => 'X-Forwarded',
                                                'HTTP_FORWARDED_FOR' => 'Forwarded-For',
                                                'HTTP_FORWARDED' => 'Forwarded',
                                                'HTTP_TRUE_CLIENT_IP' => 'True-Client-IP',
                                                'HTTP_CLIENT_IP' => 'Client-IP',
                                                'HTTP_ALI_CDN_REAL_IP' => 'Ali-CDN-Real-IP',
                                                'HTTP_CDN_SRC_IP' => 'Cdn-Src-IP',
                                                'HTTP_CDN_REAL_IP' => 'Cdn-Real-IP',
                                                'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP',
                                                'HTTP_WL_PROXY_CLIENT_IP' => 'WL-Proxy-Client-IP',
                                                'HTTP_PROXY_CLIENT_IP' => 'Proxy-Client-IP'
                                            ];
                                            $effectiveHeader = isset($headerMap[$header]) ? $headerMap[$header] : $header;
                                            break;
                                        }
                                    }
                                }

                                // 如果没有找到有效的请求头，使用REMOTE_ADDR
                                if (empty($sourceIP)) {
                                    if (!empty($_SERVER['REMOTE_ADDR'])) {
                                        $ip = trim($_SERVER['REMOTE_ADDR']);
                                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                            $sourceIP = $ip;
                                        } else {
                                            $sourceIP = '请求头值不是IPV4格式的IP地址';
                                        }
                                    } else {
                                        $sourceIP = '无法获取用户源IP';
                                    }
                                }

                                echo htmlspecialchars($sourceIP);
                                ?>
                            </code>
                            <?php if (!empty($effectiveHeader)): ?>
                                <span class="badge badge-success"><?php echo htmlspecialchars($effectiveHeader); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- 子区域2：验证结果 -->
            <div class="detection-result">
                <h4>验证结果：</h4>
                <div class="alert alert-<?php
                $targetIP = '66.66.66.66';
                $isValid = ($sourceIP === $targetIP);
                $hasTargetHeader = !empty($effectiveHeader);

                if ($isValid && $hasTargetHeader) {
                    // 记录成功的请求头到数据库
                    try {
                        $db = getHttpXFFDatabase();

                        // 插入或更新记录
                        $sql = "INSERT INTO heasec_httpxff_records (header_name, success_count, last_success_at)
                                    VALUES (?, 1, NOW())
                                    ON DUPLICATE KEY UPDATE
                                    success_count = success_count + 1,
                                    last_success_at = NOW()";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$effectiveHeader]);

                        echo 'success';
                    } catch (Exception $e) {
                        error_log('[HeaSec] Database error: ' . $e->getMessage());
                        echo 'warning';
                    }
                } elseif ($isValid) {
                    echo 'warning';
                } else {
                    echo 'error';
                }
                ?>">
                    <div>
                        <i class="fa fa-<?php
                        if ($isValid && $hasTargetHeader) {
                            echo 'check-circle';
                        } elseif ($isValid) {
                            echo 'exclamation-triangle';
                        } else {
                            echo 'times-circle';
                        }
                        ?>"></i>
                        <strong>
                            <?php
                            if ($isValid && $hasTargetHeader) {
                                echo '恭喜你，你找到了一个有用的请求头！<br>它是' . htmlspecialchars($effectiveHeader);
                            } elseif ($isValid) {
                                echo '你的源IP是66.66.66.66，但没有使用有效的请求头！';
                            } else {
                                echo '你的源IP不是66.66.66.66，不符合要求';
                            }
                            ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 成就系统区域 - 使用公共组件 -->
    <?php
    // 引入成就卡片公共组件
    require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';

    // 获取成就数量和记录
    $starCount = 0;
    $records = [];
    try {
        $db = getHttpXFFDatabase();
        $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_httpxff_records");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $starCount = intval($result['count']);

        // 获取记录列表
        $stmt = $db->query("SELECT header_name, success_count, last_success_at FROM heasec_httpxff_records ORDER BY last_success_at DESC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 转换记录格式
        $formattedRecords = [];
        foreach ($records as $record) {
            $formattedRecords[] = [
                'name' => $record['header_name'],
                'count' => $record['success_count'],
                'time' => $record['last_success_at']
            ];
        }
    } catch (Exception $e) {
        error_log('[HeaSec] Database error: ' . $e->getMessage());
        $starCount = 0;
        $formattedRecords = [];
    }

    // 渲染成就卡片公共组件
    echo renderAchievementCard([
        'achievedCount' => $starCount,
        'customRecords' => $formattedRecords,
        'recordsTitle' => '成功使用过的请求头',
        'rangeCode' => 'httpxff',

        // 恭喜功能配置（仅传自定义消息）
        'congratsConfig' => [
            'messages' => [
                'partial' => '你已经掌握了 %d/3 种代理请求头！继续努力，获得更多的成就！',
                'complete' => '太棒了！你已经掌握了3种代理请求头，解锁全部成就！你还可以继续尝试其他HTTP头'
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>