import { useEffect, useRef, useState } from 'react';
import http from '../http';
import { FormattedAnswer } from './assistantFormat';

// Widget flotante de ayuda. El historial vive solo en memoria: al recargar o
// volver a iniciar sesión no queda contexto guardado. El límite de consultas lo
// define el administrador (Sistema > Asistente IA) y el backend lo refleja aquí.

const MAX_HISTORIAL = 20;

const SUGGESTIONS = [
    {
        icon: 'fa-people-roof',
        label: 'Comités activos',
        description: 'Ver cuántos comités están vigentes.',
        prompt: '¿Cuántos comités activos hay este mes?',
    },
    {
        icon: 'fa-user-tie',
        label: 'Presidenta de un comité',
        description: 'Consultar quién preside un comité.',
        prompt: '¿Quién es la presidenta del comité ',
    },
    {
        icon: 'fa-file-circle-plus',
        label: 'Registrar una pecosa',
        description: 'Crear entrega de productos para un comité.',
        prompt: '¿Cómo registro una nueva pecosa?',
    },
    {
        icon: 'fa-boxes-stacked',
        label: 'Stock de productos',
        description: 'Revisar existencias, entradas y salidas.',
        prompt: '¿Cómo consulto el stock de productos?',
    },
];

function horaReinicio(iso) {
    if (!iso) return '';
    const fecha = new Date(iso);
    return Number.isNaN(fecha.getTime()) ? '' : fecha.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
}

