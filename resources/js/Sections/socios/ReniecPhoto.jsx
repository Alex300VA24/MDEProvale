import { useEffect, useState } from 'react';
import { personFullName } from './format';

export default function ReniecPhoto({ persona, loading, className = 'h-36 w-28' }) {
    const [failed, setFailed] = useState(false);
    const fullName = personFullName(persona);

    useEffect(() => {
        setFailed(false);
    }, [persona?.id, persona?.photo]);

    if (loading) {
        return (
            <div
                className={`${className} shrink-0 animate-pulse rounded-xl bg-wheat flex items-center justify-center`}
                role="status"
                aria-label="Cargando foto de RENIEC"
            >
                <i className="fas fa-spinner fa-spin text-earth/50" />
            </div>
        );
    }

    if (!persona?.photo || failed) {
        return (
            <div className={`flex ${className} shrink-0 flex-col items-center justify-center rounded-xl bg-gray-100 px-2 text-center text-earth border border-wheat/50`}>
                <i className="fas fa-user text-3xl text-slate-400" aria-hidden="true" />
                <span className="mt-1.5 text-[11px] font-semibold text-slate-500 leading-tight">Sin foto RENIEC</span>
            </div>
        );
    }

    return (
        <img
            src={persona.photo}
            alt={'Foto de RENIEC de ' + fullName}
            width="112"
            height="144"
            decoding="async"
            onError={() => setFailed(true)}
            className={`${className} shrink-0 rounded-xl bg-gray-100 object-cover border-2 border-wheat shadow-sm`}
        />
    );
}
