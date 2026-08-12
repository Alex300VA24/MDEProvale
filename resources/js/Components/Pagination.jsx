export default function Pagination({ links = [], meta = {}, onPage, loading = false }) {
    if (!Array.isArray(links) || links.length === 0) return null;

    const pageFromUrl = (url) => {
        if (!url) return null;
        try {
            return new URL(url, window.location.origin).searchParams.get('page');
        } catch {
            return null;
        }
    };

    const renderLabel = (label) => {
        const map = {
            '&laquo; Previous': '‹',
            Previous: '‹',
            'Next &raquo;': '›',
            Next: '›',
        };
        return map[label] || label;
    };

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1 mt-4">
            {links.map((link, i) => {
                const page = pageFromUrl(link.url);
                const isDisabled = !link.url || loading;
                return (
                    <button
                        key={i}
                        type="button"
                        disabled={isDisabled}
                        onClick={() => page && onPage(Number(page))}
                        className={`min-w-9 h-9 px-2 rounded-lg text-sm font-semibold transition-all ${
                            link.active
                                ? 'bg-gradient-to-r from-sky to-blue text-white shadow'
                                : 'bg-cream text-earth hover:bg-mist disabled:opacity-40 disabled:cursor-not-allowed'
                        }`}
                    >
                        {renderLabel(link.label)}
                    </button>
                );
            })}
        </nav>
    );
}
