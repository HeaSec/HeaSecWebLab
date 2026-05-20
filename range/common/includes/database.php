<?php
/**
 * HeaSec 数据库组件加载文件 (完全简化版)
 * 快速引入数据库连接组件
 *
 * @package HeaSec_Range_Database
 * @version 3.0.0 - 完全简化数据库分配策略
 * @author 天积安全 (HeavenlySecret)
 * @copyright 天积安全 (HeaSec) 2026
 *
 * 完全简化策略：
 * - 直接使用数据库名和完整表名
 * - 移除所有复杂的转换逻辑
 * - 提供清晰、高效的API接口
 * - 保留连接池和错误处理机制
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入数据库组件
require_once __DIR__ . '/HeaSec_Database.php';



/**
 * 快捷函数：获取数据库连接
 *
 * @param string $databaseName 数据库名称，直接使用不做转换
 * @param array $connectionConfig 可选的连接配置
 * @return PDO 数据库连接对象
 */
function heasec_db($databaseName = '', $connectionConfig = []) {
    return HeaSec_Database::getConnection($databaseName, $connectionConfig);
}

/**
 * 快捷函数：查询单行数据
 *
 * @param string $sql SQL语句
 * @param array $params 参数数组
 * @param string $databaseName 数据库名称
 * @return array|false 查询结果
 */
function heasec_fetch_one($sql, $params = [], $databaseName = '') {
    return HeaSec_DatabaseHelper::fetchOne($sql, $params, $databaseName);
}

/**
 * 快捷函数：查询多行数据
 *
 * @param string $sql SQL语句
 * @param array $params 参数数组
 * @param string $databaseName 数据库名称
 * @return array 查询结果数组
 */
function heasec_fetch_all($sql, $params = [], $databaseName = '') {
    return HeaSec_DatabaseHelper::fetchAll($sql, $params, $databaseName);
}

/**
 * 快捷函数：插入数据
 *
 * @param string $table 完整表名（包含前缀）
 * @param array $data 插入数据
 * @param string $databaseName 数据库名称
 * @return int|false 插入的ID或false
 */
function heasec_insert($table, $data, $databaseName = '') {
    try {
        $db = HeaSec_Database::getConnection($databaseName);

        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));

        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log('[HeaSec] Insert failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 快捷函数：更新数据
 *
 * @param string $table 完整表名（包含前缀）
 * @param array $data 更新数据
 * @param string $where WHERE条件
 * @param array $whereParams WHERE参数
 * @param string $databaseName 数据库名称
 * @return int|false 影响的行数或false
 */
function heasec_update($table, $data, $where, $whereParams = [], $databaseName = '') {
    try {
        $db = HeaSec_Database::getConnection($databaseName);

        $setClause = array();
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = ?";
        }
        $setClause = implode(',', $setClause);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));

        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log('[HeaSec] Update failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 快捷函数：删除数据
 *
 * @param string $table 完整表名（包含前缀）
 * @param string $where WHERE条件
 * @param array $params 参数数组
 * @param string $databaseName 数据库名称
 * @return int|false 影响的行数或false
 */
function heasec_delete($table, $where, $params = [], $databaseName = '') {
    try {
        $db = HeaSec_Database::getConnection($databaseName);

        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log('[HeaSec] Delete failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 快捷函数：执行原生SQL
 *
 * @param string $sql SQL语句
 * @param array $params 参数数组
 * @param string $databaseName 数据库名称
 * @return PDOStatement|false 执行结果
 */
function heasec_execute($sql, $params = [], $databaseName = '') {
    try {
        $db = HeaSec_Database::getConnection($databaseName);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log('[HeaSec] Execute failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 快捷函数：创建表（如果不存在）
 *
 * @param string $fullTableName 完整表名（包含前缀）
 * @param string $createSql 建表SQL语句（使用完整表名）
 * @param string $databaseName 数据库名称
 * @return bool 是否创建成功
 */
function heasec_create_table($fullTableName, $createSql, $databaseName = '') {
    try {
        $db = HeaSec_Database::getConnection($databaseName);

        // 检查表是否存在 - 使用INFORMATION_SCHEMA
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$fullTableName]);
        $result = $stmt->fetch();

        if ($result['cnt'] == 0) {
            // 表不存在，创建表
            $db->exec($createSql);
        }

        return true;
    } catch (Exception $e) {
        error_log('[HeaSec] Create table failed: ' . $e->getMessage());
        return false;
    }
}


?>