<?php

ob_start();
?>
<h2><?= e($title) ?></h2>
<?php flash(); ?>
<form method="post" action="/login">
    <?= csrf_field() ?>
    <p>
        <label>
            Email
        </label><br>
            <input type="email" name="email" required>
    </p>
    <p>
        <label>
            Password
        </label><br>
            <input type="password" name="password" required>
    </p>
    <!-- remember -->
    <div>
        <label>
            Remember Me
        </label>
            <input type="checkbox" name="remember">
    </div><br>
    <button type="submit">Login</button>
</form>

<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script></script>
<?php $scripts = ob_get_clean(); ?>

<?php require view_path('layout.main'); ?>
