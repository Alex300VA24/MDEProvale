import { useState } from 'react';
import { useToast } from './Toast';
import { pdfLoadingHtml } from './pdfLoadingScreen';

// POST con el cuerpo en el request: evita el límite de largo de URL (414) en documentos con muchos datos.
const postOptions = (data) => {
    const xsrf = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json, application/pdf',
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf.split('=')[1]) } : {}),
            ...(meta ? { 'X-CSRF-TOKEN': meta } : {}),
        },
        body: new URLSearchParams(data).toString(),
    };
};

export default function PdfLinkButton({
    href,
    loadingTitle = 'Generando documento',
    loadingMessage = 'Procesando información, esto puede tardar unos segundos.',
    icon = 'fa-file-pdf',
    className,
    children,
    disabled = false,
    postData = null,
    ...rest
}) {
    const toast = useToast();
    const [loading, setLoading] = useState(false);

    const notify = (msg) => {
        if (toast?.error) toast.error(msg);
        else window.alert(msg);
    };

    const handleClick = async () => {
        if (disabled || loading || !href) return;
        setLoading(true);
        const preview = window.open('', '_blank');
        if (!preview) {
            setLoading(false);
            notify('Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.');
            return;
        }
        preview.document.write(pdfLoadingHtml(loadingTitle, loadingMessage));
        preview.document.close();
        try {
            const res = await fetch(href, postData ? postOptions(postData) : { headers: { Accept: 'application/pdf' } });
            if (!res.ok) {
                let msg = 'No se pudo generar el documento.';
                try {
                    const data = await res.json();
                    msg = data.message || msg;
                } catch {}
                throw new Error(msg);
            }
            const blob = await res.blob();
            preview.location = URL.createObjectURL(blob);
        } catch (err) {
            preview.close();
            notify(err.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <button type="button" onClick={handleClick} disabled={disabled || loading} className={className} {...rest}>
            <i className={`fas ${loading ? 'fa-spinner fa-spin' : icon}`} aria-hidden="true" />
            {children}
        </button>
    );
}
