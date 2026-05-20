<?php
// HTTP 500错误测试页面
// 故意触发500内部服务器错误

header('HTTP/1.1 500 Internal Server Error');
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - 内部服务器错误</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
            margin: 20px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #e53e3e;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .error-title {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 15px;
        }
        .error-description {
            font-size: 16px;
            color: #718096;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .back-button {
            display: inline-block;
            background: #4299e1;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        .back-button:hover {
            background: #3182ce;
        }
        .error-details {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }
        .error-details h4 {
            color: #2d3748;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .error-details p {
            color: #4a5568;
            margin: 5px 0;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <h1 class="error-title">内部服务器错误</h1>
        <p class="error-description">
            抱歉，服务器在处理您的请求时遇到了内部错误。<br>
            这是一个用于测试HTTP 500错误状态的演示页面。
        </p>

        <div class="error-details">
            <h4>错误信息：</h4>
            <p>状态码: 500 Internal Server Error</p>
            <p>错误类型: 服务器内部错误</p>
            <p>时间: <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>请求URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
        </div>

        <a href="javascript:history.back();" class="back-button">返回上一页</a>
        <a href="index.php" class="back-button" style="margin-left: 10px;">返回首页</a>
    </div>
</body>
</html>