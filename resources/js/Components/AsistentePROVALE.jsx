import { useEffect, useRef, useState } from 'react';
import http from '../http';

export const ASSISTANT_STORAGE_KEY = 'asistente_historial';

const SUGGESTIONS = [
    {
        icon: 'fa-clipboard-user',
        label: 'Padrón de beneficiarios',
        description: 'Generar listado de socios y sus beneficiarios.',
        prompt: '¿Cómo genero el padrón de socios y beneficiarios?',
    },
    {
        icon: 'fa-people-roof',
        label: 'Padrón de Clubes de Madres',
        description: 'Consultar beneficiarios agrupados por comité.',
        prompt: '¿Cómo genero el padrón de Clubes de Madres por comité?',
    },
    {
        icon: 'fa-ranking-star',
        label: 'Comité con más beneficiarios',
        description: 'Ver clasificación de comités por beneficiarios.',
        prompt: '¿Cómo consulto el comité con más beneficiarios?',
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
    {
        icon: 'fa-truck-ramp-box',
        label: 'Repartición mensual',
        description: 'Calcular raciones por comité y descargar PDF.',
        prompt: '¿Cómo genero la repartición mensual de raciones por comité?',
    },
];

function loadHistory() {
    try {
        const value = JSON.parse(sessionStorage.getItem(ASSISTANT_STORAGE_KEY) || '[]');
        return Array.isArray(value)
            ? value.filter((message) => ['user', 'assistant'].includes(message?.role) && typeof message?.content === 'string').slice(-20)
            : [];
    } catch {
        sessionStorage.removeItem(ASSISTANT_STORAGE_KEY);
        return [];
    }
}

// Parser Markdown ligero para las respuestas del asistente.
// Soporta: **negrita**, *cursiva*, `código`, [enlace](url), encabezados (#),
// listas numeradas ("1. ") y viñetas ("- ", "* ", "• "). Los emojis se muestran tal cual.
function renderInline(text, keyPrefix) {
    const pattern = /(\*\*([^*]+)\*\*|__([^_]+)__|\*([^*]+)\*|_([^_]+)_|`([^`]+)`|\[([^\]]+)\]\((https?:\/\/[^\s)]+)\))/g;
    const nodes = [];
    let lastIndex = 0;
    let match;
    let i = 0;

    while ((match = pattern.exec(text)) !== null) {
        if (match.index > lastIndex) {
            nodes.push(text.slice(lastIndex, match.index));
        }
        const key = `${keyPrefix}-i${i++}`;
        if (match[2] || match[3]) {
            nodes.push(<strong key={key}>{match[2] || match[3]}</strong>);
        } else if (match[4] || match[5]) {
            nodes.push(<em key={key}>{match[4] || match[5]}</em>);
        } else if (match[6]) {
            nodes.push(<code key={key}>{match[6]}</code>);
        } else if (match[7] && match[8]) {
            nodes.push(
                <a key={key} href={match[8]} target="_blank" rel="noreferrer noopener">
                    {match[7]}
                </a>,
            );
        }
        lastIndex = match.index + match[0].length;
    }

    if (lastIndex < text.length) {
        nodes.push(text.slice(lastIndex));
    }

    return nodes.length ? nodes : [text];
}

function FormattedAnswer({ content }) {
    const lines = content.split('\n').map((line) => line.trim()).filter(Boolean);

    return (
        <div className="assistant-answer-content">
            {lines.map((line, index) => {
                const key = `${index}-${line.slice(0, 12)}`;
                const heading = line.match(/^#{1,6}\s+(.+)$/);
                const step = line.match(/^(\d+)[.)]\s+(.+)$/);
                const bullet = line.match(/^[-*•]\s+(.+)$/);
                const isTitle = index === 0 && line.endsWith(':');

                if (heading) {
                    return (
                        <p key={key} className="assistant-answer-title">
                            {renderInline(heading[1], key)}
                        </p>
                    );
                }

                if (step) {
                    return (
                        <div key={key} className="assistant-answer-step">
                            <span>{step[1]}</span>
                            <p>{renderInline(step[2], key)}</p>
                        </div>
                    );
                }

                if (bullet) {
                    return (
                        <div key={key} className="assistant-answer-bullet">
                            <i className="fas fa-check" aria-hidden="true" />
                            <p>{renderInline(bullet[1], key)}</p>
                        </div>
                    );
                }

                return (
                    <p key={key} className={isTitle ? 'assistant-answer-title' : 'assistant-answer-note'}>
                        {renderInline(line, key)}
                    </p>
                );
            })}
        </div>
    );
}

export default function AsistentePROVALE() {
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState(loadHistory);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const endRef = useRef(null);
    const inputRef = useRef(null);

    useEffect(() => {
        if (messages.length) {
            sessionStorage.setItem(ASSISTANT_STORAGE_KEY, JSON.stringify(messages.slice(-20)));
        } else {
            sessionStorage.removeItem(ASSISTANT_STORAGE_KEY);
        }
    }, [messages]);

    useEffect(() => {
        if (!open) return;
        endRef.current?.scrollIntoView({ behavior: 'smooth' });
        if (!loading) inputRef.current?.focus();
    }, [open, messages, loading]);

    const submitMessage = async (content) => {
        const cleanContent = content.trim();
        if (!cleanContent || loading) return;

        const history = [...messages, { role: 'user', content: cleanContent }].slice(-20);
        setMessages(history);
        setInput('');
        setError('');
        setLoading(true);

        try {
            const response = await http.post('/api/asistente/chat', { mensajes: history });
            setMessages((current) => [
                ...current,
                { role: 'assistant', content: response.data.respuesta },
            ].slice(-20));
        } catch (requestError) {
            setError(requestError.response?.data?.message || 'No se pudo contactar al asistente. Intenta nuevamente.');
        } finally {
            setLoading(false);
        }
    };

    const sendMessage = (event) => {
        event.preventDefault();
        submitMessage(input);
    };

    const resetConversation = () => {
        setMessages([]);
        setInput('');
        setError('');
    };

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
                            <button type="button" onClick={() => setOpen(false)} className="assistant-header-button" aria-label="Minimizar asistente">
                                <i className="fas fa-minus" aria-hidden="true" />
                            </button>
                        </div>
                    </header>

                    <div className="assistant-messages" aria-live="polite">
                        <div className="assistant-welcome">
                            <span className="assistant-welcome-icon"><i className="fas fa-wand-magic-sparkles" aria-hidden="true" /></span>
                            <div>
                                <h3>¿En qué te ayudo?</h3>
                                <p>Te guío paso a paso para usar las funciones de PROVALE.</p>
                            </div>
                        </div>

                        {messages.length === 0 && (
                            <div className="assistant-suggestions" aria-label="Consultas sugeridas">
                                <p>Consultas frecuentes</p>
                                <div>
                                    {SUGGESTIONS.map((suggestion) => (
                                        <button key={suggestion.label} type="button" onClick={() => submitMessage(suggestion.prompt)} disabled={loading}>
                                            <i className={`fas ${suggestion.icon}`} aria-hidden="true" />
                                            <span className="assistant-suggestion-copy">
                                                <strong>{suggestion.label}</strong>
                                                <small>{suggestion.description}</small>
                                            </span>
                                            <i className="fas fa-chevron-right" aria-hidden="true" />
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
                            placeholder="¿Qué deseas hacer en PROVALE?"
                            disabled={loading}
                        />
                        <button type="submit" disabled={loading || !input.trim()} aria-label="Enviar mensaje">
                            <i className="fas fa-paper-plane" aria-hidden="true" />
                        </button>
                    </form>
                    <p className="assistant-disclaimer"><i className="fas fa-shield-halved" aria-hidden="true" /> Orientación segura sobre uso de PROVALE</p>
                </section>
            )}

            <button
                type="button"
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
