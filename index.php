<?php
session_start();

// 配置项
$config = [
    'password' => 'your_password_here', // 设置固定密码
    'upload_dir' => 'uploads/',
    'items_per_page' => 12,
    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'max_size' => 5 * 1024 * 1024 // 5MB
];

// 登录检查
if (isset($_POST['login'])) {
    if ($_POST['password'] === $config['password']) {
        $_SESSION['authenticated'] = true;
    } else {
        $login_error = "密码错误";
    }
}

// 登出处理
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// 如果没有登录，显示登录表单
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    displayLoginForm($login_error ?? null);
    exit;
}

// 删除图片处理
if (isset($_GET['delete']) && isset($_GET['token']) && $_GET['token'] === md5($_SESSION['authenticated'])) {
    $file_to_delete = basename($_GET['delete']);
    $file_path = $config['upload_dir'] . $file_to_delete;
    
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => '图片删除成功'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => '删除图片失败'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => '文件不存在'];
    }
    
    header("Location: ".str_replace(['&delete='.$_GET['delete'], 'delete='.$_GET['delete'].'&', '?delete='.$_GET['delete']], '', $_SERVER['REQUEST_URI']));
    exit;
}

// 文件上传处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $upload_errors = [];
    $upload_success = [];
    
    // 确保上传目录存在
    if (!file_exists($config['upload_dir'])) {
        mkdir($config['upload_dir'], 0755, true);
    }
    
    // 处理每个上传的文件
    foreach ($_FILES['images']['name'] as $key => $name) {
        $tmp_name = $_FILES['images']['tmp_name'][$key];
        $error = $_FILES['images']['error'][$key];
        $size = $_FILES['images']['size'][$key];
        $type = $_FILES['images']['type'][$key];
        
        // 检查上传错误
        if ($error !== UPLOAD_ERR_OK) {
            $upload_errors[] = "文件 $name 上传失败: " . getUploadError($error);
            continue;
        }
        
        // 检查文件类型
        if (!in_array($type, $config['allowed_types'])) {
            $upload_errors[] = "文件 $name 类型不允许 ($type)";
            continue;
        }
        
        // 检查文件大小
        if ($size > $config['max_size']) {
            $upload_errors[] = "文件 $name 太大 (最大 " . ($config['max_size'] / 1024 / 1024) . "MB)";
            continue;
        }
        
        // 生成唯一文件名
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $ext;
        $destination = $config['upload_dir'] . $new_filename;
        
        // 移动文件
        if (move_uploaded_file($tmp_name, $destination)) {
            $upload_success[] = "文件 $name 上传成功";
        } else {
            $upload_errors[] = "文件 $name 保存失败";
        }
    }
    
    // 存储上传结果到session
    if (!empty($upload_errors)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => implode('<br>', $upload_errors)];
    }
    if (!empty($upload_success)) {
        $_SESSION['message'] = ['type' => 'success', 'text' => implode('<br>', $upload_success)];
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// 获取上传错误信息
function getUploadError($error_code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
        UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => 'PHP扩展阻止了文件上传'
    ];
    return $errors[$error_code] ?? '未知错误';
}

