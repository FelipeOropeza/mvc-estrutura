# Helpers Globais

Atalhos diretos da Programação para facilitar implementações cruciais.
* `app()`: Devolve a base de Container.
* `logger()->info("Salvo com sucesso")`: Uma forma maravilhosa de ler ocorrências sem atrapalhar e avisar o usuário que teve Exceção. Vai silenciado ao arquivo `/storage/logs/`.
* `request()`: Abstrai toda a URL da Web que o usuário navegante acessou e todos seus Headers Seguros.
* `session()`: Lê variáveis que transitam pela RAM do Framework até sua View. Use `session()->flash('success', 'Cadastrado')`.
* `view()`: Chamada principal de Views (Ex: `view('painel/index', ...)` ).
* `old('nome_do_campo')`: Recupera lógicas mal preenchidas.
* `errors('nome_do_campo')`: Apresenta erros do Validator em tempo real na Interface da WEB.
* `route('nome_da_rota')`: Transforma um "Name" gerado no Web.php numa String de Domínio real com Query Params processados se necessário.
* `storage_url('path/to/file.jpg')`: Gera a URL pública para arquivos na pasta `/storage`. Essencial para exibir imagens enviadas por upload.
