<div class="store-photo">
    @if ($url)
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            class="store-photo__img"
            onerror="this.onerror=null;this.hidden=true;var fallback=this.nextElementSibling;if(fallback){fallback.hidden=false;}"
        >
        <span class="store-photo__fallback" hidden>{{ 'Imagem indisponível' }}</span>
    @else
        <span class="store-photo__fallback">Sem capa</span>
    @endif
</div>
