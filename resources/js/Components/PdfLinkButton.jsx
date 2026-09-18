import { useState } from 'react';
import { useToast } from './Toast';
import { pdfLoadingHtml } from './pdfLoadingScreen';

export default function PdfLinkButton({
    href,
    loadingTitle = 'Generando documento',
    loadingMessage = 'Procesando información, esto puede tardar unos segundos.',
    icon = 'fa-file-pdf',
    className,
    children,
    disabled = false,
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
            const res = await fetch(href, { headers: { Accept: 'application/pdf' } });
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
