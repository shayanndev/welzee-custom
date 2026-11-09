<?php
// sitemap.php
// Dynamically generate XML sitemap for search engines

require_once __DIR__ . '/config/bootstrap.php';

// Set content-type as XML
header("Content-Type: application/xml; charset=utf-8");

// Base URL: configured site_url in production, auto-detected sub-folder on localhost
$base_url = app_base_url();

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
        // The sitemap must still render if the database is unavailable.
        define('DB_OPTIONAL', true);
        ob_start();
        include_once __DIR__ . '/config/db.php';
        ob_end_clean();

        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare("SELECT slug, created_at FROM blog_posts ORDER BY created_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $blog_posts = $result->fetch_all(MYSQLI_ASSOC);
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        // Fall back silently (details are in the error log if DB failed)
    }

    // Output blog posts
    foreach ($blog_posts as $post) {
        $slug = $post['slug'];
        $url = $base_url . 'blog/' . rawurlencode($slug);
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
