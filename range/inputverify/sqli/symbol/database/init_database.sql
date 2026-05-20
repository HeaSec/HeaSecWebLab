-- ============================================================
-- HeaSec天积安全团队 - SQL特殊字符过滤靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli
-- 表名前缀: heasec_symbol_
-- 说明: 本脚本可重复执行（使用 DROP TABLE IF EXISTS）
-- 注意: 本脚本只创建本靶场的表，不影响同数据库下其他靶场的表
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- -----------------------------------------------------------
-- 服务器信息表（第一关）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_symbol_servers;
CREATE TABLE heasec_symbol_servers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    hostname VARCHAR(100) NOT NULL COMMENT '主机名',
    ip_address VARCHAR(50) NOT NULL COMMENT 'IP地址',
    status VARCHAR(20) DEFAULT '运行中' COMMENT '状态',
    secret_key VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务器信息表（第一关）';

INSERT INTO heasec_symbol_servers (hostname, ip_address, status, secret_key) VALUES
('web-prod-01', '192.168.1.10', '运行中', ''),
('db-master-01', '192.168.1.20', '运行中', ''),
('app-server-01', '192.168.1.30', '维护中', ''),
('cache-redis-01', '192.168.1.40', '运行中', ''),
('mail-server-01', '192.168.1.50', '已停止', '');

-- -----------------------------------------------------------
-- 员工信息表（第二关）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_symbol_employees;
CREATE TABLE heasec_symbol_employees (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT '姓名',
    department VARCHAR(50) NOT NULL COMMENT '部门',
    position VARCHAR(50) DEFAULT NULL COMMENT '职位',
    access_token VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='员工信息表（第二关）';

INSERT INTO heasec_symbol_employees (name, department, position, access_token) VALUES
('张三', '技术部', '开发工程师', ''),
('李四', '运维部', '运维工程师', ''),
('王五', '安全部', '安全分析师', ''),
('赵六', '产品部', '产品经理', ''),
('钱七', '测试部', '测试工程师', '');

-- -----------------------------------------------------------
-- 安全告警表（第三关）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_symbol_alerts;
CREATE TABLE heasec_symbol_alerts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    alert_name VARCHAR(100) NOT NULL COMMENT '告警名称',
    severity VARCHAR(20) DEFAULT '中' COMMENT '严重程度',
    alert_type VARCHAR(50) NOT NULL COMMENT '告警类型',
    auth_code VARCHAR(50) NOT NULL DEFAULT '' COMMENT '认证码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='安全告警表（第三关）';

INSERT INTO heasec_symbol_alerts (alert_name, severity, alert_type, auth_code) VALUES
('CPU使用率过高', '高', 'performance', ''),
('异常登录检测', '严重', 'secret', ''),
('磁盘空间不足', '中', 'system', ''),
('网络流量异常', '高', 'network', ''),
('服务端口扫描', '低', 'info', '');
