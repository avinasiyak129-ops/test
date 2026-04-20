<?php
/**
 * Jarvis's Absolute Path Explorer for Windows
 * Uses full system paths for navigation and zipping.
 */

// 1. Determine the Current Working Directory or requested path
$currentPath = isset($_GET['folder']) ? $_GET['folder'] : getcwd();

// Normalize path: Replace forward slashes and remove trailing slashes
$currentPath = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $currentPath), DIRECTORY_SEPARATOR);

// 2. Security: Ensure the path actually exists and is a directory
if (!is_dir($currentPath)) {
    die("Error: Path does not exist or is not a directory.");
}

// 3. Handle Zip & Download Logic
if (isset($_GET['zip_target']) && is_dir($_GET['zip_target'])) {
    $targetToZip = $_GET['zip_target'];
    $zipName = basename($targetToZip) . '_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetToZip),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($targetToZip) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        if (file_exists($zipPath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath); 
            exit;
        }
    }
}

// 4. Calculate Parent Directory for "Go Back" functionality
$parentDir = dirname($currentPath);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Sir Venom's File Navigator</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #1a1a1a; color: #ddd; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #2d2d2d; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #3d3d3d; color: #00ff00; }
        tr:hover { background: #383838; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.9em; }
        .btn-open { background: #0078d7; color: white; }
        .btn-zip { background: #28a745; color: white; margin-left: 10px; }
        .path-display { background: #000; padding: 10px; color: #00ff00; margin-bottom: 20px; border-left: 4px solid #00ff00; }
    </style>
</head>
<body>

    <div class="path-display">
        <strong>Current Path:</strong> <?php echo htmlspecialchars($currentPath); ?>
    </div>

    <div style="margin-bottom: 15px;">
        <a href="?folder=<?php echo urlencode($parentDir); ?>" class="btn btn-open">⬅ Back to Parent</a>
        <a href="?folder=<?php echo urlencode(getcwd()); ?>" class="btn btn-open" style="background:#666;">🏠 Reset to CWD</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Full Path (Debugging)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $items = array_diff(scandir($currentPath), array('.', '..'));
            foreach ($items as $item):
                $fullItemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                $isDir = is_dir($fullItemPath);
            ?>
            <tr>
                <td><?php echo $isDir ? "📁" : "📄"; ?> <?php echo htmlspecialchars($item); ?></td>
                <td><?php echo $isDir ? "Directory" : "File"; ?></td>
                <td style="font-size: 0.8em; color: #888;"><?php echo htmlspecialchars($fullItemPath); ?></td>
                <td>
                    <?php if ($isDir): ?>
                        <a href="?folder=<?php echo urlencode($fullItemPath); ?>" class="btn btn-open">Open</a>
                        <a href="?folder=<?php echo urlencode($currentPath); ?>&zip_target=<?php echo urlencode($fullItemPath); ?>" class="btn btn-zip">Download ZIP</a>
                    <?php else: ?>
                        <span style="color:#666;">File only</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
