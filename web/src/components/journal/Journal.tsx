import { useEffect, useState, useRef, useCallback } from 'react';
import './Journal.css';

interface JournalPost {
    Id: number;
    Title: string;
    Body: string;
    CreatedAt: string; // ISO
    AuthorName: string;
}

interface JournalResponse {
    posts: JournalPost[] | null | undefined;
    totalCount: number;
}

interface CurrentUser {
    name: string | null;
    email: string | null;
}

function Journal() {
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [loadingUser, setLoadingUser] = useState(true);

    const [posts, setPosts] = useState<JournalPost[]>([]);
    const [totalCount, setTotalCount] = useState(0);
    const [skip, setSkip] = useState(0);
    const take = 10;

    const [loadingPosts, setLoadingPosts] = useState(false);
    const [hasMore, setHasMore] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [title, setTitle] = useState('');
    const [body, setBody] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const loaderRef = useRef<HTMLDivElement | null>(null);

    // Refs to avoid over-depending in useCallback
    const loadingRef = useRef(false);
    const totalCountRef = useRef(0);

    useEffect(() => {
        loadingRef.current = loadingPosts;
    }, [loadingPosts]);

    useEffect(() => {
        totalCountRef.current = totalCount;
    }, [totalCount]);

    // Load current user
    useEffect(() => {
        const loadUser = async () => {
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
                setLoadingUser(false);
            }
        };
        loadUser();
    }, []);

    const loadPosts = useCallback(
        async (initial: boolean) => {
            // Guard against concurrent loads
            if (loadingRef.current) return;

            const currentSkip = initial ? 0 : skip;
            const currentTotal = totalCountRef.current;

            // If we've already loaded everything, don't call the API again
            if (!initial && currentTotal > 0 && currentSkip >= currentTotal) {
                setHasMore(false);
                return;
            }

            loadingRef.current = true;
            setLoadingPosts(true);
            setError(null);

            try {
                const resp = await fetch(
                    `/api/journal?skip=${currentSkip}&take=${take}`,
                    { credentials: 'include' },
                );
                if (!resp.ok) {
                    setError('Failed to load journal posts.');
                    return;
                }
                const contentType = resp.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    setError('Unexpected response from server.');
                    return;
                }

                const data: JournalResponse = await resp.json();
                const newPosts = data.posts ?? [];
                const newTotal = data.totalCount;

                if (initial) {
                    setPosts(newPosts);
                    setTotalCount(newTotal);

                    const newSkip = newPosts.length;
                    setSkip(newSkip);
                    setHasMore(newSkip < newTotal);
                } else {
                    setPosts(prev => [...prev, ...newPosts]);
                    setTotalCount(newTotal);

                    const newSkip = currentSkip + newPosts.length;
                    setSkip(newSkip);
                    setHasMore(newSkip < newTotal);
                }
            } catch {
                setError('Failed to load journal posts.');
            } finally {
                loadingRef.current = false;
                setLoadingPosts(false);
            }
        },
        [skip, take],
    );

    // Initial load once
    useEffect(() => {
        loadPosts(true);
    }, [loadPosts]);

    // Infinite scroll sentinel
    useEffect(() => {
        if (!hasMore) return;

        const node = loaderRef.current;
        if (!node) return;

        const observer = new IntersectionObserver(entries => {
            const entry = entries[0];
            if (entry.isIntersecting && !loadingRef.current) {
                // Load next page
                loadPosts(false);
            }
        });

        observer.observe(node);

        return () => {
            observer.disconnect();
        };
    }, [hasMore, loadPosts]);

    const isLoggedIn = !!user;

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!title.trim() || !body.trim()) return;

        setSubmitting(true);
        setError(null);

        try {
            const resp = await fetch('/api/journal', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: title.trim(),
                    body: body.trim(),
                }),
            });
            if (!resp.ok) {
                setError('Failed to submit journal post.');
                return;
            }
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                setError('Unexpected response from server.');
                return;
            }
            const created: JournalPost = await resp.json();

            // Prepend new post
            setPosts(prev => [created, ...prev]);
            setTotalCount(prev => prev + 1);
            setSkip(prev => prev + 1);

            setTitle('');
            setBody('');
        } catch {
            setError('Failed to submit journal post.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="journal-page">
            <h1>Journal</h1>

            {error && <div className="journal-error">{error}</div>}

            {isLoggedIn && !loadingUser && (
                <form className="journal-form" onSubmit={handleSubmit}>
                    <label>
                        Title
                        <input
                            type="text"
                            maxLength={256}
                            value={title}
                            onChange={e => setTitle(e.target.value)}
                            required
                        />
                    </label>
                    <label>
                        Post
                        <textarea
                            maxLength={4000}
                            value={body}
                            onChange={e => setBody(e.target.value)}
                            required
                        />
                    </label>
                    <button
                        type="submit"
                        className="btn-auth btn-save"
                        disabled={submitting}
                    >
                        {submitting ? 'Submitting…' : 'Submit Post'}
                    </button>
                </form>
            )}

            {!isLoggedIn && !loadingUser && (
                <div className="journal-login-message">
                    Please log in to create journal posts.
                </div>
            )}

            {!loadingPosts && posts.length === 0 && (
                <div className="journal-no-posts">
                    No journal posts yet.
                </div>
            )}

            <div className="journal-list">
                {posts.map(post => (
                    <div key={post.Id} className="journal-card">
                        <h2>{post.Title}</h2>
                        <p className="journal-meta">
                            {new Date(post.CreatedAt).toLocaleString()} by{' '}
                            {post.AuthorName || 'Unknown'}
                        </p>
                        <p className="journal-body">{post.Body}</p>
                    </div>
                ))}
            </div>

            {hasMore && (
                <div ref={loaderRef} className="journal-loader">
                    {loadingPosts ? 'Loading…' : 'Scroll to load more…'}
                </div>
            )}
        </div>
    );
}

export default Journal;