import { useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';
import { useRuntimeState } from '../../RuntimeState';

const BASE = '/api/dashboard/sistema/upload-config';

export default function ParametrosTab({ can }) {
    const toast = useToast();
    const { setUploadMaxMb } = useRuntimeState();
    const [data, setData] = useState(null);
    const [value, setValue] = useState(10);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        http.get(BASE).then(({ data: response }) => {
            setData(response.data);
            setValue(response.data.max_attachment_mb);
        }).catch((error) => toast.error(errorMessage(error, 'No se pudieron cargar los parámetros.')));
    }, []);

    const save = async (event) => {
        event.preventDefault();
        setSaving(true);
        try {
            const response = await http.put(BASE, { max_attachment_mb: Number(value) });
            const savedValue = Number(response.data?.data?.max_attachment_mb ?? value);
            setUploadMaxMb(savedValue);
            setValue(savedValue);
            setData((current) => ({ ...current, max_attachment_mb: savedValue }));
            toast.success('Límite actualizado en toda la interfaz.');
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo actualizar el límite.'));
        } finally {
            setSaving(false);
        }
    };

    if (!data) return <p className="py-8 text-center text-sm text-earth">Cargando parámetros...</p>;

    return (
        <form onSubmit={save} className="max-w-xl space-y-4">
            <div className="rounded-xl border-2 border-wheat bg-base p-4">
                <label htmlFor="max-attachment-mb" className="block text-xs font-bold uppercase tracking-wider text-slate-600">Peso máximo por archivo</label>
                <div className="mt-2 flex items-center gap-3">
                    <input id="max-attachment-mb" type="number" min={data.min} max={data.max} value={value} onChange={(event) => setValue(event.target.value)} className="w-28 rounded-xl border-2 border-wheat px-4 py-2 font-semibold focus:border-leaf focus:outline-none" disabled={!can.edit} />
                    <span className="font-bold text-charcoal">MB</span>
                </div>
                <p className="mt-2 text-xs text-earth">Rango: {data.min}–{data.max} MB. PHP: archivo {data.php_upload_max}, solicitud {data.php_post_max}.</p>
                {(data.php_upload_max === '2M' || data.php_post_max === '8M') && <p className="mt-2 text-xs font-bold text-clay">Servidor PHP limita archivos por debajo de 10 MB. Actualice php.ini y reinicie Apache.</p>}
            </div>
            {can.edit && <button type="submit" disabled={saving} className="btn-primary disabled:opacity-50"><i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />Guardar parámetro</button>}
        </form>
    );
}
