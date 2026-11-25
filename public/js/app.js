document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm('Excluir esta tarefa? Esta ação não pode ser desfeita.')) {
            event.preventDefault();
        }
    });
});
