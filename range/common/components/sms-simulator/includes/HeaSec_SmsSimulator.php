<?php
/**
 * HeaSec天积安全团队 - 手机短信模拟器组件类
 * SMS Simulator Component Class
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */


/**
 * HeaSec_SmsSimulator 手机短信模拟器组件类
 */
class HeaSec_SmsSimulator {

    const VERSION = '1.0.0';
    const DB_NAME = 'heasec_common';
    const TABLE_PREFIX = 'heasec_sms_';

    /**
     * 获取默认手机号
     * @return array|false 默认手机号信息，不存在则返回false
     */
    public static function getDefaultPhone() {
        try {
            $sql = "SELECT * FROM " . self::TABLE_PREFIX . "simulator WHERE is_default = 1 LIMIT 1";
            $pdo = HeaSec_Database::getConnection(self::DB_NAME);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[HeaSec] getDefaultPhone failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取所有手机号列表
     * @return array 手机号列表
     */
    public static function getPhoneList() {
        try {
            $sql = "SELECT * FROM " . self::TABLE_PREFIX . "simulator ORDER BY is_default DESC, id ASC";
            $pdo = HeaSec_Database::getConnection(self::DB_NAME);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[HeaSec] getPhoneList failed: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * 根据ID获取手机号信息
     * @param int $phoneId 手机号ID
     * @return array|false 手机号信息，不存在则返回false
     */
    public static function getPhoneById($phoneId) {
        try {
            $sql = "SELECT * FROM " . self::TABLE_PREFIX . "simulator WHERE id = ? LIMIT 1";
            $pdo = HeaSec_Database::getConnection(self::DB_NAME);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($phoneId));
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[HeaSec] getPhoneById failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取手机号的短信列表
     * @param int $phoneId 手机号ID
     * @param int $limit 限制数量
     * @return array 短信列表
     */
    public static function getSmsList($phoneId, $limit = 100) {
        try {
            // 验证并清理limit参数
            $limit = intval($limit);
            if ($limit < 1) {
                $limit = 100;
            }
            if ($limit > 1000) {
                $limit = 1000; // 防止过大值
            }

            // PDO不支持在LIMIT中使用参数绑定，直接拼接（已验证为整数）
            $sql = "SELECT * FROM " . self::TABLE_PREFIX . "message WHERE simulator_id = ? ORDER BY created_at DESC LIMIT " . $limit;
            $pdo = HeaSec_Database::getConnection(self::DB_NAME);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($phoneId));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[HeaSec] getSmsList failed: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * 获取手机号的短信数量
     * @param int $phoneId 手机号ID
     * @return int 短信数量
     */
    public static function getSmsCount($phoneId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM " . self::TABLE_PREFIX . "message WHERE simulator_id = ?";
            $pdo = HeaSec_Database::getConnection(self::DB_NAME);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($phoneId));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['count']);
        } catch (Exception $e) {
            error_log('[HeaSec] getSmsCount failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 渲染管理页面资源
     * @param string $commonBasePath 公共组件基础路径
     * @return string HTML代码
     */
    public static function renderManageAssets($commonBasePath = null) {
        if ($commonBasePath === null) {
            $commonBasePath = isset($GLOBALS['commonBasePath']) ? $GLOBALS['commonBasePath'] : '../../../common/';
        }

        $basePath = $commonBasePath . 'components/sms-simulator/';
        $version = self::VERSION;

        $html = '';
        $html .= '<link rel="stylesheet" href="' . $basePath . 'css/heasec-sms-simulator.css?v=' . $version . '">' . "\n";
        $html .= '<script src="' . $basePath . 'js/heasec-sms-simulator.js?v=' . $version . '"></script>' . "\n";

        return $html;
    }

    /**
     * 获取组件信息
     * @return array 组件信息
     */
    public static function getComponentInfo() {
        return array(
            'name' => 'HeaSec SMS Simulator',
            'version' => self::VERSION,
            'team' => '天积安全 (HeavenlySecret)',
            'created' => '2026-01-06',
            'description' => '手机短信模拟器公共组件',
            'database' => self::DB_NAME,
            'table_prefix' => self::TABLE_PREFIX
        );
    }
}
?>
