<?php ob_start(); ?>

<h2><?= e($title) ?></h2>
<?= flash(); ?>
<form method="post" action="/register">
    <?= csrf_field() ?>
    <!-- name -->
     <p>
        <label>
            Name
        </label><br>
            <input type="text" name="name" required>
     </p>
        <!-- username -->
    <p>
        <label>
            Username
        </label><br>
            <input type="text" name="username" required>
     </p>
     <!-- email -->
      <p>
        <label>
            Email
        </label><br>
            <input type="email" name="email" required>
     </p>
     <!-- password -->
      <p>
        <label>
            Password
        </label><br>
            <input type="password" name="password" required>
     </p>
     <!-- confirm password -->
      <p>
        <label>
            Confirm Password
        </label><br>
            <input type="password" name="password_confirmation" required>
    </p>
    <!-- agree to terms -->
    <div>
        <label>
            Agree to Terms and Conditions
        </label>
            <input type="checkbox" name="agreed" required>
    </div><br>
    <button type="submit">Register</button>
</form>

<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script></script>
<?php $scripts = ob_get_clean(); ?>

<?php require view_path('layout.main'); ?>
