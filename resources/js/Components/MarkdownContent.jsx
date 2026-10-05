import { Fragment } from 'react';

const inlinePattern = /(\*\*[^*\n]+\*\*|__[^_\n]+__|\*[^*\n]+\*|_[^_\n]+_|`[^`\n]+`|\[Fuente\s+\d+\])/gi;

function InlineMarkdown({ children }) {
    return String(children).split(inlinePattern).filter(Boolean).map((part, index) => {
        if (/^\*\*[^*]+\*\*$/.test(part) || /^__[^_]+__$/.test(part)) {
            return <strong key={index} className="font-bold text-navy">{part.slice(2, -2)}</strong>;
        }

        if (/^\*[^*]+\*$/.test(part) || /^_[^_]+_$/.test(part)) {
            return <em key={index}>{part.slice(1, -1)}</em>;
        }

        if (/^`[^`]+`$/.test(part)) {
            return <code key={index} className="rounded bg-mist/70 px-1 py-0.5 font-mono text-[0.9em] text-navy">{part.slice(1, -1)}</code>;
        }

        if (/^\[Fuente\s+\d+\]$/i.test(part)) {
            return <span key={index} className="whitespace-nowrap font-semibold text-blue">{part}</span>;
        }

        return <Fragment key={index}>{part}</Fragment>;
    });
}

function isBlockStart(line) {
    return /^(#{1,3})\s+/.test(line)
        || /^\s*[-*]\s+/.test(line)
        || /^\s*\d+[.)]\s+/.test(line)
        || /^>\s?/.test(line);
}

export default function MarkdownContent({ content }) {
    const lines = String(content || '').replace(/\r\n?/g, '\n').split('\n');
    const blocks = [];

    for (let index = 0; index < lines.length;) {
        const line = lines[index].trim();

        if (!line) {
            index += 1;
            continue;
        }

        const heading = line.match(/^(#{1,3})\s+(.+)$/);
        if (heading) {
            const level = heading[1].length;
            const Tag = level === 1 ? 'h2' : level === 2 ? 'h3' : 'h4';
            const className = level === 1
                ? 'font-heading text-lg font-extrabold leading-snug text-navy'
                : level === 2
                    ? 'font-heading text-base font-extrabold leading-snug text-navy'
                    : 'font-heading text-sm font-bold leading-snug text-navy';
            blocks.push(<Tag key={`heading-${index}`} className={className}><InlineMarkdown>{heading[2]}</InlineMarkdown></Tag>);
            index += 1;
            continue;
        }

        const unordered = line.match(/^[-*]\s+(.+)$/);
        if (unordered) {
            const items = [];
            while (index < lines.length) {
                const match = lines[index].trim().match(/^[-*]\s+(.+)$/);
                if (!match) break;
                items.push(<li key={index} className="pl-1"><InlineMarkdown>{match[1]}</InlineMarkdown></li>);
                index += 1;
                let nextIndex = index;
                while (nextIndex < lines.length && !lines[nextIndex].trim()) nextIndex += 1;
                if (/^[-*]\s+/.test(lines[nextIndex]?.trim() || '')) {
                    index = nextIndex;
                }
            }
            blocks.push(<ul key={`ul-${index}`} className="list-disc space-y-1.5 pl-5 marker:text-blue">{items}</ul>);
            continue;
        }

        const ordered = line.match(/^\d+[.)]\s+(.+)$/);
        if (ordered) {
            const items = [];
            while (index < lines.length) {
                const match = lines[index].trim().match(/^\d+[.)]\s+(.+)$/);
                if (!match) break;
                items.push(<li key={index} className="pl-1"><InlineMarkdown>{match[1]}</InlineMarkdown></li>);
                index += 1;
                let nextIndex = index;
                while (nextIndex < lines.length && !lines[nextIndex].trim()) nextIndex += 1;
                if (/^\d+[.)]\s+/.test(lines[nextIndex]?.trim() || '')) {
                    index = nextIndex;
                }
            }
            blocks.push(<ol key={`ol-${index}`} className="list-decimal space-y-1.5 pl-5 marker:font-bold marker:text-blue">{items}</ol>);
            continue;
        }

        if (/^>\s?/.test(line)) {
            const quote = [];
            while (index < lines.length && /^>\s?/.test(lines[index].trim())) {
                quote.push(lines[index].trim().replace(/^>\s?/, ''));
                index += 1;
            }
            blocks.push(
                <blockquote key={`quote-${index}`} className="border-l border-blue/40 bg-blue-light/60 px-3 py-2 text-earth">
                    <InlineMarkdown>{quote.join(' ')}</InlineMarkdown>
                </blockquote>,
            );
            continue;
        }

        const paragraph = [line];
        index += 1;
        while (index < lines.length && lines[index].trim() && !isBlockStart(lines[index].trim())) {
            paragraph.push(lines[index].trim());
            index += 1;
        }
        blocks.push(<p key={`paragraph-${index}`}><InlineMarkdown>{paragraph.join(' ')}</InlineMarkdown></p>);
    }

    return <div className="space-y-3 break-words text-[0.9375rem] leading-7 text-charcoal">{blocks}</div>;
}
