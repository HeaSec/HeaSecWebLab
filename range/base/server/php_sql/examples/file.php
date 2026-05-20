<?php
/**
 * HeaSec天积安全团队 - PHP文件处理示例
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 说明: 演示PHP的文件上传、创建、读取、下载和删除操作
 */

// ==================== 初始化配置 ====================
// __DIR__: 当前脚本所在目录的绝对路径
$uploadDir = __DIR__ . '/uploads/';  // 上传文件存储目录
$message = '';      // 操作结果消息
$messageType = '';  // 消息类型（success/error）

// ==================== 创建上传目录 ====================
// 检查目录是否存在，不存在则创建
// file_exists(): 检查文件或目录是否存在
if (!file_exists($uploadDir)) {
    // mkdir(): 创建目录
    // 参数1: 目录路径
    // 参数2: 权限（0777表示最大权限，实际权限会受到umask影响）
    // 参数3: true表示递归创建（如果父目录不存在也会创建）
    mkdir($uploadDir, 0777, true);
}

// ==================== 处理文件上传 ====================
// $_FILES: PHP预定义的超全局变量，包含通过HTTP POST上传的文件信息
if (isset($_POST['upload']) && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    // $file['error']: 上传状态码，UPLOAD_ERR_OK(0)表示上传成功
    if ($file['error'] === UPLOAD_ERR_OK) {
        // basename(): 返回路径中的文件名部分，防止路径遍历攻击
        $filename = basename($file['name']);
        $filepath = $uploadDir . $filename;
        // move_uploaded_file(): 将上传的文件从临时目录移动到指定位置
        // 这个函数会检查文件是否是合法的上传文件，增加安全性
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $message = "文件上传成功：" . $filename;
            $messageType = 'success';
        } else {
            $message = "文件上传失败";
            $messageType = 'error';
        }
    }
}

// ==================== 处理文件创建 ====================
if (isset($_POST['create_file']) && isset($_POST['filename']) && isset($_POST['content'])) {
    $filename = basename($_POST['filename']);  // 获取文件名，防止路径遍历
    $filepath = $uploadDir . $filename;
    $content = $_POST['content'];  // 文件内容
    // file_put_contents(): 将内容写入文件
    // 返回写入的字节数，失败时返回false
    if (file_put_contents($filepath, $content) !== false) {
        $message = "文件创建成功：" . $filename;
        $messageType = 'success';
    } else {
        $message = "文件创建失败";
        $messageType = 'error';
    }
}

// ==================== 处理文件读取 ====================
$fileContent = '';
if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['file'])) {
    $filename = basename($_GET['file']);  // 获取文件名，防止路径遍历
    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        // file_get_contents(): 将整个文件读入字符串
        $fileContent = file_get_contents($filepath);
        $message = "文件读取成功：" . $filename;
        $messageType = 'success';
    } else {
        $message = "文件不存在";
        $messageType = 'error';
    }
}

// ==================== 处理文件下载 ====================
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['file'])) {
    $filename = basename($_GET['file']);  // 获取文件名，防止路径遍历
    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        // 设置HTTP响应头，告诉浏览器这是一个下载文件
        header('Content-Type: application/octet-stream');  // 设置内容类型为二进制流
        header('Content-Disposition: attachment; filename="' . $filename . '"');  // 指定下载文件名
        header('Content-Length: ' . filesize($filepath));  // 设置文件大小
        // readfile(): 读取文件并直接输出到浏览器
        readfile($filepath);
        exit;  // 下载后退出脚本，避免继续输出HTML
    }
}

// ==================== 处理文件删除 ====================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $filename = basename($_GET['file']);  // 获取文件名，防止路径遍历
    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        // unlink(): 删除文件
        unlink($filepath);
        $message = "文件删除成功：" . $filename;
        $messageType = 'success';
    } else {
        $message = "文件不存在";
        $messageType = 'error';
    }
}

// ==================== 获取文件列表 ====================
// scandir(): 列出指定路径中的文件和目录，返回数组
$files = scandir($uploadDir);
// array_diff(): 计算数组的差集，移除'.'（当前目录）和'..'（父目录）
$files = array_diff($files, array('.', '..'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP文件处理示例</title>
    <link rel="stylesheet" href="css/file.css">
</head>
<body>
    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- 文件上传 -->
    <div class="container">
        <h2>📤 文件上传</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>选择文件：</label>
                <input type="file" name="file" required>
            </div>
            <button type="submit" name="upload">上传文件</button>
        </form>
    </div>

    <!-- 文件创建 -->
    <div class="container">
        <h2>📝 文件创建</h2>
        <form method="post">
            <div class="form-group">
                <label>文件名：</label>
                <input type="text" name="filename" placeholder="example.txt" required>
            </div>
            <div class="form-group">
                <label>文件内容：</label>
                <textarea name="content" placeholder="输入文件内容..." required></textarea>
            </div>
            <button type="submit" name="create_file">创建文件</button>
        </form>
    </div>

    <!-- 文件列表 -->
    <div class="container">
        <h2>📂 文件列表</h2>
        <?php if (!empty($files)): ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>文件名</th>
                    <th>文件大小</th>
                    <th>修改时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <!-- 遍历文件列表数组，逐个输出文件信息 --> 
                <?php foreach ($files as $file): ?>
                <tr>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td><?php echo filesize($uploadDir . $file); ?> 字节</td>
                    <td><?php echo date('Y-m-d H:i:s', filemtime($uploadDir . $file)); ?></td>
                    <td>
                        <a href="?action=read&amp;file=<?php echo urlencode($file); ?>">读取</a>
                        <a href="?action=download&amp;file=<?php echo urlencode($file); ?>">下载</a>
                        <a href="?action=delete&amp;file=<?php echo urlencode($file); ?>" class="btn-delete" onclick="return confirm('确定删除？')">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <p>暂无文件</p>
        <?php endif; ?>
    </div>

    <!-- 文件内容显示，如果有内容则显示 -->
    <?php if ($fileContent !== ''): ?>
    <div class="container">
        <h2>📖 文件内容</h2>
        <div class="file-content"><?php echo htmlspecialchars($fileContent); ?></div>
    </div>
    <?php endif; ?>
</body>
</html>
