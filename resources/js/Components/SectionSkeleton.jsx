export default function SectionSkeleton() {
    return (
        <div className="animate-pulse space-y-6">
            <div className="h-7 w-1/3 rounded-lg bg-mist" />
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {[0, 1, 2, 3].map((i) => (
                    <div key={i} className="h-24 rounded-2xl bg-mist" />
                ))}
            </div>
            <div className="h-8 w-2/3 rounded-lg bg-mist" />
            <div className="h-72 rounded-2xl bg-mist" />
        </div>
    );
}