// 显示登录表单
function displayLoginForm($error = null) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>图片上传 - 登录</title>
        <style>
            :root {
                --primary-color: #4CAF50;
                --danger-color: #f44336;
                --text-color: #333;
                --light-bg: #f9f9f9;
                --border-radius: 8px;
                --box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            * {
                box-sizing: border-box;
                -webkit-tap-highlight-color: transparent;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                margin: 0;
                padding: 15px;
                color: var(--text-color);
                background-color: #f5f5f5;
                line-height: 1.5;
            }
            
            .login-container {
                max-width: 100%;
                margin: 0 auto;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                overflow: hidden;
                padding: 20px;
            }
            
            h2 {
                margin-top: 0;
                color: var(--primary-color);
                text-align: center;
                font-size: 1.5rem;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            
            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: var(--border-radius);
                font-size: 16px;
            }
            
            button {
                width: 100%;
                background: var(--primary-color);
                color: white;
                padding: 14px;
                border: none;
                border-radius: var(--border-radius);
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            button:hover {
                opacity: 0.9;
            }
            
            .error {
                color: var(--danger-color);
                margin: 10px 0;
                padding: 10px;
                background: #ffebee;
                border-radius: var(--border-radius);
                font-size: 14px;
            }
            
            @media (min-width: 768px) {
                body {
                    padding: 30px;
                }
                .login-container {
                    max-width: 500px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>图片上传系统</h2>
            <form method="post">
                <div class="form-group">
                    <label for="password">请输入密码:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login">登录</button>
                <?php if ($error) echo "<div class='error'>$error</div>"; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
}

// 获取当前页码
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// 获取所有图片文件
$all_files = [];
if (file_exists($config['upload_dir'])) {
    $all_files = glob($config['upload_dir'].'*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    // 按修改时间倒序排列
    usort($all_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
}

// 分页计算
$total_items = count($all_files);
$total_pages = ceil($total_items / $config['items_per_page']);
$offset = ($page - 1) * $config['items_per_page'];
$files = array_slice($all_files, $offset, $config['items_per_page']);

// 获取基础URL
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>简约图床</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <style>
        :root {
            --primary-color: #4CAF50;
            --danger-color: #f44336;
            --info-color: #2196F3;
            --text-color: #333;
            --light-bg: #f9f9f9;
            --border-radius: 8px;
            --box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            --gap: 10px;
        }
        
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: var(--text-color);
            background-color: #f5f5f5;
            line-height: 1.5;
        }
        
        .header {
            display: flex;
            flex-direction: column;
            gap: var(--gap);
            margin-bottom: 20px;
        }
        
        @media (min-width: 768px) {
            .header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
        
        h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary-color);
            text-align: center;
        }
        
        .upload-form {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        /* 文件上传区域样式 */
        .file-upload {
            position: relative;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .file-upload-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px;
            border: 2px dashed #ccc;
            border-radius: var(--border-radius);
            background-color: var(--light-bg);
            transition: all 0.3s;
            text-align: center;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background-color: #f0f8f0;
        }
        
        .file-upload-icon {
            font-size: 40px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .file-upload-text {
            font-size: 16px;
            color: #555;
        }
        
        .file-upload-hint {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }
        
        .file-selected {
            margin-top: 10px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: var(--border-radius);
            color: #2e7d32;
            display: none;
            font-size: 14px;
        }
        
        button {
            background: var(--primary-color);
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        button.logout {
            background: var(--danger-color);
        }
        
        .message {
            margin: 15px 0;
            padding: 15px;
            border-radius: var(--border-radius);
            font-size: 14px;
        }
        
        .error {
            background: #ffebee;
            border-left: 4px solid var(--danger-color);
        }
        
        .success {
            background: #e8f5e9;
            border-left: 4px solid var(--primary-color);
        }
        
        /* 画廊样式 - 移动端优先 */
        .gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--gap);
            margin: 20px 0;
        }
        
        @media (min-width: 600px) {
            .gallery {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 900px) {
            .gallery {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s;
            aspect-ratio: 1/1;
        }
        
        .gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        /* 操作按钮 - 移动端优化 */
        .action-buttons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 8px;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            transition: opacity 0.3s;
            padding: 5px;
        }
        
        @media (min-width: 768px) {
            .action-buttons {
                flex-direction: row;
                padding: 10px;
            }
        }
        
        .gallery-item:hover .action-buttons,
        .gallery-item.active .action-buttons {
            opacity: 1;
        }
        
        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            width: 80%;
            max-width: 120px;
        }
        
        @media (min-width: 768px) {
            .action-btn {
                padding: 8px 12px;
                font-size: 14px;
                width: auto;
                margin: 0 5px;
            }
        }
        
        .action-btn i {
            margin-right: 5px;
            font-size: 12px;
        }
        
        @media (min-width: 768px) {
            .action-btn i {
                font-size: 14px;
            }
        }
        
        .copy-btn {
            background: var(--info-color);
            color: white;
        }
        
        .delete-btn {
            background: var(--danger-color);
            color: white;
        }
        
        /* 分页样式 */
        .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: var(--text-color);
            border-radius: var(--border-radius);
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: #f1f1f1;
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination .disabled {
            color: #aaa;
            pointer-events: none;
        }
        
        .empty-message {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        /* 提示框 */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
            max-width: 90%;
            text-align: center;
            font-size: 14px;
        }
        
        .toast.show {
            opacity: 1;
        }
        
        /* 密码信息 */
        .password-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        
        @media (min-width: 768px) {
            .password-info {
                flex-direction: row;
            }
        }
        
        .date-display {
            font-family: monospace;
            background: var(--light-bg);
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-size: 14px;
        }
        
        @media (min-width: 768px) {
            body {
                padding: 30px;
                max-width: 1200px;
                margin: 0 auto;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="header">
        <h1>简约图床</h1>
        <div class="password-info">
            <span class="date-display">今日日期：<?php echo date('ymd'); ?></span>
            <a href="?logout=1" class="logout"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
        </div>
    </div>
    
    <div class="upload-form">
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label><i class="fas fa-images"></i> 选择图片 (可多选):</label>
                <div class="file-upload">
                    <input type="file" id="images" name="images[]" multiple accept="image/*" class="file-upload-input">
                    <label for="images" class="file-upload-label">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-upload-text">点击或拖拽文件到此处</div>
                        <div class="file-upload-hint">支持JPG, PNG, GIF, WebP格式</div>
                    </label>
                    <div class="file-selected" id="file-selected-info"></div>
                </div>
            </div>
            <button type="submit"><i class="fas fa-upload"></i> 上传图片</button>
        </form>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?php echo $_SESSION['message']['type']; ?>">
            <p><?php echo $_SESSION['message']['text']; ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <h2><i class="fas fa-camera"></i> 已上传图片 (共 <?php echo $total_items; ?> 张)</h2>
    
    <?php if (!empty($files)): ?>
        <div class="gallery">
            <?php foreach ($files as $file): 
                $filename = basename($file);
                $file_url = $base_url . '/' . ltrim($file, '/');
                $delete_token = md5($_SESSION['authenticated']);
            ?>
                <div class="gallery-item" tabindex="0">
                    <img src="<?php echo htmlspecialchars($file); ?>" alt="上传的图片" loading="lazy">
                    <div class="action-buttons">
                        <button class="action-btn copy-btn" onclick="copyToClipboard('<?php echo $file_url; ?>')">
                            <i class="fas fa-copy"></i> <span class="btn-text">复制</span>
                        </button>
                        <button class="action-btn delete-btn" 
                                onclick="if(confirm('确定要删除这张图片吗？')) { window.location.href='?delete=<?php echo $filename; ?>&token=<?php echo $delete_token; ?>'; }">
                            <i class="fas fa-trash"></i> <span class="btn-text">删除</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- 分页导航 -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1"><i class="fas fa-angle-double-left"></i> 首页</a>
                <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-angle-left"></i> 上一页</a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-angle-double-left"></i> 首页</span>
                <span class="disabled"><i class="fas fa-angle-left"></i> 上一页</span>
            <?php endif; ?>
            
            <?php
            // 显示页码，最多显示5个页码
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);
            $start_page = max(1, $end_page - 4);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">下一页 <i class="fas fa-angle-right"></i></a>
                <a href="?page=<?php echo $total_pages; ?>">末页 <i class="fas fa-angle-double-right"></i></a>
            <?php else: ?>
                <span class="disabled">下一页 <i class="fas fa-angle-right"></i></span>
                <span class="disabled">末页 <i class="fas fa-angle-double-right"></i></span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-message">
            <p><i class="fas fa-image"></i> 还没有上传任何图片</p>
        </div>
    <?php endif; ?>

    <div id="toast" class="toast"></div>

    <script>
        // 复制链接功能
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                const successful = document.execCommand('copy');
                const message = successful ? '链接已复制到剪贴板' : '复制失败，请手动复制';
                showToast(message);
            } catch (err) {
                showToast('复制失败: ' + err);
            }
            
            document.body.removeChild(textarea);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).catch(function(err) {
                    console.error('无法复制: ', err);
                });
            }
        }

        // 显示提示消息
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // 文件选择和拖拽功能
        document.getElementById('images').addEventListener('change', function(e) {
            updateFileSelectedInfo(e.target.files);
        });
        
        const uploadLabel = document.querySelector('.file-upload-label');
        
        uploadLabel.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadLabel.style.borderColor = '#4CAF50';
            uploadLabel.style.backgroundColor = '#f0f8f0';
        });
        
        uploadLabel.addEventListener('dragleave', () => {
            uploadLabel.style.borderColor = '#ccc';
            uploadLabel.style.backgroundColor = '#f9f9f9';
        });
        
        uploadLabel.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadLabel.style.borderColor = '#ccc';
            uploadLabel.style.backgroundColor = '#f9f9f9';
            
            const input = document.getElementById('images');
            input.files = e.dataTransfer.files;
            updateFileSelectedInfo(input.files);
        });
        
        function updateFileSelectedInfo(files) {
            const fileSelectedInfo = document.getElementById('file-selected-info');
            
            if (files.length > 0) {
                let fileNames = [];
                for (let i = 0; i < Math.min(files.length, 3); i++) {
                    fileNames.push(files[i].name);
                }
                
                let message = `已选择 ${files.length} 个文件`;
                if (files.length > 3) {
                    message += ` (显示前3个: ${fileNames.join(', ')}...)`;
                } else {
                    message += `: ${fileNames.join(', ')}`;
                }
                
                fileSelectedInfo.textContent = message;
                fileSelectedInfo.style.display = 'block';
            } else {
                fileSelectedInfo.style.display = 'none';
            }
        }
        
        // 移动端触摸事件处理
        document.addEventListener('DOMContentLoaded', function() {
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            galleryItems.forEach(item => {
                let tapTimer;
                let startX, startY;
                
                item.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                    
                    tapTimer = setTimeout(() => {
                        this.classList.add('active');
                    }, 100);
                }, {passive: true});
                
                item.addEventListener('touchmove', function(e) {
                    const moveX = e.touches[0].clientX;
                    const moveY = e.touches[0].clientY;
                    
                    if (Math.abs(moveX - startX) > 10 || Math.abs(moveY - startY) > 10) {
                        clearTimeout(tapTimer);
                        this.classList.remove('active');
                    }
                }, {passive: true});
                
                item.addEventListener('touchend', function(e) {
                    clearTimeout(tapTimer);
                    const endX = e.changedTouches[0].clientX;
                    const endY = e.changedTouches[0].clientY;
                    
                    if (Math.abs(endX - startX) < 10 && Math.abs(endY - startY) < 10) {
                        const buttons = this.querySelector('.action-buttons');
                        buttons.style.opacity = buttons.style.opacity === '1' ? '0' : '1';
                    }
                    this.classList.remove('active');
                }, {passive: true});
            });
            
            // 点击其他地方隐藏按钮
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.gallery-item')) {
                    document.querySelectorAll('.action-buttons').forEach(btn => {
                        btn.style.opacity = '0';
                    });
                }
            });
        });
    </script>
</body>
</html>
