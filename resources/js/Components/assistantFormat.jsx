// Formateador compartido de las respuestas del Asistente PROVALE.
// Convierte texto plano en bloques legibles: título, pasos numerados, viñetas,
// tablas con barras verticales y notas. Limpia restos de Markdown (**, *, #, `).

function stripStraySymbols(text) {
    return text
        .replace(/`{1,3}([^`]*)`{1,3}/g, '$1')
        .replace(/^#{1,6}\s*/, '')
        .replace(/_{2,}/g, '')
        .replace(/\s*\|\s*$/, (m) => m); // no-op, conserva tablas
}

// Inline: **negrita**, *cursiva*, [texto](url). Cualquier * o _ suelto se elimina.
export function renderInline(text, keyPrefix) {
    const clean = stripStraySymbols(text);
    const pattern = /(\*\*([^*]+)\*\*|__([^_]+)__|\*([^*\n]+)\*|`([^`]+)`|\[([^\]]+)\]\((https?:\/\/[^\s)]+)\))/g;
    const nodes = [];
    let lastIndex = 0;
    let match;
    let i = 0;

    while ((match = pattern.exec(clean)) !== null) {
        if (match.index > lastIndex) {
            nodes.push(clean.slice(lastIndex, match.index).replace(/[*_]/g, ''));
        }
        const key = `${keyPrefix}-i${i++}`;
        if (match[2] || match[3]) {
            nodes.push(<strong key={key}>{match[2] || match[3]}</strong>);
        } else if (match[4]) {
            nodes.push(<em key={key}>{match[4]}</em>);
        } else if (match[5]) {
            nodes.push(<code key={key}>{match[5]}</code>);
        } else if (match[6] && match[7]) {
            nodes.push(
                <a key={key} href={match[7]} target="_blank" rel="noreferrer noopener">
                    {match[6]}
                </a>,
            );
        }
        lastIndex = match.index + match[0].length;
    }

    if (lastIndex < clean.length) {
        nodes.push(clean.slice(lastIndex).replace(/[*_]/g, ''));
    }

    return nodes.length ? nodes : [clean.replace(/[*_]/g, '')];
}

function isTableRow(line) {
    return /^\|.*\|$/.test(line);
}

function isTableDivider(line) {
    return /^\|[\s:|-]+\|$/.test(line) && line.includes('-');
}

function splitCells(line) {
    return line
        .replace(/^\|/, '')
        .replace(/\|$/, '')
        .split('|')
        .map((cell) => cell.trim());
}

// Agrupa las líneas en bloques (tabla | linea).
function toBlocks(rawLines) {
    const blocks = [];
    for (let i = 0; i < rawLines.length; i += 1) {
        const line = rawLines[i];
        if (isTableRow(line) && isTableRow(rawLines[i + 1] || '') && isTableDivider(rawLines[i + 1])) {
            const header = splitCells(line);
            const rows = [];
            i += 2;
            while (i < rawLines.length && isTableRow(rawLines[i])) {
                rows.push(splitCells(rawLines[i]));
                i += 1;
            }
            i -= 1;
            blocks.push({ type: 'table', header, rows });
        } else {
            blocks.push({ type: 'line', text: line });
        }
    }
    return blocks;
}

export function FormattedAnswer({ content }) {
    const rawLines = String(content ?? '')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);

    const blocks = toBlocks(rawLines);
    let lineIndex = 0;

    return (
        <div className="assistant-answer-content">
            {blocks.map((block, index) => {
                if (block.type === 'table') {
                    return (
                        <div key={`t-${index}`} className="assistant-table-wrap">
                            <table className="assistant-table">
                                <thead>
                                    <tr>
                                        {block.header.map((cell, ci) => (
                                            <th key={ci}>{renderInline(cell, `t-${index}-h-${ci}`)}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {block.rows.map((row, ri) => (
                                        <tr key={ri}>
                                            {row.map((cell, ci) => (
                                                <td key={ci}>{renderInline(cell, `t-${index}-${ri}-${ci}`)}</td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    );
                }

                const currentLineIndex = lineIndex;
                lineIndex += 1;
                const line = block.text;
                const key = `${index}-${line.slice(0, 12)}`;
                const heading = line.match(/^#{1,6}\s+(.+)$/);
                const step = line.match(/^(\d+)[.)]\s+(.+)$/);
                const bullet = line.match(/^[-*•]\s+(.+)$/);
                const isTitle = currentLineIndex === 0 && line.endsWith(':');

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
