<?php
/**
 * HeaSec天积安全团队 - PHP表单处理示例
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 说明: 演示PHP如何接收和处理GET/POST表单数据
 */

// 初始化结果变量
$get_result = '';   // GET请求处理结果
$post_result = '';  // POST请求处理结果

// ==================== GET请求处理 ====================
// $_GET: PHP预定义的超全局变量，包含通过URL参数传递的数据
// isset(): 检查变量是否已设置且非NULL
if (isset($_GET['get_username']) && isset($_GET['get_age'])) {
    // htmlspecialchars(): 将特殊字符转换为HTML实体，防止XSS（跨站脚本）攻击
    // 参数1: 要转换的字符串
    // 参数2: 转换标志，ENT_QUOTES表示同时转换单引号和双引号
    // 参数3: 字符编码，指定为UTF-8
    $get_username = htmlspecialchars($_GET['get_username'], ENT_QUOTES, 'UTF-8');
    // intval(): 获取变量的整数值，确保数据类型安全
    $get_age = intval($_GET['get_age']);
    // 拼接结果字符串，使用<br>标签实现HTML换行
    $get_result = "GET请求接收成功！<br>用户名：" . $get_username . "<br>年龄：" . $get_age;
}

// ==================== POST请求处理 ====================
// $_SERVER['REQUEST_METHOD']: 获取当前请求的HTTP方法（GET/POST等）
// POST请求的数据不会显示在URL中，相对更安全
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_username']) && isset($_POST['post_email'])) {
    // htmlspecialchars(): 防止XSS攻击，转换特殊HTML字符
    $post_username = htmlspecialchars($_POST['post_username'], ENT_QUOTES, 'UTF-8');
    $post_email = htmlspecialchars($_POST['post_email'], ENT_QUOTES, 'UTF-8');
    // 拼接结果字符串
    $post_result = "POST请求接收成功！<br>用户名：" . $post_username . "<br>邮箱：" . $post_email;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP表单处理示例</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <!-- GET表单 -->
    <div class="form-container">
        <h2>📝 GET表单示例</h2>
        <form method="get">
            <div class="form-group">
                <label>用户名：</label>
                <input type="text" name="get_username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label>年龄：</label>
                <input type="number" name="get_age" placeholder="请输入年龄" min="1" max="150" required>
            </div>
            <button type="submit">提交GET请求</button>
        </form>
        <?php if ($get_result): ?>
        <div class="result">
            <strong>📤 GET请求处理结果：</strong>
            <?php echo $get_result; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- POST表单 -->
    <div class="form-container">
        <h2>📝 POST表单示例</h2>
        <form method="post">
            <div class="form-group">
                <label>用户名：</label>
                <input type="text" name="post_username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label>邮箱：</label>
                <input type="email" name="post_email" placeholder="请输入邮箱地址" required>
            </div>
            <button type="submit">提交POST请求</button>
        </form>
        <?php if ($post_result): ?>
        <div class="result">
            <strong>📤 POST请求处理结果：</strong>
            <?php echo $post_result; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
