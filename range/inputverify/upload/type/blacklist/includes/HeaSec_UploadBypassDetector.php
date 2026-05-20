<?php
/**
 * HeaSec天积安全团队 - 文件上传黑名单绕过检测器
 * File Upload Blacklist Bypass Detector
 * 版本: v1.0.0
 * 创建日期: 2025-12-08
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

class HeaSec_UploadBypassDetector {

    /**
     * 数据库名称
     */
    private $databaseName = 'heasec_inputverify';

    /**
     * PHP黑名单扩展名（小写）
     */
    private $blacklist = ['.php'];

    /**
     * 绕过类型常量定义
     */
    const BYPASS_NONE = 0;
    const BYPASS_CASE_SENSITIVITY = 1;    // 大小写绕过
    const BYPASS_UNCOMMON_SUFFIX = 2;     // 非常规后缀名绕过
    const BYPASS_TRUNCATION = 3;          // 截断绕过

    /**
     * 绕过类型映射
     */
    private static $bypassTypeNames = [
        self::BYPASS_NONE => '无绕过',
        self::BYPASS_CASE_SENSITIVITY => '大小写绕过',
        self::BYPASS_UNCOMMON_SUFFIX => '非常规后缀名绕过',
        self::BYPASS_TRUNCATION => '截断绕过'
    ];

    /**
     * 非常规PHP后缀名列表
     */
    private $uncommonExtensions = [
        'php5', 'phtml', 'php3', 'php4', 'php7', 'phar', 'pht'
    ];

    /**
     * 获取数据库连接
     *
     * @return PDO 数据库连接对象
     */
    private function getDatabase() {
        // 移除静态连接，每次都获取新的连接避免游标冲突
        global $commonBasePath;
        require_once $commonBasePath . 'includes/database.php';
        return heasec_db($this->databaseName);
    }

    /**
     * 处理文件上传并检测绕过
     *
     * @param array $file $_FILES数组元素
     * @return array 检测结果
     */
    public function processUpload($file) {
        $result = [
            'success' => false,
            'bypass_type' => self::BYPASS_NONE,
            'bypass_name' => '',
            'message' => '',
            'filename' => $file['name'],
            'should_block' => false,
            'achievement' => false
        ];

        // 安全检查
        if (!$this->isValidUpload($file)) {
            $result['message'] = '无效的文件上传';
            $result['should_block'] = true;
            return $result;
        }

        // 检测绕过类型
        $bypassType = $this->detectBypassType($file['name']);
        $result['bypass_type'] = $bypassType;
        $result['bypass_name'] = self::$bypassTypeNames[$bypassType];

        // 判断是否为黑名单文件且未绕过
        if ($bypassType === self::BYPASS_NONE && $this->isBlacklisted($file['name'])) {
            $result['should_block'] = true;
            $result['message'] = '疑似上传恶意文件，请遵纪守法';
            return $result;
        }

        // 处理绕过成功的情况
        if ($bypassType > self::BYPASS_NONE) {
            $isAchievement = $this->recordBypassSuccess($bypassType, $file);
            $result['success'] = true;
            $result['achievement'] = $isAchievement;
            $result['message'] = $this->getBypassSuccessMessage($bypassType);
        } else {
            // 非PHP文件正常上传
            $result['success'] = true;
            $result['message'] = '文件上传成功（非PHP文件）';
        }

        return $result;
    }

    /**
     * 验证文件上传的有效性
     *
     * @param array $file 文件数组
     * @return bool 是否有效
     */
    private function isValidUpload($file) {
        // 检查文件是否存在
        if (!isset($file) || !is_array($file)) {
            return false;
        }

        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // 检查文件大小
        if ($file['size'] <= 0) {
            return false;
        }

        // 检查文件名
        if (empty($file['name'])) {
            return false;
        }

        return true;
    }

    /**
     * 检测绕过类型
     *
     * @param string $filename 文件名
     * @return int 绕过类型
     */
    private function detectBypassType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $lowerFilename = strtolower($filename);

        // 1. 大小写绕过检测
        if ($this->isCaseSensitiveBypass($filename)) {
            return self::BYPASS_CASE_SENSITIVITY;
        }

        // 2. 非常规后缀绕过检测
        if ($this->isUncommonSuffixBypass($extension)) {
            return self::BYPASS_UNCOMMON_SUFFIX;
        }

        // 3. 截断绕过检测
        if ($this->isTruncationBypass($filename)) {
            return self::BYPASS_TRUNCATION;
        }

        return self::BYPASS_NONE;
    }

    /**
     * 检测大小写绕过
     *
     * @param string $filename 文件名
     * @return bool 是否为大小写绕过
     */
    private function isCaseSensitiveBypass($filename) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $lowerFilename = strtolower($filename);
        $lowerExtension = strtolower($extension);

        // 检查是否为PHP文件但大小写不一致
        if (in_array('.' . $lowerExtension, $this->blacklist) &&
            $lowerFilename !== $filename) {
            return true;
        }

        return false;
    }

    /**
     * 检测非常规后缀绕过
     *
     * @param string $extension 文件扩展名
     * @return bool 是否为非常规后缀绕过
     */
    private function isUncommonSuffixBypass($extension) {
        return in_array(strtolower($extension), $this->uncommonExtensions);
    }

    /**
     * 检测截断绕过
     *
     * @param string $filename 文件名
     * @return bool 是否为截断绕过
     */
    private function isTruncationBypass($filename) {
        // 检测 %00 截断
        if (strpos($filename, '%00') !== false) {
            return true;
        }

        // 检测其他截断字符
        if (preg_match('/\.php%00/i', $filename)) {
            return true;
        }

        return false;
    }

    /**
     * 检查文件是否在黑名单中
     *
     * @param string $filename 文件名
     * @return bool 是否在黑名单中
     */
    private function isBlacklisted($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array('.' . $extension, $this->blacklist);
    }

    /**
     * 记录绕过成功
     *
     * @param int $bypassType 绕过类型
     * @param array $file 文件信息
     * @return bool 是否为新成就
     */
    private function recordBypassSuccess($bypassType, $file) {
        try {
            $db = $this->getDatabase();
            $bypassTypeName = self::$bypassTypeNames[$bypassType];

            // 检查是否已经存在该绕过类型
            $checkSql = "SELECT success_count FROM heasec_blacklist_bypass_records WHERE bypass_type = ?";
            $stmt = $db->prepare($checkSql);
            $stmt->execute([$bypassTypeName]);
            $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            $isAchievement = !$existingRecord || $existingRecord['success_count'] == 0;

            // 插入或更新记录
            $sql = "INSERT INTO heasec_blacklist_bypass_records
                    (bypass_type, filename, extension, file_size, success_count, last_success_at)
                    VALUES (?, ?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                    success_count = success_count + 1,
                    filename = VALUES(filename),
                    file_size = VALUES(file_size),
                    last_success_at = NOW()";

            $stmt = $db->prepare($sql);
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $stmt->execute([$bypassTypeName, $file['name'], $extension, $file['size']]);

            // 记录详细日志
            $this->recordSuccessLog($bypassTypeName, $file);

            return $isAchievement;

        } catch (Exception $e) {
            error_log('[HeaSec] Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 记录成功日志
     *
     * @param string $bypassType 绕过类型
     * @param array $file 文件信息
     */
    private function recordSuccessLog($bypassType, $file) {
        try {
            $db = $this->getDatabase();

            $sql = "INSERT INTO heasec_blacklist_success_log
                    (bypass_type, filename, file_size, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $bypassType,
                $file['name'],
                $file['size'],
                $_SERVER['REMOTE_ADDR'],
                isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ]);

        } catch (Exception $e) {
            error_log('[HeaSec] Log record error: ' . $e->getMessage());
        }
    }

    /**
     * 获取绕过成功消息
     *
     * @param int $bypassType 绕过类型
     * @return string 成功消息
     */
    private function getBypassSuccessMessage($bypassType) {
        switch ($bypassType) {
            case self::BYPASS_CASE_SENSITIVITY:
                return '恭喜你，成功通过大小写绕过黑名单检测';
            case self::BYPASS_UNCOMMON_SUFFIX:
                return '恭喜你，成功通过非常规后缀名上传了文件';
            case self::BYPASS_TRUNCATION:
                return '恭喜你，成功通过截断绕过上传了文件';
            default:
                return '恭喜你，成功绕过了黑名单检测';
        }
    }

    /**
     * 获取成就统计
     *
     * @return array 成就统计信息
     */
    public function getAchievementStats() {
        try {
            $db = $this->getDatabase();

            // 获取绕过类型数量
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM heasec_blacklist_bypass_records WHERE success_count > 0");
            $stmt->execute();
            $starCount = intval($stmt->fetchColumn());
            $stmt->closeCursor(); // 关闭游标，避免unbuffered query错误

            // 获取详细记录
            $stmt = $db->prepare("
                SELECT bypass_type, success_count, last_success_at
                FROM heasec_blacklist_bypass_records
                WHERE success_count > 0
                ORDER BY last_success_at DESC
            ");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // 关闭游标，避免unbuffered query错误

            // 转换记录格式
            $formattedRecords = [];
            foreach ($records as $record) {
                $formattedRecords[] = [
                    'name' => $record['bypass_type'],
                    'count' => $record['success_count'],
                    'time' => $record['last_success_at']
                ];
            }

            return [
                'starCount' => $starCount,
                'records' => $formattedRecords
            ];

        } catch (Exception $e) {
            error_log('[HeaSec] Achievement stats error: ' . $e->getMessage());
            return [
                'starCount' => 0,
                'records' => []
            ];
        }
    }

    /**
     * 获取绕过类型统计
     *
     * @return array 绕过类型统计
     */
    public function getBypassStats() {
        try {
            $db = $this->getDatabase();

            $stats = [];
            foreach (self::$bypassTypeNames as $type => $name) {
                if ($type === self::BYPASS_NONE) continue;

                $stmt = $db->prepare("SELECT success_count, last_success_at FROM heasec_blacklist_bypass_records WHERE bypass_type = ?");
                $stmt->execute([$name]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);

                $stats[$type] = [
                    'name' => $name,
                    'count' => $record ? intval($record['success_count']) : 0,
                    'last_success' => $record ? $record['last_success_at'] : null
                ];
            }

            return $stats;

        } catch (Exception $e) {
            error_log('[HeaSec] Bypass stats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取绕过类型名称
     *
     * @param int $bypassType 绕过类型
     * @return string 绕过类型名称
     */
    public static function getBypassTypeName($bypassType) {
        return isset(self::$bypassTypeNames[$bypassType]) ?
               self::$bypassTypeNames[$bypassType] : '未知类型';
    }

    /**
     * 获取所有绕过类型名称
     *
     * @return array 绕过类型名称数组
     */
    public static function getAllBypassTypeNames() {
        return self::$bypassTypeNames;
    }
}

?>