import { useEffect, useState } from 'react';
import './Expo.css';
import ProjectForm from '../shared/project/ProjectForm';
import ContactForm from './ContactForm';
import type { Project } from '../../types/Project';

interface ProjectsResponse {
    projects: Project[] | null | undefined;
    totalCount: number;
}

interface CurrentUser {
    name: string | null;
    email: string | null;
}

function Expo() {
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [loadingUser, setLoadingUser] = useState(true);

    const [projects, setProjects] = useState<Project[]>([]);
    const [loadingProjects, setLoadingProjects] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [showForm, setShowForm] = useState(false);
    const [editingProject, setEditingProject] = useState<Project | null>(null);
    const [showContactFormFor, setShowContactFormFor] = useState<number | null>(null);

    // Load current user (logged-in check)
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

    // Load projects once
    useEffect(() => {
        const loadProjects = async () => {
            setLoadingProjects(true);
            setError(null);
            try {
                const resp = await fetch('/api/projects?skip=0&take=50', {
                    credentials: 'include',
                });

                if (!resp.ok) {
                    setError('Failed to load projects.');
                    return;
                }

                const contentType = resp.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    setError('Unexpected response from server.');
                    return;
                }

                const data: Project[] = await resp.json();
                setProjects(data ?? []); // use array directly
            } catch {
                setError('Failed to load projects.');
            } finally {
                setLoadingProjects(false);
            }
        };

        loadProjects();
    }, []);

    const isLoggedIn = !!user;

    const handleAddProjectClick = () => {
        setEditingProject(null);
        setShowForm(true);
    };

    const handleProjectSaved = (project: Project, isNew: boolean) => {
        setShowForm(false);
        setEditingProject(null);
        setProjects(prev =>
            isNew ? [project, ...prev] : prev.map(p => (p.id === project.id ? project : p)),
        );
    };

    const handleEditProject = (project: Project) => {
        setEditingProject(project);
        setShowForm(true);
    };

    const handleDeleteProject = async (projectId: number) => {
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

    const handleUpvote = async (projectId: number) => {
        try {
            const resp = await fetch(`/api/projects/${projectId}/upvote`, {
                method: 'POST',
                credentials: 'include',
            });
            if (!resp.ok) return;
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) return;
            const updated: Project = await resp.json();
            setProjects(prev => prev.map(p => (p.id === updated.id ? updated : p)));
        } catch {
            // ignore
        }
    };

    const openContact = (projectId: number) => {
        setShowContactFormFor(projectId);
    };

    const closeContact = () => {
        setShowContactFormFor(null);
    };

    return (
        <div className="expo-page">
            <div className="expo-header">
                {/* No "Expo" text; just Add Project where the heading was */}
                {isLoggedIn && !loadingUser && !showForm && (
                    <button
                        type="button"
                        className="btn-auth btn-add-project"
                        onClick={handleAddProjectClick}
                    >
                        Add Project
                    </button>
                )}
            </div>

            {!isLoggedIn && !loadingUser && (
                <div className="expo-login-message">
                    Please log in to interact with project managers.
                </div>
            )}

            {error && <div className="expo-error">{error}</div>}

            {showForm && isLoggedIn && (
                <ProjectForm
                    project={editingProject}
                    onSaved={handleProjectSaved}
                    onCancel={() => {
                        setShowForm(false);
                        setEditingProject(null);
                    }}
                />
            )}

            {/* No projects message */}
            {!loadingProjects && projects.length === 0 && !showForm && (
                <div className="expo-no-projects">
                    No projects have been added yet.
                </div>
            )}

            <div className="projects-list">
                {projects.map(project => (
                    <div key={project.id} className="project-card">
                        <h2>{project.name}</h2>
                        <p className="project-description">{project.description}</p>
                        <h3>Involvement sought</h3>
                        <p>
                            {project.involvementSought}
                        </p>
                        <div className="project-meta">
                            <span className="project-upvotes">
                                Upvotes (last 7 days): {project.upvotesLast7Days}
                            </span>

                            {isLoggedIn && project.canUpvote && (
                                <button
                                    type="button"
                                    className="btn-auth btn-upvote"
                                    onClick={() => handleUpvote(project.id)}
                                >
                                    Upvote
                                </button>
                            )}

                            {isLoggedIn && project.canEdit && (
                                <>
                                    <button
                                        type="button"
                                        className="btn-auth btn-edit"
                                        onClick={() => handleEditProject(project)}
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        className="btn-auth btn-delete"
                                        onClick={() => handleDeleteProject(project.id)}
                                    >
                                        Delete
                                    </button>
                                </>
                            )}

                            {isLoggedIn && !project.canEdit && (
                                <>
                                    <button
                                        type="button"
                                        className="btn-auth btn-contact"
                                        onClick={() => openContact(project.id)}
                                    >
                                        Contact
                                    </button>
                                </>
                            )}
                        </div>
                        {showContactFormFor === project.id && (
                            <div>
                                <ContactForm
                                    projectId={project.id}
                                    onSent={closeContact}
                                    onCancel={closeContact}
                                />
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

export default Expo;