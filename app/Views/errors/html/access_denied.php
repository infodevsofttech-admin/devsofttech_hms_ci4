<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Segoe UI", sans-serif;
            background: #f4f6f8;
            color: #17212b;
        }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
        }
        main {
            width: min(560px, calc(100% - 40px));
            border-left: 5px solid #b42318;
            background: #fff;
            padding: 32px;
            box-shadow: 0 8px 28px rgba(23, 33, 43, 0.12);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }
        p {
            margin: 0 0 24px;
            line-height: 1.55;
            color: #475467;
        }
        a {
            display: inline-block;
            color: #fff;
            background: #175cd3;
            padding: 10px 16px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main>
        <h1>Access denied</h1>
        <p>Your account does not have the permission required to open this page. Ask an administrator to verify your assigned role and its permissions.</p>
        <a href="<?= esc(base_url('/')) ?>">Return to HMS</a>
    </main>
</body>
</html>