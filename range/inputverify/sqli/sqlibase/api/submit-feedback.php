<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场提交反馈接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    if (!sqlibase_is_logged_in()) {
        sqlibase_json_error('请先登录', 401);
    }

    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : '';
    $content     = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($category_id === '' || $content === '') {
        sqlibase_json_error('请填写完整信息');
    }

    $pdo = sqlibase_get_pdo();

    $check_sql = "SELECT * FROM heasec_sqlibase_categories WHERE id=($category_id)";

    try {
        $stmt = $pdo->query($check_sql);
        if (!$stmt || !$stmt->fetch()) {
            sqlibase_json_error('请选择有效的分类');
        }
    } catch (PDOException $e) {
        sqlibase_json_error('请选择有效的分类');
    }

    // 处理文件上传（可选）
    $screenshot_path = null;
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $error = sqlibase_validate_upload_file($_FILES['screenshot']);
        if ($error !== null) {
            sqlibase_json_error($error);
        }

        sqlibase_ensure_screenshot_directory();
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['screenshot']['tmp_name']);
        finfo_close($finfo);
        $extension = sqlibase_get_extension_by_mime($mimeType);
        $filename = sqlibase_generate_screenshot_filename($extension);
        $targetPath = sqlibase_get_screenshot_directory() . $filename;

        if (!move_uploaded_file($_FILES['screenshot']['tmp_name'], $targetPath)) {
            sqlibase_json_error('文件上传失败');
        }

        $screenshot_path = $filename;
    }

    $user = sqlibase_get_current_user();
    sqlibase_insert_feedback($pdo, $user['id'], $category_id, $content, $screenshot_path);

    sqlibase_json_success('反馈提交成功');
});
