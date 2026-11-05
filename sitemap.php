<?php
// sitemap.php
// Dynamically generate XML sitemap for search engines

// Set content-type as XML
header("Content-Type: application/xml; charset=utf-8");

// Detect environment and set the appropriate base URL
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'welzee.com';
if ($host === 'localhost' || $host === '127.0.0.1') {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $segments = array_values(array_filter(explode('/', trim($scriptDir, '/'))));
    $folder = !empty($segments[0]) ? $segments[0] : '';
    $base_path = $folder !== '' ? '/' . $folder . '/' : '/';
    $base_url = "$protocol://$host$base_path";
} else {
    $base_url = "https://welzee.com/";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php
    // Define static pages mapping with search engine priority and change frequency
    $static_pages = [
        '' => ['priority' => '1.0', 'changefreq' => 'daily', 'file' => 'index.php'],
        'custom_order' => ['priority' => '0.9', 'changefreq' => 'weekly', 'file' => 'custom_order.php'],
        'services' => ['priority' => '0.8', 'changefreq' => 'weekly', 'file' => 'services.php'],
        'blog' => ['priority' => '0.8', 'changefreq' => 'daily', 'file' => 'blog.php'],
        'about' => ['priority' => '0.7', 'changefreq' => 'monthly', 'file' => 'about.php'],
        'contact' => ['priority' => '0.7', 'changefreq' => 'monthly', 'file' => 'contact.php'],
        'faq' => ['priority' => '0.6', 'changefreq' => 'weekly', 'file' => 'faq.php'],
        'privacy' => ['priority' => '0.3', 'changefreq' => 'monthly', 'file' => 'privacy.php'],
        'returns' => ['priority' => '0.3', 'changefreq' => 'monthly', 'file' => 'returns.php'],
        'shipping' => ['priority' => '0.3', 'changefreq' => 'monthly', 'file' => 'shipping.php'],
        'terms' => ['priority' => '0.3', 'changefreq' => 'monthly', 'file' => 'terms.php']
    ];

    // Output static pages
    foreach ($static_pages as $path => $info) {
        $file_path = __DIR__ . '/' . $info['file'];
        $lastmod = file_exists($file_path) ? date('Y-m-d', filemtime($file_path)) : date('Y-m-d');
        
        $url = $base_url . $path;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>' . $info['changefreq'] . '</changefreq>' . PHP_EOL;
        echo '    <priority>' . $info['priority'] . '</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
    }

    // Attempt to fetch dynamic blog posts from database
    $blog_posts = [];
    try {
        if (file_exists(__DIR__ . '/config/db.php')) {
            // Temporarily suppress errors/exceptions to prevent corrupting the XML output
            $old_error_reporting = error_reporting();
            error_reporting(0);
            
            // Use output buffering to catch and discard any raw HTML comments printed by db.php
            ob_start();
            include_once __DIR__ . '/config/db.php';
            ob_end_clean();
            
            if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                $stmt = $conn->prepare("SELECT slug, created_at FROM blog_posts ORDER BY created_at DESC");
                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result) {
                        $blog_posts = $result->fetch_all(MYSQLI_ASSOC);
                    }
                    $stmt->close();
                }
            }
            
            error_reporting($old_error_reporting);
        }
    } catch (Exception $e) {
        // Fallback silently if database is down or not configured
    }

    // Output blog posts
    foreach ($blog_posts as $post) {
        $slug = $post['slug'];
        $url = $base_url . 'blog/' . $slug;
        $lastmod = date('Y-m-d', strtotime($post['created_at']));
        
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
        echo '    <priority>0.6</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
    }
    ?>
</urlset>
