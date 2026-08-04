<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo BASE_URL; ?>/</loc>
        <changefreq>monthly</changefreq>
        <priority>1.0</priority>
    </url>
    <?php foreach ($links as $link): ?>
    <url>
        <loc><?php echo BASE_URL; ?>/projects/<?php echo $link['id']; ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach ?>
</urlset>