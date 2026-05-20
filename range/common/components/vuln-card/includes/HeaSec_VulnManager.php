<?php
/**
 * HeaSec天积安全团队 - 漏洞挖掘管理公共组件
 * Vulnerability Discovery Manager
 * 版本: v1.0.0
 * 创建日期: 2026-03-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明：
 * - 封装基于数据库的漏洞记录管理（增删查、去重）
 * - 漏洞定义加载与匹配算法
 * - 星星状态和恭喜弹窗状态管理
 * - 提供标准 API 处理器供靶场 API 文件调用
 */

class HeaSec_VulnManager
{
    /** @var PDO 数据库连接 */
    private $pdo;

    /** @var string 漏洞记录表名 */
    private $vulnRecordsTable;

    /** @var string 星星状态表名 */
    private $starStatusTable;

    /** @var array 分数阈值列表 */
    private $scoreThresholds;

    /** @var array|null 已加载的漏洞定义缓存 */
    private $vulnDefinitions = null;

    /** @var string|null 漏洞配置文件路径 */
    private $vulnConfigPath = null;

    /** @var string 靶场代码标识 */
    private $rangeCode = '';

    /** @var bool 是否显示参数错误详情 */
    private $showParamError = false;

    /** @var bool 是否显示类型错误详情 */
    private $showTypeError = false;

