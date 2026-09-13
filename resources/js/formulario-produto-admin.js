import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import '../css/formulario-produto-admin.css';

const EMPTY_HTML = ['<p><br></p>', '<p></p>', ''];

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('admin-product-form');
    const descriptionField = document.getElementById('description');
    const editorRoot = document.getElementById('description-editor');

    if (!form || !descriptionField || !editorRoot) {
        return;
    }

    const quill = new Quill(editorRoot, {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean'],
            ],
        },
    });

    const syncDescription = () => {
        const text = quill.getText().replace(/\u00a0/g, ' ').trim();

        if (text === '') {
            descriptionField.value = '';
            return;
        }

        const html = quill.root.innerHTML.trim();
        descriptionField.value = EMPTY_HTML.includes(html) ? '' : html;
    };

    const initialHtml = descriptionField.value.trim();

    if (initialHtml !== '') {
        quill.clipboard.dangerouslyPasteHTML(initialHtml);
    }

    quill.on('text-change', syncDescription);
    syncDescription();

    form.addEventListener('submit', () => {
        syncDescription();
    });

    editorRoot._quill = quill;
});
