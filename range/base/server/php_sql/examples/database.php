<?php
/**
 * HeaSec天积安全团队 - PHP数据库处理示例
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 说明: 演示PHP使用PDO进行数据库操作（CRUD）
 */

// ==================== 数据库配置 ====================
// 定义数据库连接常量，使用常量可以避免配置被意外修改
define('DB_HOST', 'localhost');      // 数据库服务器地址
define('DB_USER', 'root');           // 数据库用户名
define('DB_PASS', 'root');           // 数据库密码
define('DB_NAME', 'heasec_base');    // 数据库名称
define('DB_CHARSET', 'utf8mb4');     // 字符集，utf8mb4支持完整的UTF-8字符（包括emoji）

// ==================== 数据库连接 ====================
try {
    // 构建数据源名称（DSN），指定数据库类型、主机、数据库名和字符集
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    // 创建PDO实例，并配置以下关键属性：
    // 1. ATTR_ERRMODE: 设置为ERRMODE_EXCEPTION，错误时抛出异常（最佳实践）
    // 2. ATTR_DEFAULT_FETCH_MODE: 设置为FETCH_ASSOC，默认返回关联数组（字段名=>值）
    // 3. ATTR_EMULATE_PREPARES: 设置为false，使用真正的预处理语句（防止SQL注入）
    $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ));
} catch (PDOException $e) {
    // 捕获PDO异常，输出错误信息并终止脚本
    // 在生产环境中，应该记录错误日志而不是直接显示给用户
    die("数据库连接失败: " . $e->getMessage());
}

