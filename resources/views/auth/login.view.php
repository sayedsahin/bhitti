<?php $this->layout('layout.main'); ?>
<?php $this->start('content'); ?>

<div style="font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 80vh; background-color: #f8fafc; color: #1e293b; padding: 20px;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); max-width: 400px; width: 100%;">

        <h2 style="font-size: 2rem; font-weight: 700; margin-top: 0; margin-bottom: 20px; color: #0f172a; text-align: center;">
            <?= $this->e($title ?? 'Login') ?>
        </h2>

        <div style="margin-bottom: 20px;">
            <?= $this->flash() ?>
        </div>

        <form method="post" action="/login" style="display: flex; flex-direction: column; gap: 15px;">
            <?= $this->csrfField() ?>

            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 5px; color: #334155;">Email</label>
                <input type="email" name="email" required style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; color: #0f172a; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
            </div>

            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 5px; color: #334155;">Password</label>
                <input type="password" name="password" required style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; color: #0f172a; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
            </div>

            <div style="display: flex; align-items: center; gap: 8px; margin-top: 5px;">
                <input type="checkbox" name="remember" id="remember" style="width: 16px; height: 16px; cursor: pointer;">
                <label for="remember" style="font-size: 0.875rem; color: #475569; cursor: pointer;">Remember Me</label>
            </div>

            <button type="submit" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 12px; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 10px;">Login</button>

            <p style="text-align: center; margin-top: 15px; font-size: 0.875rem; color: #64748b;">
                Don't have an account? <a href="/register" style="color: #3b82f6; text-decoration: none; font-weight: 500;">Register</a>
            </p>
        </form>

    </div>
</div>

<?php $this->end(); ?>

<?php $this->start('scripts'); ?>
<!-- Scripts -->
<?php $this->end(); ?>

