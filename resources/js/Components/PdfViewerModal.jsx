import Modal from './Modal';

export default function PdfViewerModal({ open, onClose, url, title = 'Documento PDF' }) {
    if (!open || !url) return null;

    return (
        <Modal open onClose={onClose} title={title} icon="fa-file-pdf" iconClass="text-clay" maxWidth="sm:max-w-5xl">
            <div className="p-4">
                <iframe
                    src={`${url}#toolbar=1&navpanes=0`}
                    title={title}
                    className="h-[75vh] w-full rounded-xl border-2 border-wheat bg-base"
                />
                <div className="mt-4 flex justify-between gap-3 border-t-2 border-wheat pt-4">
                    <button type="button" onClick={onClose} className="btn-secondary">Cerrar</button>
                    <a href={url} target="_blank" rel="noreferrer" className="btn-primary">
                        <i className="fas fa-up-right-from-square mr-2" aria-hidden="true" />
                        Abrir en pestaña nueva
                    </a>
                </div>
            </div>
        </Modal>
    );
}
