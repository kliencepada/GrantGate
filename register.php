<!DOCTYPE html>
<html lang="en">
<head>

<div class="card">
    <form action="register_logic.php" method="POST">
        <h2>Sign up</h2>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="school" placeholder="School" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register_btn">Sign up</button>


    <style>
    @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }
    body { background: #0f172a; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: sans-serif; }
    .card { background: #1e293b; padding: 2.5rem; border-radius: 15px; position: relative; width: 320px; color: white; }
    .card::before {
        content: ''; position: absolute; inset: -4px; border-radius: 19px; z-index: -1;
        background: conic-gradient(from var(--angle), transparent, #00ffff, #ff00ff, #00ffff);
        animation: spin 3s linear infinite;
    }
    @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }
    input { width: 100%; padding: 12px; margin: 10px 0; background: #334155; border: none; color: white; border-radius: 8px; box-sizing: border-box; }
    button { width: 100%; padding: 12px; background: #00ffff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 15px; }
</style>


    </form>
</div>