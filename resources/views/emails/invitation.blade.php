<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation à rejoindre l'équipe Growai</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0c0c0e;
            color: #ffffff;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #18181b;
            border: 1px solid #27272a;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .header {
            background-color: #18181b;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #27272a;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #8950fc;
            text-decoration: none;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px 30px;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 20px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #a1a1aa;
            margin-bottom: 25px;
        }
        .credentials-card {
            background-color: #0c0c0e;
            border: 1px solid #27272a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .credential-item {
            margin-bottom: 12px;
            font-size: 14px;
        }
        .credential-item:last-child {
            margin-bottom: 0;
        }
        .credential-label {
            color: #71717a;
            font-weight: 500;
            display: inline-block;
            width: 150px;
        }
        .credential-value {
            color: #ffffff;
            font-weight: 600;
            font-family: monospace;
            background-color: #18181b;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #27272a;
        }
        .btn-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 30px;
        }
        .btn {
            background-color: #8950fc;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(137, 80, 252, 0.3);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #783cfb;
        }
        .footer {
            background-color: #0c0c0e;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #52525b;
            border-top: 1px solid #27272a;
        }
        .footer a {
            color: #8950fc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="logo">FLASHMANAGER</span>
        </div>
        <div class="content">
            <h1>Bonjour {{ $name }},</h1>
            <p>Vous avez été invité à rejoindre la plateforme en tant que membre de l'équipe (Rôle: <strong>{{ $role }}</strong>).</p>
            <p>Voici vos identifiants de connexion temporaires pour accéder à votre espace :</p>
            
            <div class="credentials-card">
                <div class="credential-item">
                    <span class="credential-label">Adresse e-mail :</span>
                    <span class="credential-value">{{ $email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Mot de passe :</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>
            
            <div class="btn-container">
                <a href="{{ $loginUrl }}" class="btn">Rejoindre l'équipe</a>
            </div>
            
            <p style="margin-bottom: 0;">Pour des raisons de sécurité, nous vous conseillons vivement de modifier ce mot de passe temporaire dès votre première connexion.</p>
        </div>
        <div class="footer">
            Cet e-mail a été envoyé automatiquement par FlashManager.<br>
            Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.
        </div>
    </div>
</body>
</html>
