export default function LoadingScreen({ subtitle = 'Cargando panel...' }) {
    return (
        <div id="loading-screen" className="active">
            <div className="loader-container">
                <div className="loader-icon">
                    <div className="loader-spin"></div>
                    <div className="loader-ring"></div>
                    <img src="/img/muni2.png" alt="PROVALE" />
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