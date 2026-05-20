-- ============================================================
-- HeaSec天积安全团队 - SQL不同语句注入靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli（与同分类下其他SQL注入靶场共享）
-- 表名前缀: heasec_cuosi_
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- 用户表（第一关：UPDATE注入+报错注入）
DROP TABLE IF EXISTS heasec_cuosi_users;
CREATE TABLE heasec_cuosi_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    password VARCHAR(100) NOT NULL COMMENT '密码',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表（第一关）';

INSERT INTO heasec_cuosi_users (username, password, role, email) VALUES
('test', 'test123', 'user', 'test@heasec.com'),
('user', 'user123', 'user', 'user@heasec.com'),
('guest', 'guest123', 'user', 'guest@heasec.com');

-- 留言表（第二关：INSERT注入+布尔盲注）
DROP TABLE IF EXISTS heasec_cuosi_messages;
CREATE TABLE heasec_cuosi_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '发布用户ID',
    content VARCHAR(500) NOT NULL COMMENT '留言内容',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言表（第二关）';

INSERT INTO heasec_cuosi_messages (user_id, content) VALUES
(1, '欢迎来到天积社区！'),
(2, '大家好，我是新用户'),
(3, '今天天气不错');

-- 商品表（第三关：ORDER BY注入+布尔盲注）
DROP TABLE IF EXISTS heasec_cuosi_products;
CREATE TABLE heasec_cuosi_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名称',
    price DECIMAL(10,2) DEFAULT NULL COMMENT '价格',
    stock INT UNSIGNED DEFAULT 0 COMMENT '库存',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态（1=上架，0=下架）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表（第三关）';

INSERT INTO heasec_cuosi_products (name, price, stock, status) VALUES
('天积元宝', 100.00, 50, 1),
('天积小元宝', 50.00, 100, 1),
('天积铜钱', 10.00, 200, 1),
('天积令牌', 200.00, 30, 1),
('天积护身符', 150.00, 20, 1),
('天积秘籍', 300.00, 10, 1);

-- 密码存储说明：所有关卡通关密码均存储在 config/ 目录下的文件中
-- L1: config/secret.php（键名: level1_pass）
-- L2: config/secret2.php（键名: level2_pass）
-- L3: config/secret3.php（键名: level3_pass）
-- 文件存储方式可防止通过报错注入直接提取密码
