import { Link } from 'react-router-dom';
import { useEffect, useState, useCallback } from 'react';
import './Navigation.css';

interface CurrentUser {
    name: string | null;
    email: string | null;
}

function Navigation() {
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [loading, setLoading] = useState(true);

    const loadUser = useCallback(async () => {
        setLoading(true);
        try {
            const resp = await fetch('/api/auth/user', {
                credentials: 'include',
            });

            if (!resp.ok) {
                setUser(null);
                return;
            }

            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                setUser(null);
                return;
            }

            const data = await resp.json();
            setUser({ name: data.name, email: data.email });
        } catch {
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadUser();
    }, [loadUser]);

    const handleSignIn = () => {
        window.location.href = '/api/auth/login/google';
    };

    const handleSignOut = async () => {
        try {
            await fetch('/api/auth/logout', {
                method: 'GET',
                credentials: 'include',
            });
        } catch {}
        window.location.href = '/';
    };

    const isLoggedIn = !!user;

    return (
        <nav className="main-nav">
            <div className="nav-content">
                <div className="nav-links">
                    <Link to="/">Company</Link>
                    <Link to="/expo">Expo</Link>
                    <Link to="/intro">Intro</Link>
                    <Link to="/intelligence">Intelligence</Link>
                    <Link to="/journal">Journal</Link>
                </div>

                <div className="auth-area">
                    {!loading && isLoggedIn && (
                        <div className="auth-buttons">
                            <Link to="/profile" className="user-name">
                                {user?.name ?? 'Profile'}
                            </Link>
                            <button
                                type="button"
                                onClick={handleSignOut}
                                className="btn-auth btn-signout"
                            >
                                Sign out
                            </button>
                        </div>
                    )}

                    {!loading && !isLoggedIn && (
                        <div className="auth-buttons">
                            <button
                                type="button"
                                onClick={handleSignIn}
                                className="btn-auth btn-google"
                            >
                                Sign in with Google
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </nav>
    );
}

export default Navigation;