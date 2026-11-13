<?php
/**
 * tools/update_article_01.php
 *
 * Idempotent, safe migration script to update Article 1 (dtg-vs-screen-print)
 * in the MySQL database.
 *
 * Usage:
 *   php tools/update_article_01.php
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

$metaJsonFile = __DIR__ . '/../content_drafts/article_01/ARTICLE_01_metadata.json';
$bodyHtmlFile = __DIR__ . '/../content_drafts/article_01/ARTICLE_01_body.html';

if (!file_exists($metaJsonFile) || !file_exists($bodyHtmlFile)) {
    die("Error: Source files in content_drafts/article_01/ not found.\n");
}

$meta = json_decode(file_get_contents($metaJsonFile), true);
$bodyHtml = file_get_contents($bodyHtmlFile);

$slug = $meta['existing_slug'];
$title = $meta['h1'];
$category = $meta['category'];
$excerpt = $meta['excerpt'];
$imageUrl = 'assets/images/blog/dtg-vs-screen-print-cover.webp';

echo "Updating article slug: '$slug'...\n";

$stmt = $conn->prepare("UPDATE blog_posts SET title = ?, category = ?, excerpt = ?, content = ?, image_url = ? WHERE slug = ?");
$stmt->bind_param("ssssss", $title, $category, $excerpt, $bodyHtml, $imageUrl, $slug);
$stmt->execute();

$affected = $stmt->affected_rows;
$stmt->close();

echo "Update complete. Rows affected: $affected\n";

// Verification
$stmtV = $conn->prepare("SELECT id, slug, title, category, excerpt, LENGTH(content) as content_len, image_url, created_at FROM blog_posts WHERE slug = ?");
$stmtV->bind_param("s", $slug);
$stmtV->execute();
$res = $stmtV->get_result()->fetch_assoc();
print_r($res);