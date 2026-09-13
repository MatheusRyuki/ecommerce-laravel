import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import '../css/formulario-produto-admin.css';

const HTML_VAZIO = ['<p><br></p>', '<p></p>', ''];

const ROTULOS_BARRA = {
    bold: 'Negrito',
    italic: 'Itálico',
    underline: 'Sublinhado',
    strike: 'Riscado',
    link: 'Inserir link',
    clean: 'Limpar formatação',
    'list-ordered': 'Lista numerada',
    'list-bullet': 'Lista com marcadores',
};

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formulario-produto-admin');
    const campoDescricao = document.getElementById('descricao');
    const raizEditor = document.getElementById('editor-descricao');

    if (!form || !campoDescricao || !raizEditor) {
        return;
    }

    const quill = new Quill(raizEditor, {
        theme: 'snow',
        placeholder: 'Escreva a descrição do produto',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean'],
            ],
        },
    });

    const barra = raizEditor.previousElementSibling;

    if (barra?.classList.contains('ql-toolbar')) {
        barra.querySelectorAll('button, .ql-picker').forEach((elemento) => {
            const formato = [...elemento.classList]
                .find((classe) => classe.startsWith('ql-') && classe !== 'ql-picker')
                ?.slice(3);

            if (!formato) {
                return;
            }

            const valor = elemento.getAttribute('value') || elemento.dataset.value;
            const chave = valor ? `${formato}-${valor}` : formato;
            const rotulo = ROTULOS_BARRA[chave] ?? ROTULOS_BARRA[formato];

            if (rotulo) {
                elemento.setAttribute('aria-label', rotulo);
                elemento.setAttribute('title', rotulo);
            }
        });
    }

    const sincronizarDescricao = () => {
        const texto = quill.getText().replace(/\u00a0/g, ' ').trim();

        if (texto === '') {
            campoDescricao.value = '';
            return;
        }

        const html = quill.root.innerHTML.trim();
        campoDescricao.value = HTML_VAZIO.includes(html) ? '' : html;
    };

    const htmlInicial = campoDescricao.value.trim();

    if (htmlInicial !== '') {
        quill.clipboard.dangerouslyPasteHTML(htmlInicial);
    }

    quill.on('text-change', sincronizarDescricao);
    sincronizarDescricao();

    form.addEventListener('submit', () => {
        sincronizarDescricao();
    });

    raizEditor._quill = quill;
});

document.addEventListener('DOMContentLoaded', () => {
    const raiz = document.querySelector('[data-seletor-imagens]');

    if (! raiz) {
        return;
    }

    const input = raiz.querySelector('#imagens');
    const botao = raiz.querySelector('[data-abrir-imagens]');
    const listaNovas = raiz.querySelector('[data-previsao-novas]');
    const ordemPrevista = document.querySelector('[data-ordem-prevista]');
    const atuais = [...document.querySelectorAll('[data-imagem-atual]')];
    const urls = [];

    const limparUrls = () => {
        urls.splice(0).forEach((url) => URL.revokeObjectURL(url));
    };

    const atualizarOrdem = () => {
        const mantidas = atuais.filter((item) => ! item.querySelector('[data-remover-imagem]')?.checked);
        const novas = listaNovas ? [...listaNovas.querySelectorAll('li')] : [];

        atuais.forEach((item) => {
            const remover = item.querySelector('[data-remover-imagem]')?.checked === true;
            const capa = item.querySelector('[data-rotulo-capa]');
            const ordem = item.querySelector('[data-rotulo-ordem]');
            const remocao = item.querySelector('[data-rotulo-remocao]');
            item.classList.toggle('opacity-60', remover);
            if (remocao) {
                remocao.hidden = ! remover;
            }
            const indice = mantidas.indexOf(item);
            if (capa) {
                capa.hidden = remover || indice !== 0;
            }
            if (ordem) {
                ordem.textContent = remover || indice < 0 ? '' : `Ordem após salvar: ${indice + 1}`;
            }
        });

        novas.forEach((item, indiceNova) => {
            const capa = item.querySelector('[data-rotulo-capa]');
            const ordem = item.querySelector('[data-rotulo-ordem]');
            const indice = mantidas.length + indiceNova;
            if (capa) {
                capa.hidden = indice !== 0;
            }
            if (ordem) {
                ordem.textContent = `Ordem após salvar: ${indice + 1}`;
            }
        });

        if (ordemPrevista) {
            const total = mantidas.length + novas.length;
            const capa = mantidas[0]?.querySelector('img')?.alt || novas[0]?.querySelector('[data-nome-arquivo]')?.textContent;
            ordemPrevista.textContent = total === 0
                ? 'Nenhuma imagem permanecerá após salvar.'
                : `A capa será a primeira imagem mantida na ordem original; as novas entram no final. Total previsto: ${total}.`;
            if (capa && total > 0) {
                ordemPrevista.textContent += ` Capa prevista a partir da primeira posição.`;
            }
        }
    };

    const renderizarNovas = () => {
        if (! listaNovas || ! input) {
            return;
        }

        limparUrls();
        listaNovas.replaceChildren();

        const arquivos = [...input.files];
        listaNovas.hidden = arquivos.length === 0;

        arquivos.forEach((arquivo) => {
            const item = document.createElement('li');
            item.className = 'flex items-start gap-3 rounded-md border border-blue-200 bg-blue-50 p-3';
            const url = URL.createObjectURL(arquivo);
            urls.push(url);
            item.innerHTML = `
                <img src="${url}" alt="" class="h-16 w-16 rounded object-contain border border-gray-200 bg-white">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600" data-rotulo-capa hidden>Capa</p>
                    <p class="text-xs font-medium text-gray-800" data-nome-arquivo></p>
                    <p class="text-xs text-gray-500">Nova — entra ao final</p>
                    <p class="text-xs text-gray-500" data-rotulo-ordem></p>
                </div>
            `;
            item.querySelector('[data-nome-arquivo]').textContent = arquivo.name;
            listaNovas.append(item);
        });

        atualizarOrdem();
    };

    botao?.addEventListener('click', () => input?.click());
    input?.addEventListener('change', renderizarNovas);
    atuais.forEach((item) => {
        item.querySelector('[data-remover-imagem]')?.addEventListener('change', atualizarOrdem);
    });
    window.addEventListener('pagehide', limparUrls);
    atualizarOrdem();
});
