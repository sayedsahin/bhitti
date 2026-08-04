<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title><?= e($title ?? 'App') ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Security -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Optional SEO -->
    <?php if (!empty($description)): ?>
        <meta name="description" content="<?= e($description) ?>">
    <?php endif; ?>

    <!-- Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>

    <!-- Page Content -->
    <?= $content ?>

    <!-- Optional Scripts -->
    <?php if (!empty($scripts)): ?>
        <?= raw($scripts) ?>
    <?php endif; ?>

</body>
</html>
