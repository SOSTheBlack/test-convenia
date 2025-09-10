<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Funcionário {{ $isNew ? 'Criado' : 'Atualizado' }}</title>
</head>
<body>
    <h1>Olá, {{ $user->name }}!</h1>
    
    <p>Este é um aviso de que um funcionário foi {{ $isNew ? 'criado' : 'atualizado' }} no sistema:</p>
    
    <h2>Dados do Funcionário:</h2>
    <ul>
        <li><strong>Nome:</strong> {{ $employee->name }}</li>
        <li><strong>Email:</strong> {{ $employee->email }}</li>
        <li><strong>Documento:</strong> {{ $employee->document }}</li>
        <li><strong>Cidade:</strong> {{ $employee->city }}</li>
        <li><strong>Estado:</strong> {{ $employee->state }}</li>
        <li><strong>Data de Início:</strong> {{ $employee->start_date }}</li>
    </ul>
    
    @if(!$isNew && $previousEmployee)
    <h3>Dados Anteriores:</h3>
    <ul>
        <li><strong>Nome:</strong> {{ $previousEmployee->name }}</li>
        <li><strong>Email:</strong> {{ $previousEmployee->email }}</li>
        <li><strong>Cidade:</strong> {{ $previousEmployee->city }}</li>
        <li><strong>Estado:</strong> {{ $previousEmployee->state }}</li>
        <li><strong>Data de Início:</strong> {{ $previousEmployee->start_date }}</li>
    </ul>
    @endif
    
    <p>Esta atualização foi processada em {{ $employee->updated_at->format('d/m/Y H:i:s') }}.</p>
    
    <hr>
    <p><small>Este é um email automático do sistema Convenia.</small></p>
</body>
</html>