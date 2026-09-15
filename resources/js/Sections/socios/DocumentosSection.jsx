import { useEffect, useRef, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/socios-beneficiarios';

export const DOCUMENT_TYPE_LABELS = {
    ficha_fisica: 'Ficha Física Escaneada',
    dni_socia: 'Copia DNI Socia',
    carnet_gestacion: 'Carnet de Gestación',
    dni_beneficiario: 'Copia DNI Beneficiario',
    partida_nacimiento: 'Partida de Nacimiento',
    constancia_medica: 'Constancia Médica (Desnutrición/Discapacidad)',
};

const inputCls =
    'w-full px-3 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1';
const Optional = () => <span className="text-earth font-normal normal-case tracking-normal">(opcional)</span>;

const documentTypesFor = (attachableType) => (
    attachableType === 'partner'
        ? ['ficha_fisica', 'dni_socia', 'carnet_gestacion']
        : ['dni_beneficiario', 'partida_nacimiento', 'constancia_medica']
);

export async function uploadDocumentAttachment(attachableType, attachableId, document) {
    if (!attachableId || !document?.file) return null;

    const formData = new FormData();
    formData.append('attachable_type', attachableType);
    formData.append('attachable_id', attachableId);
    formData.append('document_type', document.documentType);
    formData.append('file', document.file);

    const response = await http.post(`${BASE}/documents`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data;
}

export function PendingDocumentSection({
    attachableType,
    value,
    onChange,
    inputId,
    allowedTypes = null,
    showTypeSelect = true,
    title = 'Documento adjunto',
}) {
    const toast = useToast();
    const fileInputRef = useRef(null);
    const availableTypes = allowedTypes || documentTypesFor(attachableType);
    const document = value || { documentType: availableTypes[0], file: null };

    const handleFileChange = (event) => {
        const selected = event.target.files?.[0] || null;
        if (!selected) {
            onChange({ ...document, file: null });
            return;
        }

        if (selected.size > 10 * 1024 * 1024) {
            toast.error('El archivo no debe exceder los 10MB.');
            event.target.value = '';
            return;
        }

        const allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!allowedMimes.includes(selected.type)) {
            toast.error('Formato no permitido. Use PDF, JPG, PNG o WEBP.');
            event.target.value = '';
            return;
        }

        onChange({ ...document, file: selected });
    };

    const clearFile = () => {
        onChange({ ...document, file: null });
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    return (
        <div className="rounded-xl border border-wheat bg-gray-50/80 p-3 space-y-2">
            <div>
                <span className="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">
                    <i className="fas fa-paperclip text-leaf mr-1.5" /> {title} <Optional />
                </span>
                <p className="mt-1 text-xs text-earth">Se subirá automáticamente al guardar el registro.</p>
            </div>
            <div className={`grid grid-cols-1 gap-2 ${showTypeSelect ? 'sm:grid-cols-2' : ''}`}>
                {showTypeSelect && (
                    <div>
                        <label className={labelCls}>Tipo de documento <Optional /></label>
                        <select
                            value={document.documentType}
                            onChange={(event) => onChange({ ...document, documentType: event.target.value })}
                            className={inputCls}
                        >
                            {availableTypes.map((type) => (
                                <option key={type} value={type}>{DOCUMENT_TYPE_LABELS[type] || type}</option>
                            ))}
                        </select>
                    </div>
                )}
                <div>
                    <label htmlFor={inputId} className={labelCls}>
                        Archivo (PDF, JPG, PNG, WEBP; máx. 10 MB) <Optional />
                    </label>
                    <input
                        ref={fileInputRef}
                        id={inputId}
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        onChange={handleFileChange}
                        className="block w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-leaf file:text-white hover:file:opacity-90"
                    />
                </div>
            </div>
            {document.file && (
                <div className="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-xs text-charcoal">
                    <span className="truncate font-semibold">{document.file.name}</span>
                    <button type="button" onClick={clearFile} className="shrink-0 font-bold text-clay hover:text-red-700">
                        Quitar
                    </button>
                </div>
            )}
        </div>
    );
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

export default function DocumentosSection({
    attachableType,
    attachableId,
    initialDocuments = [],
    allowedTypes = null,
    onDocumentsChange,
    canUpload = true,
    canDelete = true,
    canDownload = true,
    title = 'Documentos Adjuntos',
    highlightType = null,
}) {
    const toast = useToast();
    const [documents, setDocuments] = useState(initialDocuments);
    const [selectedType, setSelectedType] = useState(
        allowedTypes?.[0] || (attachableType === 'partner' ? 'ficha_fisica' : 'dni_beneficiario')
    );
    const [file, setFile] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [previewDoc, setPreviewDoc] = useState(null);
    const [openingId, setOpeningId] = useState(null);
    const [deletingId, setDeletingId] = useState(null);

    const availableTypes = allowedTypes || documentTypesFor(attachableType);

    useEffect(() => () => {
        if (previewDoc?.previewUrl) URL.revokeObjectURL(previewDoc.previewUrl);
    }, [previewDoc]);

    const handleFileChange = (e) => {
        const selected = e.target.files?.[0];
        if (!selected) return;

        if (selected.size > 10 * 1024 * 1024) {
            toast.error('El archivo no debe exceder los 10MB.');
            e.target.value = '';
            return;
        }

        const allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!allowedMimes.includes(selected.type)) {
            toast.error('Formato no permitido. Use PDF, JPG, PNG o WEBP.');
            e.target.value = '';
            return;
        }

        setFile(selected);
    };

    const handleUpload = async (e) => {
        e?.preventDefault();
        if (!file || !attachableId) return;

        setUploading(true);
        try {
            const newDoc = await uploadDocumentAttachment(attachableType, attachableId, {
                documentType: selectedType,
                file,
            });
            const updated = [newDoc, ...documents];
            setDocuments(updated);
            setFile(null);
            const fileInput = document.getElementById(`doc-file-input-${attachableType}-${attachableId}`);
            if (fileInput) fileInput.value = '';

            toast.success('Documento subido correctamente.');
            if (onDocumentsChange) onDocumentsChange(updated);
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo subir el documento.'));
        } finally {
            setUploading(false);
        }
    };

    const handleDelete = async (docId) => {
        setDeletingId(docId);
        try {
            await http.delete(`${BASE}/documents/${docId}`);
            const updated = documents.filter((d) => d.id !== docId);
            setDocuments(updated);
            toast.success('Documento eliminado.');
            if (onDocumentsChange) onDocumentsChange(updated);
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el documento.'));
        } finally {
            setDeletingId(null);
        }
    };

    const isImage = (doc) => doc.mime_type?.startsWith('image/');

    const handleOpenDocument = async (doc) => {
        const opensInModal = isImage(doc);
        const newTab = opensInModal ? null : window.open('', '_blank');

        if (!opensInModal && !newTab) {
            toast.error('El navegador bloqueó la nueva pestaña. Permita ventanas emergentes e inténtelo de nuevo.');
            return;
        }

        if (newTab) newTab.opener = null;

        setOpeningId(doc.id);
        try {
            const response = await http.get(`${BASE}/documents/${doc.id}`, {
                responseType: 'blob',
            });
            const objectUrl = URL.createObjectURL(response.data);

            if (opensInModal) {
                setPreviewDoc({ ...doc, previewUrl: objectUrl });
                return;
            }

            newTab.location.replace(objectUrl);
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60_000);
        } catch (err) {
            if (newTab && !newTab.closed) newTab.close();
            toast.error(errorMessage(err, 'No se pudo abrir el documento.'));
        } finally {
            setOpeningId(null);
        }
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-charcoal uppercase tracking-wider flex items-center gap-1.5">
                    <i className="fas fa-paperclip text-leaf" /> {title}
                </span>
                <span className="text-[11px] font-semibold text-earth">
                    {documents.length} {documents.length === 1 ? 'archivo' : 'archivos'}
                </span>
            </div>

            {/* Listado de documentos */}
            {documents.length === 0 ? (
                <div className="rounded-xl border border-dashed border-wheat bg-gray-50/50 p-3 text-center text-xs text-earth">
                    No hay documentos adjuntos registrados.
                </div>
            ) : (
                <div className="space-y-2">
                    {documents.map((doc) => {
                        const isHighlighted = highlightType && doc.document_type === highlightType;
                        const label = DOCUMENT_TYPE_LABELS[doc.document_type] || doc.document_type;
                        return (
                            <div
                                key={doc.id}
                                className={`flex items-center justify-between gap-3 p-2.5 rounded-xl border transition-all ${
                                    isHighlighted
                                        ? 'border-leaf bg-leaf-light/30'
                                        : 'border-wheat bg-white hover:border-leaf/50'
                                }`}
                            >
                                <div className="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div
                                        className={`h-9 w-9 rounded-lg flex items-center justify-center shrink-0 ${
                                            isImage(doc)
                                                ? 'bg-sky-light text-sky'
                                                : 'bg-clay-light text-clay'
                                        }`}
                                    >
                                        <i className={`fas ${isImage(doc) ? 'fa-file-image' : 'fa-file-pdf'} text-base`} />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-bold text-charcoal truncate">{label}</span>
                                            {isHighlighted && (
                                                <span className="badge badge-success text-[10px] py-0 px-1.5">Ficha física</span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2 text-[11px] text-earth truncate">
                                            <span className="truncate">{doc.file_name}</span>
                                            <span>•</span>
                                            <span className="shrink-0">{formatBytes(doc.file_size)}</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-1 shrink-0">
                                    {isImage(doc) ? (
                                        <button
                                            type="button"
                                            onClick={() => handleOpenDocument(doc)}
                                            disabled={openingId === doc.id}
                                            className="btn-action bg-sky-light text-sky hover:bg-sky hover:text-white"
                                            title="Abrir documento"
                                            aria-label={`Abrir ${label}`}
                                        >
                                            <i className={`fas ${openingId === doc.id ? 'fa-spinner fa-spin' : 'fa-eye'}`} />
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => handleOpenDocument(doc)}
                                            disabled={openingId === doc.id}
                                            className="btn-action bg-leaf-light text-leaf hover:bg-leaf hover:text-white"
                                            title="Abrir documento"
                                            aria-label={`Abrir ${label}`}
                                        >
                                            <i className={`fas ${openingId === doc.id ? 'fa-spinner fa-spin' : 'fa-eye'}`} />
                                        </button>
                                    )}

                                    {canDelete && (
                                        <button
                                            type="button"
                                            disabled={deletingId === doc.id}
                                            onClick={() => handleDelete(doc.id)}
                                            className="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white disabled:opacity-50"
                                            title="Eliminar archivo"
                                        >
                                            <i className={`fas ${deletingId === doc.id ? 'fa-spinner fa-spin' : 'fa-trash'}`} />
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Formulario de subida directa si attachableId existe */}
            {canUpload && attachableId && (
                <div className="rounded-xl border border-wheat bg-gray-50/80 p-3 space-y-2">
                    <span className="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">
                        Subir nuevo adjunto
                    </span>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label className={labelCls}>Tipo de documento</label>
                            <select
                                value={selectedType}
                                onChange={(e) => setSelectedType(e.target.value)}
                                className={inputCls}
                            >
                                {availableTypes.map((t) => (
                                    <option key={t} value={t}>
                                        {DOCUMENT_TYPE_LABELS[t] || t}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Archivo (PDF, JPG, PNG, WEBP máx 10MB)</label>
                            <input
                                id={`doc-file-input-${attachableType}-${attachableId}`}
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                onChange={handleFileChange}
                                className="block w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-leaf file:text-white hover:file:opacity-90"
                            />
                        </div>
                    </div>

                    {file && (
                        <div className="flex justify-end pt-1">
                            <button
                                type="button"
                                onClick={handleUpload}
                                disabled={uploading}
                                className="btn-primary text-xs font-bold py-1.5 px-3 flex items-center gap-1.5 disabled:opacity-50"
                            >
                                <i className={`fas ${uploading ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'}`} />
                                {uploading ? 'Subiendo...' : 'Confirmar subida'}
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Modal de previsualización de imágenes */}
            {previewDoc && (
                <Modal
                    open
                    onClose={() => setPreviewDoc(null)}
                    title={DOCUMENT_TYPE_LABELS[previewDoc.document_type] || previewDoc.file_name}
                    icon="fa-file-image"
                    maxWidth="sm:max-w-3xl"
                >
                    <div className="p-4 flex flex-col items-center justify-center bg-gray-900/5 rounded-b-2xl">
                        <img
                            src={previewDoc.previewUrl}
                            alt={previewDoc.file_name}
                            className="max-h-[75vh] w-auto max-w-full rounded-lg object-contain shadow-md"
                        />
                        <div className="mt-3 flex items-center justify-between w-full text-xs text-earth px-2">
                            <span>{previewDoc.file_name} ({formatBytes(previewDoc.file_size)})</span>
                            {canDownload && (
                                <a
                                    href={previewDoc.previewUrl}
                                    download={previewDoc.file_name}
                                    className="btn-secondary text-xs font-bold flex items-center gap-1.5"
                                >
                                    <i className="fas fa-download" /> Descargar original
                                </a>
                            )}
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
}
