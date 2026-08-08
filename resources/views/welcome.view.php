<?php ob_start(); ?>

<div style="font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 80vh; background-color: #f8fafc; color: #1e293b; padding: 20px;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); max-width: 600px; width: 100%; text-align: center;">
        
        <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 10px; color: #0f172a;">
            <?= e($title ?? 'Welcome') ?>
        </h1>
        
        <p style="color: #64748b; font-size: 1.125rem; margin-bottom: 30px;">
            A simple, lightweight, Performance-first PHP framework.
        </p>

        <div style="margin-bottom: 30px; display: flex; justify-content: center; align-items: center; gap: 15px;">
            <?php if (\App\Supports\Auth::check()): ?>
                <span style="background: #e2e8f0; padding: 8px 16px; border-radius: 9999px; font-weight: 500;">
                    Welcome, <?= e(\App\Supports\Auth::user()->name) ?> 👋
                </span>
                <a href="/logout" style="background: #ef4444; color: white; text-decoration: none; padding: 8px 16px; border-radius: 9999px; font-weight: 500;">Logout</a>
            <?php else: ?>
                <a href="/login" style="background: #3b82f6; color: white; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: 600;">Login</a>
                <a href="/register" style="background: #f1f5f9; color: #334155; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; border: 1px solid #e2e8f0;">Register</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($users) && is_array($users)): ?>
            <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                <h3 style="margin-top: 0; margin-bottom: 15px; color: #334155; font-size: 1.25rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">Dummy Users</h3>
                <ul style="list-style-type: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($users as $user): ?>
                        <li style="display: flex; justify-content: space-between; background: white; padding: 12px 16px; border-radius: 6px; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);">
                            <span style="font-weight: 500; color: #0f172a;"><?= e($user->name ?? $user['name'] ?? '') ?></span>
                            <span style="color: #64748b; font-size: 0.875rem;"><?= e($user->email ?? $user['email'] ?? '') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<!-- Page-specific scripts can go here -->
<?php $scripts = ob_get_clean(); ?>

<?php require view_path('layout.main'); ?>