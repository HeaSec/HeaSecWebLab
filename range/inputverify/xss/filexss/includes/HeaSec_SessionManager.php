<?php
/**
 * HeaSec 文件相关XSS靶场会话管理类
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 管理文件相关XSS靶场的用户会话和关卡进度
 */

class HeaSec_SessionManager {

    /**
     * @var PDO 数据库连接
     */
    private static $db = null;

    /**
     * @var string 当前会话ID
     */
    private static $sessionId = null;

    /**
     * @var array 关卡进度数据
     */
    private static $progress = null;

    /**
     * 初始化会话管理器
     *
     * @param PDO $db 数据库连接
     */
    public static function init($db) {
        self::$db = $db;

        // 启动PHP会话
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$sessionId = session_id();

        // 加载或创建用户进度
        self::loadProgress();
    }

    /**
     * 加载用户进度数据
     */
    private static function loadProgress() {
        try {
            $stmt = self::$db->prepare(
                "SELECT * FROM heasec_filexss_progress WHERE session_id = ? LIMIT 1"
            );
            $stmt->execute([self::$sessionId]);
            self::$progress = $stmt->fetch(PDO::FETCH_ASSOC);

            // 如果不存在进度记录，创建新记录
            if (!self::$progress) {
                self::createProgress();
            }
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in loadProgress: ' . $e->getMessage());
            self::$progress = [
                'current_level' => 1,
                'level1_completed' => 0,
                'level2_completed' => 0,
                'level3_completed' => 0
            ];
        }
    }

    /**
     * 创建新的进度记录
     */
    private static function createProgress() {
        try {
            $stmt = self::$db->prepare(
                "INSERT INTO heasec_filexss_progress
                (session_id, current_level, level1_completed, level2_completed, level3_completed)
                VALUES (?, 1, 0, 0, 0)"
            );
            $stmt->execute([self::$sessionId]);

            self::$progress = [
                'id' => self::$db->lastInsertId(),
                'session_id' => self::$sessionId,
                'current_level' => 1,
                'level1_completed' => 0,
                'level2_completed' => 0,
                'level3_completed' => 0
            ];
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in createProgress: ' . $e->getMessage());
        }
    }

    /**
     * 标记关卡为已完成
     *
     * @param int $level 关卡编号（1, 2, 3）
     * @return bool 是否成功更新
     */
    public static function completeLevel($level) {
        if (!self::$progress || !self::$db) {
            return false;
        }

        try {
            $fieldName = 'level' . $level . '_completed';

            // 更新数据库
            $stmt = self::$db->prepare(
                "UPDATE heasec_filexss_progress
                SET {$fieldName} = 1, current_level = ?, updated_at = NOW()
                WHERE session_id = ?"
            );
            $nextLevel = $level < 3 ? $level + 1 : 1;
            $stmt->execute([$nextLevel, self::$sessionId]);

            // 更新内存中的进度
            self::$progress[$fieldName] = 1;
            self::$progress['current_level'] = $nextLevel;

            // 记录成就
            self::recordAchievement($level);

            return true;
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in completeLevel: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 记录成就到数据库
     *
     * @param int $level 关卡编号
     */
    private static function recordAchievement($level) {
        try {
            $achievementName = 'level' . $level;

            // 插入或更新成就记录
            $stmt = self::$db->prepare(
                "INSERT INTO heasec_filexss_records
                (achievement, success_count, last_success_at)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = NOW()"
            );
            $stmt->execute([$achievementName]);
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in recordAchievement: ' . $e->getMessage());
        }
    }

    /**
     * 获取当前关卡
     *
     * @return int 当前关卡编号
     */
    public static function getCurrentLevel() {
        return self::$progress ? intval(self::$progress['current_level']) : 1;
    }

    /**
     * 检查关卡是否已完成
     *
     * @param int $level 关卡编号
     * @return bool 是否已完成
     */
    public static function isLevelCompleted($level) {
        if (!self::$progress) {
            return false;
        }
        $fieldName = 'level' . $level . '_completed';
        return isset(self::$progress[$fieldName]) && intval(self::$progress[$fieldName]) === 1;
    }

    /**
     * 获取已完成的关卡数量（用于星星系统）
     *
     * @return int 已完成关卡数量
     */
    public static function getCompletedLevelsCount() {
        if (!self::$progress) {
            return 0;
        }

        $count = 0;
        for ($i = 1; $i <= 3; $i++) {
            if (self::isLevelCompleted($i)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 获取星星数量（从成就记录表获取）
     *
     * @return int 星星数量
     */
    public static function getStarCount() {
        if (!self::$db) {
            return 0;
        }

        try {
            $stmt = self::$db->query(
                "SELECT COUNT(*) as count FROM heasec_filexss_records"
            );
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['count']);
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in getStarCount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 重置用户进度
     *
     * @return bool 是否成功重置
     */
    public static function resetProgress() {
        if (!self::$db || !self::$sessionId) {
            return false;
        }

        try {
            // 重置进度表
            $stmt = self::$db->prepare(
                "DELETE FROM heasec_filexss_progress WHERE session_id = ?"
            );
            $stmt->execute([self::$sessionId]);

            // 重置成就表
            $stmt = self::$db->prepare(
                "DELETE FROM heasec_filexss_records"
            );
            $stmt->execute();

            // 重新初始化
            self::loadProgress();

            return true;
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in resetProgress: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取关卡标题
     *
     * @param int $level 关卡编号
     * @return string 关卡标题
     */
    public static function getLevelTitle($level) {
        $titles = [
            1 => '第一关 · 图形审核员',
            2 => '第二关 · 文档审核员',
            3 => '第三关 · 预览审核员'
        ];
        return isset($titles[$level]) ? $titles[$level] : '未知关卡';
    }

    /**
     * 获取关卡描述
     *
     * @param int $level 关卡编号
     * @return string 关卡描述
     */
    public static function getLevelDescription($level) {
        $descriptions = [
            1 => 'SVG矢量图形上传功能已上线，系统会检查文件安全性。',
            2 => 'PDF文档预览功能已上线，支持各种版本的PDF文件。',
            3 => '图片预览功能已上线，我们严格过滤了所有危险关键词。'
        ];
        return isset($descriptions[$level]) ? $descriptions[$level] : '';
    }
}