// ==================== 创建数据表 ====================
// 定义创建表的SQL语句，使用CREATE TABLE IF NOT EXISTS避免重复创建时出错
$createTableSQL = "
    CREATE TABLE IF NOT EXISTS heasec_server_sql (
        id INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID，自增主键',
        username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名，唯一',
        email VARCHAR(100) NOT NULL COMMENT '邮箱地址',
        age INT DEFAULT 18 COMMENT '年龄，默认18',
        status TINYINT DEFAULT 1 COMMENT '状态：1启用 0禁用',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PHP服务端语言基础示例表'
";

try {
    // exec()方法用于执行不返回结果的SQL语句（如CREATE、DROP、INSERT等）
    $pdo->exec($createTableSQL);
} catch (PDOException $e) {
    die("创建表失败: " . $e->getMessage());
}

// ==================== 处理表单提交 ====================
// 初始化消息变量，用于向用户显示操作结果
$message = '';      // 消息内容
$messageType = '';  // 消息类型（success/error/info）

// ==================== 插入数据（CREATE） ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert') {
    // 获取并清理POST数据
    // trim(): 去除字符串首尾的空白字符
    // ?? (空合并运算符): PHP7+语法，这里使用传统兼容写法处理未定义的键
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = intval($_POST['age'] ?? 18);  // intval(): 将值转换为整数

    // 验证必填字段
    if (empty($username) || empty($email)) {
        $message = "用户名和邮箱不能为空";
        $messageType = 'error';
    } else {
        try {
            // prepare()方法预处理SQL语句，使用命名占位符（:username等）防止SQL注入
            // 这是数据库操作的安全最佳实践
            $stmt = $pdo->prepare("INSERT INTO heasec_server_sql (username, email, age) VALUES (:username, :email, :age)");

            // execute()执行预处理语句，传入参数数组（关联数组键名与占位符对应）
            $result = $stmt->execute(array(
                ':username' => $username,
                ':email' => $email,
                ':age' => $age
            ));

            if ($result) {
                $message = "用户添加成功！";
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            // 错误代码23000表示违反了唯一性约束（用户名重复）
            if ($e->getCode() == 23000) {
                $message = "用户名已存在，请使用其他用户名";
            } else {
                $message = "添加失败: " . $e->getMessage();
            }
            $messageType = 'error';
        }
    }
}

// ==================== 更新数据（UPDATE） ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // 获取并验证参数
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = intval($_POST['age'] ?? 18);
    $status = intval($_POST['status'] ?? 1);

    // 参数验证：确保ID有效且必填字段不为空
    if ($id <= 0 || empty($username) || empty($email)) {
        $message = "参数不完整";
        $messageType = 'error';
    } else {
        try {
            // 准备UPDATE语句，使用WHERE子句确保只更新指定记录
            $stmt = $pdo->prepare("UPDATE heasec_server_sql SET username = :username, email = :email, age = :age, status = :status WHERE id = :id");
            $result = $stmt->execute(array(
                ':username' => $username,
                ':email' => $email,
                ':age' => $age,
                ':status' => $status,
                ':id' => $id
            ));

            // rowCount()返回受影响的行数，用于判断是否真的更新了数据
            if ($result && $stmt->rowCount() > 0) {
                $message = "用户更新成功！";
                $messageType = 'success';
            } else {
                $message = "没有数据被更新";
                $messageType = 'info';
            }
        } catch (PDOException $e) {
            $message = "更新失败: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ==================== 删除数据（DELETE） ====================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($id > 0) {
        try {
            // 准备DELETE语句，务必使用WHERE子句指定要删除的记录
            // 注意：没有WHERE子句的DELETE会删除表中所有数据！
            $stmt = $pdo->prepare("DELETE FROM heasec_server_sql WHERE id = :id");
            $result = $stmt->execute(array(':id' => $id));

            // 检查是否真的删除了数据
            if ($result && $stmt->rowCount() > 0) {
                $message = "用户删除成功！";
                $messageType = 'success';
            } else {
                $message = "用户不存在";
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            $message = "删除失败: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ==================== 查询数据（READ） ====================
// 从URL参数中获取搜索关键词（如：?search=admin）
// isset(): 检查参数是否存在，避免未定义索引警告
// trim(): 去除关键词首尾的空白字符，防止用户输入无意义空格
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$users = array();
try {
    if (!empty($searchKeyword)) {
        // ========== 带搜索关键词的查询 ==========
        // 使用LIKE运算符进行模糊匹配
        // LIKE: SQL的模式匹配运算符，支持通配符
        // %: 匹配任意长度任意字符（0个或多个字符）
        // _: 匹配单个字符（本例未使用）
        // 示例：keyword='%admin%' 会匹配 'admin'、'administrator'、'myadmin' 等
        $stmt = $pdo->prepare("SELECT * FROM heasec_server_sql WHERE username LIKE :keyword ORDER BY id DESC");
        // 在关键词前后添加%通配符，实现包含匹配（不仅限于完全匹配）
        $keywordParam = '%' . $searchKeyword . '%';
        $stmt->execute(array(':keyword' => $keywordParam));
    } else {
        // ========== 无搜索关键词时，查询全部数据 ==========
        // 直接执行SQL查询，获取所有用户记录
        // ORDER BY id DESC: 按ID降序排列，最新添加的用户显示在最前面
        $stmt = $pdo->query("SELECT * FROM heasec_server_sql ORDER BY id DESC");
    }
    // fetchAll()获取所有结果行，返回二维数组
    // 每个数组元素代表一行记录（一个用户），内部是关联数组（字段名=>值）
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "查询失败: " . $e->getMessage();
    $messageType = 'error';
}

// ==================== 获取单个用户（用于编辑） ====================
$editUser = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // 准备查询单个用户的SQL语句
        $stmt = $pdo->prepare("SELECT * FROM heasec_server_sql WHERE id = :id");
        $stmt->execute(array(':id' => $id));
        // fetch()获取单行结果，返回一维数组；如果没有结果则返回false
        $editUser = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "获取用户信息失败: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP数据库处理示例</title>
    <link rel="stylesheet" href="css/database.css">
</head>
<body>
    <?php if ($message): ?>
    <!-- 显示操作结果消息 -->
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- 添加用户表单 -->
    <div class="container">
        <h2>➕ 添加用户</h2>
        <form method="post">
            <input type="hidden" name="action" value="insert">
            <div class="form-group">
                <label>用户名：</label>
                <input type="text" name="username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label>邮箱：</label>
                <input type="email" name="email" placeholder="请输入邮箱" required>
            </div>
            <div class="form-group">
                <label>年龄：</label>
                <input type="number" name="age" value="18" min="1" max="150" required>
            </div>
            <button type="submit">添加用户</button>
        </form>
    </div>

    <!-- 编辑用户表单 -->
    <?php if ($editUser): ?>
    <!-- 当有用户需要编辑时显示此表单 -->
    <div class="container">
        <h2>✏️ 编辑用户</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
            <div class="form-group">
                <label>用户名：</label>
                <!-- htmlspecialchars()将特殊字符转换为HTML实体，防止XSS攻击 -->
                <input type="text" name="username" value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
            </div>
            <div class="form-group">
                <label>邮箱：</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>年龄：</label>
                <input type="number" name="age" value="<?php echo $editUser['age']; ?>" min="1" max="150" required>
            </div>
            <div class="form-group">
                <label>状态：</label>
                <select name="status">
                    <!-- 三元运算符根据状态值选择对应的选项 -->
                    <option value="1" <?php echo $editUser['status'] == 1 ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo $editUser['status'] == 0 ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            <button type="submit" class="btn-update">更新用户</button>
            <a href="?" class="btn-link btn-cancel">取消</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- 用户列表 -->
    <div class="container">
        <h2>👥 用户列表</h2>

        <!-- ==================== 搜索框区域 ==================== -->
        <!-- method="get": 使用GET方法提交，搜索关键词会出现在URL中（如：?search=admin）
             这样用户可以分享搜索链接，也方便浏览器缓存记录
        -->
        <form method="get" class="search-form">
            <!-- 如果当前处于编辑模式，需要保持编辑状态 -->
            <!-- 隐藏字段用于传递额外的参数，不在界面上显示 -->
            <?php if ($editUser): ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
            <?php endif; ?>

            <div class="search-group">
                <!-- 搜索输入框
                     placeholder: 输入框为空时显示的提示文本
                     value: 显示当前搜索关键词（如果有），方便用户查看和修改
                     htmlspecialchars(): 将特殊字符转义为HTML实体，防止XSS攻击
                -->
                <input type="text" name="search" placeholder="搜索用户名..." value="<?php echo htmlspecialchars($searchKeyword); ?>" class="search-input">

                <!-- 搜索提交按钮 -->
                <button type="submit" class="btn-search">搜索</button>

                <!-- 清除搜索按钮
                     仅在有搜索关键词时显示
                     href="?": 跳转到当前页面不带任何参数，相当于重置搜索
                -->
                <?php if (!empty($searchKeyword)): ?>
                <a href="?" class="btn-clear">清除搜索</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- ==================== 搜索结果提示 ==================== -->
        <!-- 仅在有搜索关键词时显示 -->
        <!-- count($users): 统计搜索结果数量 -->
        <?php if (!empty($searchKeyword)): ?>
        <p class="search-info">搜索结果：找到 <?php echo count($users); ?> 个匹配 "<?php echo htmlspecialchars($searchKeyword); ?>" 的用户</p>
        <?php endif; ?>

        <?php if (!empty($users)): ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>年龄</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <!-- foreach循环遍历用户数组，每行显示一个用户 -->
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo $user['age']; ?></td>
                    <td><?php echo $user['status'] == 1 ? '启用' : '禁用'; ?></td>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="?action=edit&amp;id=<?php echo $user['id']; ?>">编辑</a>
                        <a href="?action=delete&amp;id=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('确定删除？')">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <p>暂无用户数据</p>
        <?php endif; ?>
    </div>
</body>
</html>
