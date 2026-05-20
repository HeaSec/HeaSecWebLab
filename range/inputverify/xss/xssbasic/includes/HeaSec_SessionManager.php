<?php
/**
 * HeaSec XSS基础靶场会话管理类
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

class HeaSec_SessionManager {
    private static $db = null;
    private static $sessionId = null;
    private static $progress = null;

    public static function init($db) {
        self::$db = $db;
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        self::$sessionId = session_id();
        self::loadProgress();
    }

    private static function loadProgress() {
        try {
            $stmt = self::$db->prepare("SELECT * FROM heasec_xssbasic_progress WHERE session_id = ? LIMIT 1");
            $stmt->execute([self::$sessionId]);
            self::$progress = $stmt->fetch(PDO::FETCH_ASSOC);

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

    private static function createProgress() {
        try {
            $stmt = self::$db->prepare(
                "INSERT INTO heasec_xssbasic_progress
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

    public static function completeLevel($level) {
        if (!self::$progress || !self::$db) {
            return false;
        }

        try {
            $fieldName = 'level' . $level . '_completed';

            $stmt = self::$db->prepare(
                "UPDATE heasec_xssbasic_progress
                SET {$fieldName} = 1, current_level = CASE WHEN ? > current_level THEN ? ELSE current_level END, updated_at = NOW()
                WHERE session_id = ?"
            );
            $nextLevel = $level < 3 ? $level + 1 : 1;
            $stmt->execute([$nextLevel, $nextLevel, self::$sessionId]);

            self::$progress[$fieldName] = 1;
            if ($nextLevel > self::$progress['current_level']) {
                self::$progress['current_level'] = $nextLevel;
            }

            self::recordAchievement($level);

            return true;
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in completeLevel: ' . $e->getMessage());
            return false;
        }
    }

    private static function recordAchievement($level) {
        try {
            $achievementName = 'level' . $level;
            $stmt = self::$db->prepare(
                "INSERT INTO heasec_xssbasic_records
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

    public static function getCurrentLevel() {
        return self::$progress ? intval(self::$progress['current_level']) : 1;
    }

    public static function isLevelCompleted($level) {
        if (!self::$progress) {
            return false;
        }
        $fieldName = 'level' . $level . '_completed';
        return isset(self::$progress[$fieldName]) && intval(self::$progress[$fieldName]) === 1;
    }

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

    public static function getStarCount() {
        if (!self::$db) {
            return 0;
        }
        try {
            $stmt = self::$db->query("SELECT COUNT(*) as count FROM heasec_xssbasic_records");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['count']);
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in getStarCount: ' . $e->getMessage());
            return 0;
        }
    }

    public static function resetProgress() {
        if (!self::$db || !self::$sessionId) {
            return false;
        }
        try {
            $stmt = self::$db->prepare("DELETE FROM heasec_xssbasic_progress WHERE session_id = ?");
            $stmt->execute([self::$sessionId]);
            self::$db->query("DELETE FROM heasec_xssbasic_records");
            self::loadProgress();
            return true;
        } catch (PDOException $e) {
            error_log('[HeaSec] Database error in resetProgress: ' . $e->getMessage());
            return false;
        }
    }

    public static function getLevelTitle($level) {
        $titles = [
            1 => '第一关',
            2 => '第二关',
            3 => '第三关'
        ];
        return isset($titles[$level]) ? $titles[$level] : '未知关卡';
    }

    public static function getLevelDescription($level) {
        $descriptions = [
            1 => '反射型 XSS - 在URL参数中构造恶意代码，当用户访问页面时被执行',
            2 => '存储型 XSS - 将恶意脚本存储到服务器的数据库中',
            3 => 'DOM 型 XSS - 基于DOM结构，无需向服务器发请求的XSS漏洞'
        ];
        return isset($descriptions[$level]) ? $descriptions[$level] : '';
    }
}