    /**
     * 构造函数
     *
     * @param array $config 配置参数
     *  - pdo:              PDO       (必填) 数据库连接
     *  - vulnConfigPath:   string    (二选一) 漏洞配置文件路径
     *  - vulnDefinitions:  array     (二选一) 漏洞定义数组，格式同 vuln_config.php
     *  - vulnRecordsTable: string    (必填) 漏洞记录表名
     *  - starStatusTable:  string    (必填) 星星状态表名
     *  - scoreThresholds:  array     (必填) 星星解锁的分数阈值
     *  - sessionId:        string    (已废弃，保留兼容性) 会话标识
     *  - rangeCode:        string    (可选) 靶场代码
     *  - showParamError:   bool      (可选) 是否显示参数错误详情，默认 false
     *  - showTypeError:    bool      (可选) 是否显示类型错误详情，默认 false
     */
    public function __construct(array $config)
    {
        $required = ['pdo', 'vulnRecordsTable', 'starStatusTable', 'scoreThresholds'];
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new InvalidArgumentException("[HeaSec_VulnManager] 缺少必填配置项: {$key}");
            }
        }

        if (!isset($config['vulnConfigPath']) && !isset($config['vulnDefinitions'])) {
            throw new InvalidArgumentException("[HeaSec_VulnManager] 必须提供 vulnConfigPath 或 vulnDefinitions");
        }

        $this->pdo = $config['pdo'];
        $this->vulnRecordsTable = $config['vulnRecordsTable'];
        $this->starStatusTable = $config['starStatusTable'];
        $this->scoreThresholds = $config['scoreThresholds'];
        $this->rangeCode = isset($config['rangeCode']) ? $config['rangeCode'] : '';
        $this->showParamError = isset($config['showParamError']) ? (bool) $config['showParamError'] : false;
        $this->showTypeError = isset($config['showTypeError']) ? (bool) $config['showTypeError'] : false;

        if (isset($config['vulnConfigPath'])) {
            $this->vulnConfigPath = $config['vulnConfigPath'];
        }

        if (isset($config['vulnDefinitions'])) {
            $this->loadDefinitionsFromArray($config['vulnDefinitions']);
        }
    }

    // ========================================================================
    // 漏洞定义管理
    // ========================================================================

    /**
     * 获取漏洞定义列表
     *
     * @return array
     */
    public function getVulnDefinitions()
    {
        if ($this->vulnDefinitions !== null) {
            return $this->vulnDefinitions;
        }

        if ($this->vulnConfigPath !== null) {
            $this->loadDefinitionsFromFile($this->vulnConfigPath);
        } else {
            $this->vulnDefinitions = [];
        }

        return $this->vulnDefinitions;
    }

    /**
     * 从配置文件加载漏洞定义
     *
     * @param string $filePath 配置文件路径
     */
    private function loadDefinitionsFromFile($filePath)
    {
        if (!file_exists($filePath)) {
            $this->vulnDefinitions = [];
            return;
        }

        $config = require $filePath;
        $vulns = isset($config['vulns']) ? $config['vulns'] : [];
        $this->loadDefinitionsFromArray($vulns);
    }

    /**
     * 从数组加载漏洞定义
     * 格式: [[URL, [[参数名, 位置], ...], 漏洞类型, 分数], ...]
     *
     * @param array $vulns 漏洞定义数组
     */
    private function loadDefinitionsFromArray(array $vulns)
    {
        $this->vulnDefinitions = [];

        foreach ($vulns as $item) {
            if (!is_array($item) || count($item) < 4) {
                continue;
            }

            $url = self::normalizeVulnUrl($item[0]);
            $params = [];
            if (isset($item[1]) && is_array($item[1])) {
                foreach ($item[1] as $param) {
                    if (!is_array($param) || count($param) < 2) {
                        continue;
                    }
                    $params[] = [
                        'name' => (string) $param[0],
                        'location' => strtoupper((string) $param[1]),
                    ];
                }
            }

            $normalizedParams = self::normalizeVulnParams($params);
            $type = (string) $item[2];
            $score = (int) $item[3];
            $key = self::buildVulnKey($url, $type, $normalizedParams);

            $this->vulnDefinitions[$key] = [
                'id' => $key,
                'url' => $url,
                'params' => $normalizedParams,
                'type' => $type,
                'score' => $score,
            ];
        }
    }

    /**
     * 匹配漏洞提交
     *
     * @param string $url  提交的URL
     * @param string $type 提交的漏洞类型
     * @param array  $params 提交的参数列表
     * @return array|null 匹配到的漏洞定义，未匹配返回null
     */
    public function matchVulnerability($url, $type, array $params)
    {
        $normalizedUrl = self::normalizeVulnUrl($url);
        $normalizedType = trim((string) $type);
        $normalizedParams = self::normalizeVulnParams($params);
        $definitions = $this->getVulnDefinitions();

        foreach ($definitions as $definition) {
            if ($definition['url'] !== $normalizedUrl) {
                continue;
            }
            if ($definition['type'] !== $normalizedType) {
                continue;
            }
            if (json_encode($definition['params'], JSON_UNESCAPED_UNICODE) !== json_encode($normalizedParams, JSON_UNESCAPED_UNICODE)) {
                continue;
            }
            return $definition;
        }

        return null;
    }

    /**
     * 分析漏洞验证失败的具体原因
     *
     * @param string $url    提交的URL
     * @param string $type   提交的漏洞类型
     * @param array  $params 提交的参数列表
     * @return array 错误详情数组
     */
    public function analyzeValidationError($url, $type, array $params)
    {
        $normalizedUrl = self::normalizeVulnUrl($url);
        $normalizedType = trim((string) $type);
        $normalizedParams = self::normalizeVulnParams($params);
        $definitions = $this->getVulnDefinitions();

        $urlMatched = [];
        $urlAndTypeMatched = [];

        foreach ($definitions as $definition) {
            if ($definition['url'] === $normalizedUrl) {
                $urlMatched[] = $definition;

                if ($definition['type'] === $normalizedType) {
                    $urlAndTypeMatched[] = $definition;
                }
            }
        }

        $errorDetail = [];

        // URL 不匹配
        if (empty($urlMatched)) {
            return []; // URL 完全不匹配，不提供任何提示
        }

        // URL 匹配但类型不匹配
        if (empty($urlAndTypeMatched)) {
            if ($this->showTypeError) {
                $errorDetail['type_error'] = true;
                $errorDetail['hint'] = '漏洞类型不正确';
            }
            return $errorDetail;
        }

        // URL 和类型都匹配，但参数不匹配
        if ($this->showParamError) {
            $correctParamsList = array_column($urlAndTypeMatched, 'params');
            $errorDetail['param_error'] = true;

            // 分析参数差异
            $hints = [];
            foreach ($correctParamsList as $correctParams) {
                $diff = $this->compareParams($normalizedParams, $correctParams);
                if (!empty($diff)) {
                    $hints[] = $diff;
                }
            }

            if (!empty($hints)) {
                $errorDetail['hint'] = '参数不正确: ' . implode('; ', array_unique($hints));
            }
        }

        return $errorDetail;
    }

    /**
     * 比较参数差异
     *
     * @param array $submitted 提交的参数
     * @param array $expected  期望的参数
     * @return string|null 差异描述
     */
    private function compareParams(array $submitted, array $expected)
    {
        $submittedCount = count($submitted);
        $expectedCount = count($expected);

        if ($submittedCount !== $expectedCount) {
            return "参数数量应为 {$expectedCount} 个";
        }

        $submittedNames = array_column($submitted, 'name');
        $expectedNames = array_column($expected, 'name');

        $missing = array_diff($expectedNames, $submittedNames);
        if (!empty($missing)) {
            return "缺少必要参数";
        }

        $extra = array_diff($submittedNames, $expectedNames);
        if (!empty($extra)) {
            return "存在多余参数";
        }

        // 检查参数位置
        foreach ($submitted as $i => $param) {
            if (!isset($expected[$i])) {
                continue;
            }
            if ($param['location'] !== $expected[$i]['location']) {
                return "参数 {$param['name']} 位置错误";
            }
        }

        return null;
    }

    // ========================================================================
    // 漏洞记录数据库操作（全局共享，不区分会话）
    // ========================================================================

    /**
     * 检查漏洞记录是否已存在（全局去重）
     *
     * @param string $vulnId 漏洞标识
     * @return bool
     */
    public function hasVulnRecord($vulnId)
    {
        $sql = "SELECT id FROM {$this->vulnRecordsTable} WHERE vuln_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vulnId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * 新增漏洞记录
     *
     * @param string $vulnId 漏洞标识
     * @param int    $score  分数
     */
    public function addVulnRecord($vulnId, $score)
    {
        $sql = "INSERT INTO {$this->vulnRecordsTable} (vuln_id, score) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vulnId, (int) $score]);
    }

    /**
     * 获取全局漏洞总分
     *
     * @return int
     */
    public function getTotalScore()
    {
        $sql = "SELECT COALESCE(SUM(score), 0) FROM {$this->vulnRecordsTable}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * 获取已提交的漏洞记录（全局）
     *
     * @return array
     */
    public function getSubmittedRecords()
    {
        $sql = "SELECT vuln_id, score, created_at FROM {$this->vulnRecordsTable} ORDER BY created_at ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $definitions = $this->getVulnDefinitions();
        $records = [];

        foreach ($rows as $row) {
            if (!isset($definitions[$row['vuln_id']])) {
                continue;
            }
            $definition = $definitions[$row['vuln_id']];
            $records[] = [
                'type' => $definition['type'],
                'url' => $definition['url'],
                'params' => $definition['params'],
                'score' => (int) $row['score'],
                'time' => $row['created_at'],
            ];
        }

        return $records;
    }

    // ========================================================================
    // 星星状态管理（全局单条记录）
    // ========================================================================

    /**
     * 获取全局星星状态
     *
     * @return array ['unlocked_stars' => int]
     */
    public function getStarStatus()
    {
        $sql = "SELECT unlocked_stars FROM {$this->starStatusTable} WHERE id = 1 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return [
                'unlocked_stars' => (int) $row['unlocked_stars'],
            ];
        }

        return [
            'unlocked_stars' => 0,
        ];
    }

    /**
     * 更新全局星星状态
     *
     * @param int      $unlockedStars      已解锁的星星数量
     * @param int|null $congratsShownStars  已废弃，保留兼容性
     */
    public function updateStarStatus($unlockedStars, $congratsShownStars = null)
    {
        $sql = "INSERT INTO {$this->starStatusTable} (id, unlocked_stars) VALUES (1, ?)
                ON DUPLICATE KEY UPDATE unlocked_stars = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $unlockedStars, (int) $unlockedStars]);
    }

    /**
     * 根据分数计算已解锁的星星数量
     *
     * @param int|null $totalScore 总分（null则自动查询）
     * @return int
     */
    public function calculateUnlockedStars($totalScore = null)
    {
        if ($totalScore === null) {
            $totalScore = $this->getTotalScore();
        }

        $unlockedStars = 0;
        foreach ($this->scoreThresholds as $threshold) {
            if ($totalScore >= $threshold) {
                $unlockedStars++;
            } else {
                break;
            }
        }
        return $unlockedStars;
    }

    // ========================================================================
    // API 处理器
    // ========================================================================

    /**
     * 处理漏洞验证请求
     * 返回结构化数组，由调用者负责 JSON 输出
     *
     * @param array  $requestData 请求数据 (vuln_url, vuln_type, params, range_code)
     * @param string $rangeCode   靶场代码 (用于校验)
     * @return array ['success' => bool, 'message' => string, 'data' => array, 'statusCode' => int]
     */
    public function handleValidateRequest(array $requestData, $rangeCode = '')
    {
        $vulnUrl = isset($requestData['vuln_url']) ? trim((string) $requestData['vuln_url']) : '';
        $vulnType = isset($requestData['vuln_type']) ? trim((string) $requestData['vuln_type']) : '';
        $params = isset($requestData['params']) && is_array($requestData['params']) ? $requestData['params'] : [];

        // 校验靶场标识
        $submittedRangeCode = isset($requestData['range_code']) ? trim((string) $requestData['range_code']) : '';
        $expectedCode = $rangeCode ?: $this->rangeCode;
        if ($expectedCode !== '' && $submittedRangeCode !== $expectedCode) {
            return [
                'success' => false,
                'message' => '靶场标识不匹配',
                'data' => [],
                'statusCode' => 400,
            ];
        }

        // 匹配漏洞
        $matchedVuln = $this->matchVulnerability($vulnUrl, $vulnType, $params);
        if (!$matchedVuln) {
            // 分析验证失败原因
            $errorDetail = $this->analyzeValidationError($vulnUrl, $vulnType, $params);

            return [
                'success' => false,
                'message' => '漏洞审核失败',
                'data' => $errorDetail,
                'statusCode' => 400,
            ];
        }

        // 检查是否已提交
        if ($this->hasVulnRecord($matchedVuln['id'])) {
            return [
                'success' => false,
                'message' => '该漏洞已提交过',
                'data' => ['already_submitted' => true],
                'statusCode' => 200,
            ];
        }

        // 写入记录
        $this->addVulnRecord($matchedVuln['id'], $matchedVuln['score']);

        // 计算星星状态
        $totalScore = $this->getTotalScore();
        $unlockedStars = $this->calculateUnlockedStars($totalScore);
        $this->updateStarStatus($unlockedStars);

        return [
            'success' => true,
            'message' => '漏洞审核通过',
            'data' => [
                'valid' => true,
                'score' => (int) $matchedVuln['score'],
                'message' => '漏洞审核通过',
                'totalScore' => $totalScore,
                'unlockedStars' => $unlockedStars,
                'vulnInfo' => [
                    'type' => $matchedVuln['type'],
                    'url' => $matchedVuln['url'],
                    'params' => $matchedVuln['params'],
                ],
            ],
            'statusCode' => 200,
        ];
    }

    /**
     * 处理获取星星状态请求
     *
     * @return array ['success' => bool, 'message' => string, 'data' => array, 'statusCode' => int]
     */
    public function handleGetStarStatusRequest()
    {
        $totalScore = $this->getTotalScore();
        $unlockedStars = $this->calculateUnlockedStars($totalScore);

        // 同步更新数据库中的星星状态
        $this->updateStarStatus($unlockedStars);

        return [
            'success' => true,
            'message' => '',
            'data' => [
                'total_score' => $totalScore,
                'unlocked_stars' => $unlockedStars,
                'score_thresholds' => $this->scoreThresholds,
                'total_stars' => count($this->scoreThresholds),
            ],
            'statusCode' => 200,
        ];
    }

    // ========================================================================
    // 响应输出辅助
    // ========================================================================

    /**
     * 将 API 处理器的返回结果输出为 JSON 响应
     *
     * @param array $result handleXxxRequest() 的返回值
     */
    public static function sendJsonResponse(array $result)
    {
        http_response_code(isset($result['statusCode']) ? (int) $result['statusCode'] : 200);
        header('Content-Type: application/json; charset=utf-8');
        header('HeavenlySecret: HeaSec');
        header('X-HeavenlySecret: HeaSec VulnManager API v1.0.0');

        echo json_encode([
            'success' => (bool) $result['success'],
            'message' => isset($result['message']) ? $result['message'] : '',
            'data' => isset($result['data']) ? $result['data'] : [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================================
    // 静态工具方法
    // ========================================================================

    /**
     * 规范化漏洞 URL
     *
     * @param string $url 原始 URL
     * @return string
     */
    public static function normalizeVulnUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === false || $path === '') {
            $path = $url;
        }

        $apiPos = strpos($path, '/api/');
        if ($apiPos !== false) {
            $path = substr($path, $apiPos);
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * 对漏洞参数进行规范化排序
     *
     * @param array $params 参数列表
     * @return array
     */
    public static function normalizeVulnParams(array $params)
    {
        $normalized = [];

        foreach ($params as $param) {
            if (!is_array($param)) {
                continue;
            }

            $name = isset($param['name']) ? trim((string) $param['name']) : '';
            $location = isset($param['location']) ? strtoupper(trim((string) $param['location'])) : '';

            if ($name === '' || $location === '') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'location' => $location,
            ];
        }

        usort($normalized, function ($left, $right) {
            $leftKey = $left['location'] . ':' . $left['name'];
            $rightKey = $right['location'] . ':' . $right['name'];
            return strcmp($leftKey, $rightKey);
        });

        return $normalized;
    }

    /**
     * 构建漏洞唯一键
     *
     * @param string $url    规范化后的URL
     * @param string $type   漏洞类型
     * @param array  $params 规范化后的参数列表
     * @return string
     */
    public static function buildVulnKey($url, $type, array $params)
    {
        $normalizedUrl = self::normalizeVulnUrl($url);
        $normalizedType = trim((string) $type);
        $normalizedParams = self::normalizeVulnParams($params);

        return sha1(json_encode([
            'url' => $normalizedUrl,
            'type' => $normalizedType,
            'params' => $normalizedParams,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 生成或获取当前会话的漏洞记录标识键
     *
     * @return string
     */
    public static function generateSessionRecordKey()
    {
        if (!isset($_SESSION['heasec_session_id']) || $_SESSION['heasec_session_id'] === '') {
            $_SESSION['heasec_session_id'] = bin2hex(random_bytes(16));
        }

        return $_SESSION['heasec_session_id'];
    }

    // ========================================================================
    // 数据库初始化 SQL 生成（供模板使用）
    // ========================================================================

    /**
     * 生成漏洞记录和星星状态表的 SQL 语句
     *
     * @param string $tablePrefix 表名前缀，如 'heasec_privesc_'
     * @return string SQL 语句
     */
    public static function generateInitSQL($tablePrefix)
    {
        $vulnRecordsTable = $tablePrefix . 'vuln_records';
        $starStatusTable = $tablePrefix . 'star_status';

        $sql = "-- 漏洞记录表（全局共享）\n";
        $sql .= "DROP TABLE IF EXISTS `{$vulnRecordsTable}`;\n";
        $sql .= "CREATE TABLE IF NOT EXISTS `{$vulnRecordsTable}` (\n";
        $sql .= "    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',\n";
        $sql .= "    `vuln_id` VARCHAR(100) NOT NULL COMMENT '漏洞标识',\n";
        $sql .= "    `score` INT NOT NULL COMMENT '漏洞得分',\n";
        $sql .= "    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',\n";
        $sql .= "    PRIMARY KEY (`id`),\n";
        $sql .= "    UNIQUE KEY `idx_vuln_id` (`vuln_id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='漏洞记录表';\n\n";

        $sql .= "-- 星星状态表（全局单条记录）\n";
        $sql .= "DROP TABLE IF EXISTS `{$starStatusTable}`;\n";
        $sql .= "CREATE TABLE IF NOT EXISTS `{$starStatusTable}` (\n";
        $sql .= "    `id` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '主键ID（固定为1）',\n";
        $sql .= "    `unlocked_stars` INT NOT NULL DEFAULT 0 COMMENT '已解锁的星星数量',\n";
        $sql .= "    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',\n";
        $sql .= "    PRIMARY KEY (`id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='星星状态表';\n";

        return $sql;
    }
}
