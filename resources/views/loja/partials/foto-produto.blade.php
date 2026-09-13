<div class="foto-produto">
    @if ($url)
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            class="foto-produto__img"
            onerror="this.onerror=null;this.hidden=true;var fallback=this.nextElementSibling;if(fallback){fallback.hidden=false;}"
        >
        <span class="foto-produto__fallback" hidden>{{ 'Imagem indisponível' }}</span>
    @else
        <span class="foto-produto__fallback">Sem capa</span>
    @endif
</div>
