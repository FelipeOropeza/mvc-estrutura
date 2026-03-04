<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Aplicação</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm my-8">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Criar nova conta</h2>

        <form action="/register" method="POST">
            <?= csrf_field() ?? '' ?>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nome">Nome</label>
                <input class="shadow appearance-none border <?= errors('nome') ? 'border-red-500' : '' ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="nome" type="text" name="nome" value="<?= htmlspecialchars(old('nome')) ?>" required>
                <?php if ($error = errors('nome')): ?><p class="text-red-500 text-xs italic mt-1"><?= $error ?></p><?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">E-mail</label>
                <input class="shadow appearance-none border <?= errors('email') ? 'border-red-500' : '' ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" name="email" value="<?= htmlspecialchars(old('email')) ?>" required>
                <?php if ($error = errors('email')): ?><p class="text-red-500 text-xs italic mt-1"><?= $error ?></p><?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="senha">Senha</label>
                <input class="shadow appearance-none border <?= errors('senha') ? 'border-red-500' : '' ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="senha" type="password" name="senha" required>
                <?php if ($error = errors('senha')): ?><p class="text-red-500 text-xs italic mt-1"><?= $error ?></p><?php endif; ?>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="senha_confirmacao">Confirme a Senha</label>
                <input class="shadow appearance-none border <?= errors('senha_confirmacao') ? 'border-red-500' : '' ?> rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="senha_confirmacao" type="password" name="senha_confirmacao" required>
                <?php if ($error = errors('senha_confirmacao')): ?><p class="text-red-500 text-xs italic mt-1"><?= $error ?></p><?php endif; ?>
            </div>
            
            <div class="flex items-center justify-between">
                <button class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    Registrar
                </button>
            </div>
            <div class="mt-4 text-center">
                <a href="/login" class="text-sm text-green-600 hover:underline">Já possui conta? Entre aqui</a>
            </div>
        </form>
    </div>

</body>
</html>
