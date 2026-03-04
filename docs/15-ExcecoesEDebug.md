# Tratamento de Exceções e Debug Bar

Esse motor usa injeção de ExceptionGlobal nativa (Na pasta `core/Exceptions/Handler.php`) que intercepta TUDO que crasha seu site e impede dele expor os vazamentos em Nuvem, caso configurado corretamente.

Se a variável do seu `.env` contiver:
```env
APP_DEBUG=true
```
A arquitetura irá "ligar" uma **Debug Bar Interativa HTML Deslumbrante** similar à "Whoops / Ignition". Ela pinta na tela, cor de rosa e vermelho com detalhes, que linha exata seu programa crashou (`Stack Trace`) para você depurar.
* **MUITO CUIDADO:** Coloque **SEMPRE** `false` quando jogar para internet/host. Quando setada como False, o sistema vai pintar na tela apenas um grande e calmo erro *HTTP 500* para o visitante de maneira amigável, e salvará a bomba relógio silenciada na pasta `/storage/logs/app.log` para você conseguir investigar os vazamentos sem expor seu servidor a hackers!
