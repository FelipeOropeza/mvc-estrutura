<!-- Componente: demo_htmx -->
<div id="comp-demo_htmx" class="component-wrapper mt-3 text-center">
    <!-- 
       O HTMX por padrão fará atualizações neste elemento 
       ou você pode engatinhar aqui para responder a um hx-trigger 
    -->
    <p>Componente gerado via Forge CLI!</p>
    
    <div class="alert alert-secondary d-inline-block">
        Cliques aqui: <span class="badge bg-primary fs-5"><?= $cliques ?? 0 ?></span>
    </div>
    <br>
    
    <!-- Este botão vai mandar uma requisição e vai substituir APENAS ESTE COMPONENTE -->
    <button hx-post="/api/comp-clique" hx-target="#comp-demo_htmx" hx-swap="outerHTML" 
            class="btn btn-sm btn-dark mt-2">
        <i class="bi bi-hand-index-thumb"></i> Simular Interação 
    </button>
</div>