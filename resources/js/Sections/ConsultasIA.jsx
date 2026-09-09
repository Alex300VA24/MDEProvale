import { useEffect, useRef, useState } from 'react';
import http from '../http';
import { FormattedAnswer } from '../Components/assistantFormat';

// Consultas IA: preguntas puntuales sobre datos del programa. El historial vive
// solo en memoria (sin sessionStorage): al cambiar de sección, recargar o volver
// a iniciar sesión no queda contexto. El límite de consultas por ventana de
// horas lo define el administrador en Sistema > Asistente IA.

const SUGERENCIAS = [
    '¿Cuántos comités activos hay este mes?',
    '¿Quién es la presidenta del comité ...?',
    '¿Cuántos beneficiarios tiene el comité ...?',
    '¿Cuántas pecosas se han generado este mes?',
    'Crea un reporte de pecosas de este mes',
    'Crea un reporte de beneficiarios del comité ...',
];

const MAX_HISTORIAL = 20;

function horaReinicio(iso) {
    if (!iso) return '';
    const fecha = new Date(iso);
    if (Number.isNaN(fecha.getTime())) return '';
    return fecha.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
}

export default function ConsultasIA() {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [limite, setLimite] = useState(null);
    const [bloqueado, setBloqueado] = useState(false);
    const endRef = useRef(null);
    const inputRef = useRef(null);

    useEffect(() => {
        endRef.current?.scrollIntoView({ behavior: 'smooth' });
        if (!loading && !bloqueado) inputRef.current?.focus();
    }, [messages, loading, bloqueado]);

    const enviar = async (texto) => {
        const contenido = texto.trim();
        if (!contenido || loading || bloqueado) return;

        const historial = [...messages, { role: 'user', content: contenido }].slice(-MAX_HISTORIAL);
        setMessages(historial);
        setInput('');
        setError('');
        setLoading(true);

        try {
            const { data } = await http.post('/api/asistente/chat', {
                mensajes: historial.map(({ role, content }) => ({ role, content })),
            });
            setMessages((actual) =>
                [...actual, { role: 'assistant', content: data.respuesta, accion: data.accion ?? null }].slice(-MAX_HISTORIAL),
            );
            if (data.limite) {
                setLimite(data.limite);
                if (data.limite.restantes <= 0) setBloqueado(true);
            }
        } catch (err) {
            const status = err.response?.status;
            if (status === 429) {
                setBloqueado(true);
                setLimite(err.response?.data?.limite ?? null);
                setError(err.response?.data?.mensaje || 'Alcanzaste el límite de consultas por ahora.');
            } else {
                setError(err.response?.data?.message || 'No se pudo contactar al asistente. Intenta nuevamente.');
            }
        } finally {
            setLoading(false);
        }
    };

    const onSubmit = (event) => {
        event.preventDefault();
        enviar(input);
    };

    // Las sugerencias solo rellenan el campo: el usuario ajusta el texto (nombre
    // del comité, periodo) antes de enviar.
    const usarSugerencia = (texto) => {
        if (bloqueado) return;
        setInput(texto);
        inputRef.current?.focus();
    };

    const restantes = limite?.restantes ?? null;
    const hora = horaReinicio(limite?.reinicia_en);

    return (
        <div className="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden flex flex-col h-[calc(100vh-11rem)] min-h-[520px]">
            <div className="px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat flex items-start justify-between gap-4">
                <div>
                    <h3 className="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
                        <i className="fas fa-robot text-leaf" /> Consultas IA
                    </h3>
                    <p className="text-earth text-xs sm:text-sm mt-1">
                        Preguntas puntuales sobre comités, presidentas, beneficiarios y pecosas. Pide un reporte
                        cuando lo necesites y ábrelo en una pestaña nueva.
                    </p>
                </div>
                {restantes !== null && (
                    <span className={`consulta-limit-badge ${bloqueado ? 'is-blocked' : ''}`}>
                        <i className="fas fa-gauge-high" aria-hidden="true" />
                        {restantes}/{limite.total} consultas
                        {hora && <span className="opacity-70">· reinicia {hora}</span>}
                    </span>
                )}
            </div>

            <div className="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 space-y-4 bg-base">
                {messages.length === 0 && (
                    <div className="space-y-3">
                        <p className="text-xs font-extrabold text-earth uppercase tracking-wider">Consultas frecuentes</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            {SUGERENCIAS.map((sugerencia) => (
                                <button
                                    key={sugerencia}
                                    type="button"
                                    onClick={() => usarSugerencia(sugerencia)}
                                    disabled={bloqueado}
                                    className="text-left text-sm font-semibold text-charcoal bg-white border-2 border-wheat rounded-xl px-3 py-2.5 hover:border-leaf/50 disabled:opacity-60 transition-all"
                                >
                                    <i className="fas fa-pen text-leaf mr-2" aria-hidden="true" />
                                    {sugerencia}
                                </button>
                            ))}
                        </div>
                        <p className="text-earth text-xs">
                            Al elegir una consulta se copia al campo de abajo; complétala con el nombre o código real
                            del comité antes de enviarla.
                        </p>
                    </div>
                )}

                {messages.map((message, index) =>
                    message.role === 'user' ? (
                        <div key={index} className="flex justify-end">
                            <div className="max-w-[85%] bg-blue text-white rounded-2xl rounded-br-sm px-4 py-2.5 text-sm font-semibold">
                                {message.content}
                            </div>
                        </div>
                    ) : (
                        <div key={index} className="flex gap-3">
                            <span className="w-8 h-8 rounded-xl bg-leaf-light text-leaf flex items-center justify-center flex-shrink-0">
                                <i className="fas fa-robot" aria-hidden="true" />
                            </span>
                            <div className="max-w-[85%] bg-white border-2 border-wheat rounded-2xl rounded-tl-sm px-4 py-3">
                                <FormattedAnswer content={message.content} />
                                {message.accion?.tipo === 'reporte' && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            window.open((window.APP_URL || '') + message.accion.url, '_blank', 'noopener')
                                        }
                                        className="consulta-report-btn"
                                    >
                                        <i className="fas fa-file-pdf" aria-hidden="true" /> {message.accion.label || 'Ver reporte'}
                                    </button>
                                )}
                            </div>
                        </div>
                    ),
                )}

                {loading && (
                    <div className="flex gap-3">
                        <span className="w-8 h-8 rounded-xl bg-leaf-light text-leaf flex items-center justify-center flex-shrink-0">
                            <i className="fas fa-robot" aria-hidden="true" />
                        </span>
                        <div className="bg-white border-2 border-wheat rounded-2xl rounded-tl-sm px-4 py-3 text-earth text-sm">
                            <i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" /> Consultando...
                        </div>
                    </div>
                )}

                {error && (
                    <div className="bg-clay-light text-clay text-sm font-semibold rounded-xl px-4 py-3 flex items-start gap-2" role="alert">
                        <i className="fas fa-circle-exclamation mt-0.5" aria-hidden="true" /> {error}
                    </div>
                )}

                <div ref={endRef} />
            </div>

            <form onSubmit={onSubmit} className="border-t-2 border-wheat p-3 sm:p-4 flex items-end gap-2">
                <label htmlFor="consulta-input" className="sr-only">
                    Escribe tu consulta
                </label>
                <textarea
                    id="consulta-input"
                    ref={inputRef}
                    rows="1"
                    maxLength="2000"
                    value={input}
                    onChange={(event) => setInput(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' && !event.shiftKey) onSubmit(event);
                    }}
                    placeholder={bloqueado ? 'Límite de consultas alcanzado' : 'Escribe tu consulta sobre PROVALE...'}
                    disabled={loading || bloqueado}
                    className="flex-1 resize-none px-3 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all disabled:opacity-60"
                />
                <button
                    type="submit"
                    disabled={loading || bloqueado || !input.trim()}
                    className="btn-primary flex items-center gap-2 disabled:opacity-60"
                    aria-label="Enviar consulta"
                >
                    <i className="fas fa-paper-plane" aria-hidden="true" />
                </button>
            </form>
        </div>
    );
}
