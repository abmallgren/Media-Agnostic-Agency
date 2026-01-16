import { useEffect, useState } from 'react';
import './Intelligence.css';

interface IntelligenceData {
    activeProjects: number;
    recentVotes: number;
}

function Intelligence() {
    const [data, setData] = useState<IntelligenceData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const load = async () => {
            setLoading(true);
            setError(null);
            try {
                const resp = await fetch('/api/intelligence', {
                    credentials: 'include',
                });
                if (!resp.ok) {
                    setError('Failed to load intelligence data.');
                    return;
                }
                const contentType = resp.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    setError('Unexpected response from server.');
                    return;
                }
                const json: IntelligenceData = await resp.json();
                setData(json);
            } catch {
                setError('Failed to load intelligence data.');
            } finally {
                setLoading(false);
            }
        };

        load();
    }, []);

    return (
        <div className="intelligence-page">
            <h1>Intelligence</h1>

            {loading && <p>Loading…</p>}
            {error && <p className="intelligence-error">{error}</p>}

            {!loading && !error && data && (
                <div className="intelligence-metrics">
                    <div className="metric-card">
                        <h2>Active Projects</h2>
                        <p className="metric-value">{data.activeProjects}</p>
                    </div>
                    <div className="metric-card">
                        <h2>Recent Votes</h2>
                        <p className="metric-value">{data.recentVotes}</p>
                    </div>
                </div>
            )}
        </div>
    );
}

export default Intelligence;