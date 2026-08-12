export default function SectionPlaceholder({ title, description }) {
    return (
        <div className="animate-fade-in">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-navy font-extrabold text-xl sm:text-2xl">{title}</h1>
            </div>
            <div className="bg-white rounded-2xl border-2 border-mist p-10 text-center">
                <div className="empty-state">
                    <i className="fas fa-cubes text-4xl"></i>
                    <p className="text-sm font-semibold">{description || 'Sección en construcción.'}</p>
                </div>
            </div>
        </div>
    );
}
