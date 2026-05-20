// 初始化拖拽上传功能
document.addEventListener('DOMContentLoaded', function () {
    initDragUpload();

    // 初始化传统文件选择
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                validateFileTypeByMIME(file);
            }
        });
    }
});

/**
 * 初始化拖拽上传功能
 */
function initDragUpload() {
    const uploadDropzone = document.getElementById('uploadDropzone');
    const dropzoneInput = document.getElementById('avatarInputDropzone');
    const avatarInput = document.getElementById('avatarInput');

    if (!uploadDropzone || !dropzoneInput) {
        return;
    }

    // 点击拖拽区域触发文件选择
    uploadDropzone.addEventListener('click', function (e) {
        if (e.target !== dropzoneInput) {
            dropzoneInput.click();
        }
    });

    // 拖拽区域文件选择事件
    dropzoneInput.addEventListener('change', function (e) {
        const files = e.target.files;
        if (files.length > 0) {
            updateDropzoneText(files[0]);
            syncFileInputs(files[0]);

            // 自动触发表单验证
            if (!validateFileTypeByMIME(files[0])) {
                this.value = '';
                if (avatarInput) avatarInput.value = '';
                resetDropzoneText();
            }
        }
    });

    // 拖拽事件处理
    uploadDropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });

    uploadDropzone.addEventListener('dragleave', function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });

    uploadDropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];

            // 验证文件MIME类型
            if (!validateFileTypeByMIME(file)) {
                return;
            }

            // 同步文件到两个输入框
            syncFileInputs(file);

            // 更新拖拽区域显示
            updateDropzoneText(file);
        }
    });
}

/**
 * 同步文件到拖拽输入框
 * 注意：只同步到dropzoneInput，避免重复上传
 */
function syncFileInputs(file) {
    const dropzoneInput = document.getElementById('avatarInputDropzone');

    // 创建一个新的FileList对象（通过DataTransfer）
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);

    if (dropzoneInput) {
        dropzoneInput.files = dataTransfer.files;
    }
}

/**
 * 更新拖拽区域文本
 */
function updateDropzoneText(file) {
    const uploadDropzone = document.getElementById('uploadDropzone');
    if (!uploadDropzone) return;

    const textElement = uploadDropzone.querySelector('p');
    const fileSize = formatFileSize(file.size);

    if (textElement) {
        textElement.innerHTML = '<strong>已选择文件:</strong><br>' +
            file.name + '<br><small>大小: ' + fileSize + '</small>';
    }
}

/**
 * 重置拖拽区域文本
 */
function resetDropzoneText() {
    const uploadDropzone = document.getElementById('uploadDropzone');
    if (!uploadDropzone) return;

    const textElement = uploadDropzone.querySelector('p');
    if (textElement) {
        textElement.textContent = '点击选择文件或拖拽文件到此处';
    }
}

/**
 * 格式化文件大小
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * 通过MIME类型验证文件类型（Content-Type校验）
 */
function validateFileTypeByMIME(file) {
    if (!file) return true;

    const allowedTypes = ['image/jpeg', 'image/png'];

    if (!allowedTypes.includes(file.type)) {
        alert('只能上传 JPEG 或 PNG 格式的图片文件！\n检测到的文件类型: ' + (file.type || '未知'));
        // 注意：这里仅验证浏览器报告的MIME类型，容易被绕过
        return false;
    }

    return true;
}

/**
 * 表单提交时的文件类型验证
 */
function validateFileType() {
    const fileInput = document.getElementById('avatarInput');
    const dropzoneInput = document.getElementById('avatarInputDropzone');

    // 检查两个输入框是否有文件
    let file = null;
    if (fileInput && fileInput.files.length > 0) {
        file = fileInput.files[0];
    } else if (dropzoneInput && dropzoneInput.files.length > 0) {
        file = dropzoneInput.files[0];
    }

    if (file) {
        return validateFileTypeByMIME(file);
    }

    return true;
}

function confirmReset() {
    return confirm('确认删除全部已上传的文件吗？');
}

// 添加键盘快捷键支持（Ctrl+O 打开文件选择）
document.addEventListener('keydown', function (e) {
    if (e.ctrlKey && e.key === 'o') {
        e.preventDefault();
        const dropzoneInput = document.getElementById('avatarInputDropzone');
        if (dropzoneInput) {
            dropzoneInput.click();
        }
    }
});
