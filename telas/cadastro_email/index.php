<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Identidade - CSS Puro</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background-color: #ffffff;
            max-width: 400px;
            width: 90%;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        h2 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .input-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box; /
            outline: none;
            transition: border-color 0.2s;
        }

        input[type="email"]:focus {
            border-color: #2563eb;
            ring: 2px solid #bfdbfe;
        }

        button {
            width: 100%;
            background-color: #517fe2ff;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        button:active {
            transform: scale(0.98);
        }

        .footer-link {
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .footer-link a {
            color: #2563eb;
            text-decoration: none;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="card">
        <h2>Confirme seu acesso</h2>
        <p>Digite o e-mail utilizado no cadastro para receber o link de ativação.</p>

        <form>
            <div class="input-group">
                <label for="email">E-mail de confirmação</label>
                <input type="email" id="email" required placeholder="exemplo@email.com.br">
            </div>

            <button type="submit">Enviar Link de Confirmação</button>
        </form>

        <div class="footer-link">
            Errou o e-mail? <a href="#">Voltar para o cadastro</a>
        </div>
    </div>

</body>
</html>