import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import http from './http';

const RuntimeStateContext = createContext(null);

export function RuntimeStateProvider({ initialModules = [], initialUploadMaxMb = 10, initialUser = null, children }) {
    const [modules, setModules] = useState(initialModules);
    const [uploadMaxMb, setUploadMaxMb] = useState(Number(initialUploadMaxMb) || 10);
    const [currentUser, setCurrentUser] = useState(initialUser);

    const refreshModules = useCallback(async () => {
        const response = await http.get('/api/dashboard/sistema/current-modules');
        const nextModules = response.data?.data ?? [];
        setModules(nextModules);
        return nextModules;
    }, []);

    const value = useMemo(() => ({
        modules,
        setModules,
        refreshModules,
        uploadMaxMb,
        setUploadMaxMb,
        currentUser,
        setCurrentUser,
    }), [modules, refreshModules, uploadMaxMb, currentUser]);

    return (
        <RuntimeStateContext.Provider value={value}>
            {children}
        </RuntimeStateContext.Provider>
    );
}

export function useRuntimeState() {
    const context = useContext(RuntimeStateContext);
    if (!context) throw new Error('useRuntimeState debe usarse dentro de RuntimeStateProvider.');
    return context;
}
