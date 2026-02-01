<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Portal Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo i {
            font-size: 4rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }
        .login-logo h1 {
            color: #fff;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        .login-logo h1 span {
            color: #3b82f6;
        }
        .login-logo p {
            color: #94a3b8;
            margin: 0.5rem 0 0;
        }
        .form-label {
            color: #e2e8f0;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 0.875rem 1rem;
            border-radius: 12px;
            font-size: 1rem;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .form-control::placeholder {
            color: #64748b;
        }
        .input-group-text {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(255,255,255,0.1);
            color: #3b82f6;
            border-radius: 12px 0 0 12px;
        }
        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            color: #fff;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .alert {
            border-radius: 12px;
            border: none;
        }
        .help-text {
            color: #64748b;
            font-size: 0.875rem;
            text-align: center;
            margin-top: 1.5rem;
        }
        .help-text i {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="bi bi-wifi"></i>
                <h1>Inter<span>Vereda</span>Net</h1>
                <p>Portal de Clientes</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.login.post') }}">
                @csrf
                
                <div class="mb-4">
                    <label class="form-label">Número de Cédula</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="documento" class="form-control" 
                               placeholder="Ingrese su cédula" value="{{ old('documento') }}" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">PIN (4 dígitos)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" name="pin" class="form-control" 
                               placeholder="****" maxlength="4" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                </button>
            </form>

            <div class="help-text">
                <i class="bi bi-info-circle me-1"></i>
                Tu PIN son los últimos 4 dígitos de tu cédula
            </div>
        </div>
    </div>
</body>
</html>
