const loadingLogoUrl = document
    .querySelector('meta[name="provale-loading-logo"]')
    ?.getAttribute('content') || '/img/muni2.png';

export default function LoadingScreen({ subtitle = 'Cargando panel...' }) {
    return (
        <div id="loading-screen" className="active">
            <div className="loader-container">
                <div className="loader-icon">
                    <div className="loader-spin"></div>
                    <div className="loader-ring"></div>
                    <img
                        src={loadingLogoUrl}
                        alt="PROVALE"
                        decoding="sync"
                        fetchPriority="high"
                    />
                </div>
                <div className="loader-text">
                    <div className="loader-title">PROVALE</div>
                    <div className="loader-subtitle">{subtitle}</div>
                </div>
                <div className="loader-progress">
                    <div className="loader-progress-bar"></div>
                </div>
            </div>
        </div>
    );
}
