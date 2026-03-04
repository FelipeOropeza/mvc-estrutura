# Views e Interface de Usuário (UI)

Temos a view engatilhada a retornar PHP ou TWIG baseado no motor setado em `config/app.php`. 

Para renderizar (o core procura dentro de `app/Views/`):
```php
return view('produto/detalhes', [
    'nome' => 'Sabão em pó',
    'preco' => 12.00
]);
```

## Layouts Principais (Master Page) e Sections
A separação de Layouts evita que você repita `<head>` e Menus em todas as páginas. É totalmente suportado dependendo do "motor" ativo:

**Com Engine de PHP Puro (Padrão):** O framework possui um motor inteligente inspirado no *Blade* do Laravel.
No seu arquivo Mestre (`app/Views/layouts/app.php`):
```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $titulo ?? 'Meu Site' ?></title>
</head>
<body>
    <!-- Aqui vai renderizar o miolo das views filhas -->
    <?php $this->renderSection('content') ?>
</body>
</html>
```

Na sua view Filha (`app/Views/home.php`):
```php
<?php $this->layout('layouts/app', ['titulo' => 'Página Inicial']) ?>

<?php $this->section('content') ?>
    <h1>Bem vindo à tela inicial!</h1>
    
    <!-- Chamando um componente/partial isolado -->
    <?php $this->include('partials/botao_voltar', ['cor' => 'blue']) ?>
<?php $this->endSection() ?>
```

**Com Engine Twig:** A herança é nativa e segue o mesmo padrão poderoso:
```twig
{% extends "layouts/app.twig" %}
{% block content %}
    <h1>Listagem de Produtos</h1>
{% endblock %}
```

## Variáveis Compartilhadas (Globais)
Se você precisa que uma variável (Ex: `$usuarioLogado` ou `$configuracoesDoSite`) esteja disponível magicamente em TODAS as views e layouts sem ter que passar array por array em todo Controller, use o método `share` no seu `AppServiceProvider`:
```php
\Core\View\PhpEngine::share('usuarioLogado', session()->get('user_name'));
```

## Retornando feedbacks e erros do Validate() na Interface
O Framework mantém sessões invisíveis "Flash" que expiram e apagam no Reload seguinte da tela para lidar os formulários rejeitados.

```php
<form method="POST" action="/submeter">
    <?= csrf_field() ?>
    
    <!-- Mantenha o que o usuário preencheu usando old() caso ele erre algo e seja redirecionado para cá novamente -->
    <input type="text" name="email" value="<?= old('email') ?>">
    
    <!-- Mostre O ERRO mágico do #[Email('..')] de sua Model abaixo do Campo usando o errors()! -->
    <span style="color:red;"><?= errors('email') ?></span>
</form>
```
