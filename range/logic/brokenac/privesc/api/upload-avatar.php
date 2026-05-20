<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战上传头像接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('POST');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    $currentUser = privesc_require_login($pdo);

    if (!isset($_FILES['avatar'])) {
        privesc_json_error('请选择要上传的头像文件');
    }

    $file = $_FILES['avatar'];
    if (!is_array($file) || !isset($file['error'])) {
        privesc_json_error('上传文件无效');
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        privesc_json_error('头像上传失败');
    }

    $config = privesc_get_config();
    if (!isset($file['size']) || (int) $file['size'] <= 0) {
        privesc_json_error('上传文件不能为空');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        privesc_json_error('上传临时文件无效');
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mimeType === '' && function_exists('mime_content_type')) {
        $mimeType = (string) mime_content_type($file['tmp_name']);
    }

    if (!in_array($mimeType, $config['upload']['allowed_mime'], true)) {
        privesc_json_error('仅支持 PNG、JPG、JPEG 图片');
    }

    $extension = privesc_get_extension_by_mime($mimeType);
    if ($extension === '' || !in_array($extension, $config['upload']['extensions'], true)) {
        privesc_json_error('图片扩展名校验失败');
    }

    privesc_ensure_avatar_directory();
    $filename = privesc_generate_avatar_filename($extension);
    $targetPath = privesc_get_avatar_directory() . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('移动上传文件失败');
    }

    $oldAvatar = (string) $currentUser['avatar'];
    privesc_update_avatar($pdo, $currentUser['id'], $filename);

    if ($oldAvatar !== '' && $oldAvatar !== $filename) {
        privesc_delete_avatar_file($oldAvatar);
    }

    privesc_json_success('头像上传成功');
});
