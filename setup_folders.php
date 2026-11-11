<?php
/**
 * 🛠️ REMER FITNESS GYM - Folder Setup
 * Run once to create upload directories
 */

$base_dir = __DIR__;
$upload_dir = $base_dir . '/uploads/payments/';

echo "<h2>🛠️ Setting up folders...</h2>";

// Create directories if not exist
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "✅ <strong>Created:</strong> uploads/payments/<br>";
    } else {
        echo "❌ <strong>Failed to create:</strong> uploads/payments/<br>";
    }
} else {
    echo "✓ <strong>Already exists:</strong> uploads/payments/<br>";
}

// Check permissions
if (is_writable($upload_dir)) {
    echo "✅ <strong>Writable:</strong> Folder has write permissions<br>";
} else {
    echo "⚠️ <strong>Warning:</strong> Folder may not be writable<br>";
    echo "→ Try setting permissions to 777<br>";
}

// Create .htaccess file
$htaccess_path = $upload_dir . '.htaccess';
$htaccess_content = "# Prevent direct access to uploaded files
Options -Indexes
<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>";

if (!file_exists($htaccess_path)) {
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        echo "✅ <strong>Created:</strong> .htaccess (security file)<br>";
    } else {
        echo "❌ <strong>Failed to create:</strong> .htaccess<br>";
    }
} else {
    echo "✓ <strong>Already exists:</strong> .htaccess<br>";
}

echo "<br><hr>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p>Your upload folders are ready to use.</p>";
echo "<p style='color: red;'><strong>You can now DELETE this setup_folders.php file.</strong></p>";
?>