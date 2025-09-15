<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Funcionários</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #5cb85c;
            color: white;
            padding: 20px;
            text-align: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            margin: -20px -20px 20px -20px;
        }
        h1 {
            color: #2e7d32;
            margin-top: 30px;
            font-weight: 600;
        }
        .greeting {
            font-size: 22px;
            margin-bottom: 20px;
            color: #2e7d32;
        }
        p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }
        th {
            background-color: #81c784;
            color: #fff;
            font-weight: 600;
            border-bottom: 2px solid #5cb85c;
        }
        tr:nth-child(even) {
            background-color: #f2f9f2;
        }
        tr:hover {
            background-color: #e8f5e9;
        }
        .section-title {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #2e7d32;
            font-size: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #5cb85c;
        }
        .no-data {
            font-style: italic;
            color: #666;
            background-color: #f2f9f2;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #5cb85c;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #757575;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background-color: #5cb85c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 10px;
        }
        .badge {
            color: white;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 5px;
            display: inline-block;
        }
        .badge-created {
            background-color: #5cb85c;
        }
        .badge-updated {
            background-color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: white; margin: 0;">Sistema Convenia</h1>
            <p style="color: white; margin: 5px 0 0 0;">Plataforma completa de RH e Departamento Pessoal</p>
        </div>

        <p class="greeting">Olá, {{ $user->name }}!</p>

        <p>
            {{ $employeeCount }} {{ $employeeCount === 1 ? 'funcionário foi' : 'funcionários foram' }}
            <strong>{{ $actionText }}</strong> no sistema:
        </p>

        <h2 class="section-title">
            Funcionários {{ $actionText }}
            <span class="badge badge-{{ $action }}">{{ ucfirst($actionText) }}</span>
        </h2>

        @if($employees->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Documento</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Data de Início</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        <tr>
                            <td><strong>{{ $employee->name }}</strong></td>
                            <td>{{ $employee->email }}</td>
                            <td>{{ $employee->document }}</td>
                            <td>{{ $employee->city }}</td>
                            <td>{{ $employee->state }}</td>
                            <td>{{ $employee->startDate }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-data">Nenhum funcionário foi processado.</p>
        @endif

        <div class="footer">
            <p>Precisa de ajuda com o sistema de RH? Entre em contato conosco:</p>
            <a href="https://convenia.com.br/contato" class="cta-button">Fale com um Especialista</a>
            <p><small>Este é um email automático do sistema Convenia.<br>Data do processamento: {{ now()->format('d/m/Y H:i:s') }}</small></p>
        </div>
    </div>
</body>
</html>
