# Tratamento de Exceções e Debug Bar

Esse motor usa injeção de ExceptionGlobal nativa (Na pasta `core/Exceptions/Handler.php`) que intercepta TUDO que crasha seu site e impede dele expor os vazamentos em Nuvem, caso configurado corretamente.

Se a variável do seu `.env` contiver:
```env
APP_DEBUG=true
```
A arquitetura irá "ligar" uma **Tela de Erro Deslumbrante (Aesthetic Debug Page)**. Ela apresenta um design moderno em Dark Mode com:
* **Stack Trace Detalhado**: Indica a linha exata e a sequência de chamadas que causaram o crash.
* **Informações de Ambiente**: Código HTTP, Classe da Exceção e Mensagem limpa.
* **Suporte a HTMX**: Se o erro ocorrer em uma requisição HTMX, o framework realiza um *Retarget* automático para o `body`. Isso garante que o erro apareça em tela cheia e não "espremido" dentro de um componente da interface.

* **MUITO CUIDADO:** Coloque **SEMPRE** `false` quando jogar para internet/host. Quando setada como False, o sistema vai pintar na tela apenas um grande e calmo erro *HTTP 500* para o visitante de maneira amigável, e salvará a bomba relógio silenciada na pasta `/storage/logs/app.log` para você conseguir investigar sem expor seu servidor a hackers!
