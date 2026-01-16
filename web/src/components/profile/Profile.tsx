import { useEffect, useState } from 'react';
import './Profile.css';
import type { Project } from '../../types/Project';
import ProjectForm from '../shared/project/ProjectForm';

interface CurrentUser {
    name: string | null;
    email: string | null;
}

interface Message {
    id: number;
    body: string;
    sentAt: string;       // ISO string from backend
    senderName: string;
    recipientName: string;
    projectName?: string | null;
}

function Profile() {
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [loadingUser, setLoadingUser] = useState(true);

    const [projects, setProjects] = useState<Project[]>([]);
    const [loadingProjects, setLoadingProjects] = useState(false);

    const [messages, setMessages] = useState<Message[]>([]);
    const [loadingMessages, setLoadingMessages] = useState(false);

    const [showForm, setShowForm] = useState(false);
    const [editingProject, setEditingProject] = useState<Project | null>(null);

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

    // Load messages for current user
    useEffect(() => {
        const loadMessages = async () => {
            if (!user) return;
            setLoadingMessages(true);
            try {
                const resp = await fetch('/api/contact', {
                    credentials: 'include',
                });
                if (!resp.ok) return;
                const contentType = resp.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) return;
                const data: Message[] = await resp.json();
                setMessages(data ?? []);
            } catch {
                // ignore
            } finally {
                setLoadingMessages(false);
            }
        };

        if (user) {
            loadMessages();
        }
    }, [user]);

    // Load projects for current user
    useEffect(() => {
        const loadProjects = async () => {
            if (!user) return;
            setLoadingProjects(true);
            try {
                const resp = await fetch('/api/my/projects', {
                    credentials: 'include',
                });
                if (!resp.ok) return;
                const contentType = resp.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) return;
                const data: Project[] = await resp.json();
                setProjects(data ?? []);
            } catch {
                // ignore
            } finally {
                setLoadingProjects(false);
            }
        };

        if (user) {
            loadProjects();
        }
    }, [user]);

    const handleEdit = (p: Project) => {
        setEditingProject(p);
        setShowForm(true);
    };

    const handleDelete = async (projectId: number) => {
        if (!window.confirm('Are you sure you want to delete this project?')) return;
        try {
            const resp = await fetch(`/api/projects/${projectId}`, {
                method: 'DELETE',
                credentials: 'include',
            });
            if (!resp.ok) return;
            setProjects(prev => prev.filter(p => p.id !== projectId));
        } catch {
            // ignore
        }
    };

    const handleSaved = (project: Project) => {
        setShowForm(false);
        setEditingProject(null);
        setProjects(prev => prev.map(p => (p.id === project.id ? project : p)));
    };

    if (loadingUser) {
        return <div className="profile-page">Loading profile…</div>;
    }

    if (!user) {
        return (
            <div className="profile-page">
                <p>Please sign in to view your profile.</p>
            </div>
        );
    }

    return (
        <div className="profile-page">

            {/* Messages section */}
            <section className="profile-section">
                <h2>Messages</h2>
                {loadingMessages && <p>Loading messages…</p>}
                {!loadingMessages && messages.length === 0 && (
                    <p>You don&apos;t have any messages yet.</p>
                )}
                <div className="profile-messages">
                    {messages.map(m => (
                        <div key={m.id} className="message-card">
                            <div className="message-header">
                                <span className="message-from">
                                    From: {m.senderName}
                                </span>
                                {m.projectName && (
                                    <span className="message-project">
                                        Project: {m.projectName}
                                    </span>
                                )}
                                <span className="message-date">
                                    {new Date(m.sentAt).toLocaleString()}
                                </span>
                            </div>
                            <p className="message-body">{m.body}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Projects section */}
            <section className="profile-section">
                {showForm && (
                    <ProjectForm
                        project={editingProject}
                        onSaved={handleSaved}
                        onCancel={() => {
                            setShowForm(false);
                            setEditingProject(null);
                        }}
                    />
                )}

                <h2>Your Projects</h2>
                {loadingProjects && <p>Loading your projects…</p>}
                {!loadingProjects && projects.length === 0 && (
                    <p>You have not added any projects yet.</p>
                )}

                <div className="profile-projects">
                    {projects.map(p => (
                        <div key={p.id} className="project-card">
                            <h3>{p.name}</h3>
                            <p>{p.description}</p>
                            <p>
                                <strong>Involvement sought:</strong> {p.involvementSought}
                            </p>
                            <div className="project-actions">
                                <button
                                    type="button"
                                    className="btn-auth btn-edit"
                                    onClick={() => handleEdit(p)}
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    className="btn-auth btn-delete"
                                    onClick={() => handleDelete(p.id)}
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </div>
    );
}

export default Profile;