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