export default function AsistentePROVALE({ open, onOpenChange }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [limite, setLimite] = useState(null);
    const [bloqueado, setBloqueado] = useState(false);
    const endRef = useRef(null);
    const inputRef = useRef(null);
    const launcherRef = useRef(null);

    const setOpen = (nextOpen) => {
        onOpenChange(typeof nextOpen === 'function' ? nextOpen(open) : nextOpen);
    };

    const closeAssistant = () => {
        onOpenChange(false);
        window.requestAnimationFrame(() => launcherRef.current?.focus());
    };

    useEffect(() => {
        if (!open) return;
        endRef.current?.scrollIntoView({ behavior: 'smooth' });
        if (!loading && !bloqueado) inputRef.current?.focus();
    }, [open, messages, loading, bloqueado]);

    useEffect(() => {
        if (!open) return undefined;

        const closeOnEscape = (event) => {
            if (event.key !== 'Escape') return;
            closeAssistant();
        };

        window.addEventListener('keydown', closeOnEscape);
        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [open, onOpenChange]);

    const submitMessage = async (content) => {
        const cleanContent = content.trim();
        if (!cleanContent || loading || bloqueado) return;

        const history = [...messages, { role: 'user', content: cleanContent }].slice(-MAX_HISTORIAL);
        setMessages(history);
        setInput('');
        setError('');
        setLoading(true);

        try {
            const response = await http.post('/api/asistente/chat', {
                mensajes: history.map(({ role, content: texto }) => ({ role, content: texto })),
            });
            const { data } = response;
            setMessages((current) =>
                [...current, {
                    role: 'assistant',
                    content: data.respuesta,
                    accion: data.accion ?? null,
                    sugerencias: data.sugerencias ?? [],
                }].slice(-MAX_HISTORIAL),
            );
            if (data.limite) {
                setLimite(data.limite);
                if (data.limite.restantes <= 0) setBloqueado(true);
            }
        } catch (requestError) {
            const status = requestError.response?.status;
            if (status === 429) {
                setBloqueado(true);
                setLimite(requestError.response?.data?.limite ?? null);
                setError(requestError.response?.data?.mensaje || 'Alcanzaste el límite de consultas por ahora.');
            } else {
                setError(requestError.response?.data?.message || 'No se pudo contactar al asistente. Intenta nuevamente.');
            }
        } finally {
            setLoading(false);
        }
    };

    const sendMessage = (event) => {
        event.preventDefault();
        submitMessage(input);
    };

    // Las sugerencias solo rellenan el campo; el usuario completa y envía.
    const useSuggestion = (prompt) => {
        if (bloqueado) return;
        setInput(prompt);
        inputRef.current?.focus();
    };

    const resetConversation = () => {
        setMessages([]);
        setInput('');
        setError('');
    };

    const restantes = limite?.restantes ?? null;
    const hora = horaReinicio(limite?.reinicia_en);

    return (
        <div className="assistant-widget">
            {open && (
                <section className="assistant-panel animate-scale-in" aria-label="Chat con el Asistente PROVALE">
                    <header className="assistant-header">
                        <div className="flex items-center gap-3 min-w-0">
                            <span className="assistant-avatar" aria-hidden="true"><i className="fas fa-comments" /></span>
                            <div className="min-w-0">
                                <h2 className="assistant-heading">Asistente PROVALE</h2>
                                <p className="assistant-status"><span /> Disponible para ayudarte</p>
                            </div>
                        </div>
                        <div className="assistant-header-actions">
                            {messages.length > 0 && (
                                <button type="button" onClick={resetConversation} className="assistant-header-button" aria-label="Iniciar nueva conversación" title="Nueva conversación">
                                    <i className="fas fa-plus" aria-hidden="true" />
                                </button>
                            )}
                            <button type="button" onClick={closeAssistant} className="assistant-header-button" aria-label="Minimizar asistente">
                                <i className="fas fa-minus" aria-hidden="true" />
                            </button>
                        </div>
                    </header>

                    <div className="assistant-messages" aria-live="polite">
                        <div className="assistant-welcome">
                            <span className="assistant-welcome-icon"><i className="fas fa-wand-magic-sparkles" aria-hidden="true" /></span>
                            <div>
                                <h3>¿En qué te ayudo?</h3>
                                <p>Entiendo preguntas en lenguaje natural, consulto datos y te guío dentro de PROVALE.</p>
                            </div>
                        </div>

                        {messages.length === 0 && (
                            <div className="assistant-suggestions" aria-label="Consultas sugeridas">
                                <p>Consultas frecuentes</p>
                                <div>
                                    {SUGGESTIONS.map((suggestion) => (
                                        <button key={suggestion.label} type="button" onClick={() => useSuggestion(suggestion.prompt)} disabled={bloqueado}>
                                            <i className={`fas ${suggestion.icon}`} aria-hidden="true" />
                                            <span className="assistant-suggestion-copy">
                                                <strong>{suggestion.label}</strong>
                                                <small>{suggestion.description}</small>
                                            </span>
                                            <i className="fas fa-pen" aria-hidden="true" />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {messages.map((message, index) => message.role === 'user' ? (
                            <div key={`${message.role}-${index}`} className="assistant-message assistant-message-user">
                                {message.content}
                            </div>
                        ) : (
                            <div key={`${message.role}-${index}`} className="assistant-message-row">
                                <span className="assistant-bot-avatar" aria-hidden="true"><i className="fas fa-comment-dots" /></span>
                                <div className="assistant-message assistant-message-bot">
                                    <FormattedAnswer content={message.content} />
                                    {message.sugerencias?.length > 0 && (
                                        <div className="assistant-clarification-options" aria-label="Opciones para aclarar la consulta">
                                            {message.sugerencias.map((suggestion) => (
                                                <button
                                                    key={suggestion.prompt}
                                                    type="button"
                                                    onClick={() => submitMessage(suggestion.prompt)}
                                                    disabled={loading || bloqueado}
                                                >
                                                    {suggestion.label}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                    {message.accion?.tipo === 'reporte' && (
                                        <button
                                            type="button"
                                            onClick={() => window.open((window.APP_URL || '') + message.accion.url, '_blank', 'noopener')}
                                            className="consulta-report-btn"
                                        >
                                            <i className="fas fa-file-pdf" aria-hidden="true" /> {message.accion.label || 'Ver reporte'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}

                        {loading && (
                            <div className="assistant-message-row">
                                <span className="assistant-bot-avatar" aria-hidden="true"><i className="fas fa-comment-dots" /></span>
                                <div className="assistant-message assistant-message-bot assistant-typing" aria-label="El asistente está escribiendo">
                                    <span /><span /><span />
                                </div>
                            </div>
                        )}
                        {error && <div className="assistant-error" role="alert"><i className="fas fa-circle-exclamation" aria-hidden="true" /> {error}</div>}
                        <div ref={endRef} />
                    </div>

                    <form onSubmit={sendMessage} className="assistant-form">
                        <label htmlFor="assistant-input" className="sr-only">Escribe tu consulta sobre PROVALE</label>
                        <textarea
                            id="assistant-input"
                            ref={inputRef}
                            rows="1"
                            maxLength="2000"
                            value={input}
                            onChange={(event) => setInput(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' && !event.shiftKey) sendMessage(event);
                            }}
                            placeholder={bloqueado ? 'Límite de consultas alcanzado' : '¿Qué deseas hacer en PROVALE?'}
                            disabled={loading || bloqueado}
                        />
                        <button type="submit" disabled={loading || bloqueado || !input.trim()} aria-label="Enviar mensaje">
                            <i className="fas fa-paper-plane" aria-hidden="true" />
                        </button>
                    </form>
                    <p className="assistant-disclaimer">
                        <i className="fas fa-shield-halved" aria-hidden="true" />
                        {restantes !== null
                            ? ` ${restantes}/${limite.total} consultas${hora ? ` · se reinicia ${hora}` : ''}`
                            : ' Orientación segura sobre uso de PROVALE'}
                    </p>
                </section>
            )}

            <button
                type="button"
                ref={launcherRef}
                className="assistant-launcher"
                onClick={() => setOpen((current) => !current)}
                aria-label={open ? 'Cerrar Asistente PROVALE' : 'Abrir Asistente PROVALE'}
                aria-expanded={open}
            >
                <i className={`fas ${open ? 'fa-times' : 'fa-comment-dots'}`} aria-hidden="true" />
            </button>
        </div>
    );
}
