// 允许的文件类型（前端校验）
var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
var allowedExtensions = ['jpg', 'jpeg', 'png'];

// 初始化拖拽上传功能
document.addEventListener('DOMContentLoaded', function () {
    initDragUpload();
    initFormValidation();
});

/**
 * 验证文件类型（前端校验）
 */
function validateFileType(file) {
    // 检查MIME类型
    if (allowedTypes.indexOf(file.type) === -1) {
        return false;
    }

    // 检查扩展名
    var fileName = file.name.toLowerCase();
    var extension = fileName.split('.').pop();
    if (allowedExtensions.indexOf(extension) === -1) {
        return false;
    }

    return true;
}

/**
 * 初始化表单验证
 */
function initFormValidation() {
    var uploadForm = document.getElementById('uploadForm');
    var uploadBtn = document.getElementById('uploadBtn');

    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            var dropzoneInput = document.getElementById('avatarInputDropzone');
            var avatarInput = document.getElementById('avatarInput');

            // 获取选择的文件
            var file = null;
            if (dropzoneInput && dropzoneInput.files.length > 0) {
                file = dropzoneInput.files[0];
            } else if (avatarInput && avatarInput.files.length > 0) {
                file = avatarInput.files[0];
            }

            if (!file) {
                e.preventDefault();
                alert('请先选择要上传的文件！');
                return false;
            }

            // 前端文件类型校验
            if (!validateFileType(file)) {
                e.preventDefault();
                alert('只允许上传 JPG/PNG 格式的图片文件！');
                return false;
            }

            return true;
        });
    }
}

/**
 * 初始化拖拽上传功能
 */
function initDragUpload() {
    var uploadDropzone = document.getElementById('uploadDropzone');
    var dropzoneInput = document.getElementById('avatarInputDropzone');
    var avatarInput = document.getElementById('avatarInput');

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
        var files = e.target.files;
        if (files.length > 0) {
            var file = files[0];

            // 前端文件类型校验
            if (!validateFileType(file)) {
                alert('只允许上传 JPG/PNG 格式的图片文件！');
                dropzoneInput.value = '';
                return;
            }

            updateDropzoneText(file);
            syncFileInputs(file);
        }
    });

    // 传统文件选择事件
    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            var files = e.target.files;
            if (files.length > 0) {
                var file = files[0];

                // 前端文件类型校验
                if (!validateFileType(file)) {
                    alert('只允许上传 JPG/PNG 格式的图片文件！');
                    avatarInput.value = '';
                    return;
                }

                syncFileInputs(file);
                updateDropzoneText(file);
            }
        });
    }

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

        var files = e.dataTransfer.files;
        if (files.length > 0) {
            var file = files[0];

            // 前端文件类型校验
            if (!validateFileType(file)) {
                alert('只允许上传 JPG/PNG 格式的图片文件！');
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
    var dropzoneInput = document.getElementById('avatarInputDropzone');

    // 创建一个新的FileList对象（通过DataTransfer）
    var dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);

    if (dropzoneInput) {
        dropzoneInput.files = dataTransfer.files;
    }
}

/**
 * 更新拖拽区域文本
 */
function updateDropzoneText(file) {
    var uploadDropzone = document.getElementById('uploadDropzone');
    if (!uploadDropzone) return;

    var textElement = uploadDropzone.querySelector('p');
    var fileSize = formatFileSize(file.size);

    if (textElement) {
        textElement.innerHTML = '<strong>已选择文件:</strong><br>' +
            file.name + '<br><small>大小: ' + fileSize + '</small>';
    }
}

/**
 * 格式化文件大小
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';

    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function confirmReset() {
    return confirm('确认删除全部已上传的文件吗？');
}

// 添加键盘快捷键支持（Ctrl+O 打开文件选择）
document.addEventListener('keydown', function (e) {
    if (e.ctrlKey && e.key === 'o') {
        e.preventDefault();
        var dropzoneInput = document.getElementById('avatarInputDropzone');
        if (dropzoneInput) {
            dropzoneInput.click();
        }
    }
});
