<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/entorno.php';
require_once dirname(__DIR__) . '/api/seo.php';

header('Content-Type: text/plain; charset=utf-8');

$base = urlBaseApp();

echo "User-agent: *\n";
echo "Allow: /\n";
echo "\n";
echo "Disallow: /api/\n";
echo "Disallow: /zg-x7k9m2p/\n";
echo "Disallow: /grupo.html\n";
echo "\n";
echo "Sitemap: {$base}/sitemap.xml\n";
