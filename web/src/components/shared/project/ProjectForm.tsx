import { useState } from 'react';
import type { FormEvent } from 'react';
import type { Project } from '../../../types/Project';
import './ProjectForm.css';

interface ProjectFormProps {
    project: Project | null;
    onSaved: (project: Project, isNew: boolean) => void;
    onCancel: () => void;
}

function ProjectForm({ project, onSaved, onCancel }: ProjectFormProps) {
    const [name, setName] = useState(project?.name ?? '');
    const [description, setDescription] = useState(project?.description ?? '');
    const [involvementSought, setInvolvementSought] = useState(
        project?.involvementSought ?? '',
    );
    const [saving, setSaving] = useState(false);
    const isEdit = !!project;

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!name.trim()) return;

        setSaving(true);
        try {
            const body = {
                name: name.trim(),
                description: description.trim(),
                involvementSought: involvementSought.trim(),
            };

            const resp = await fetch(
                isEdit ? `/api/projects/${project!.id}` : '/api/projects',
                {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify(body),
                },
            );

            if (!resp.ok) return;
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) return;

            const saved: Project = await resp.json();
            onSaved(saved, !isEdit);
        } catch {
            // ignore
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="project-form-container">
            <form className="project-form" onSubmit={handleSubmit}>
                <h2>{isEdit ? 'Edit Project' : 'Add Project'}</h2>

                <label>
                    Project Name
                    <input
                        type="text"
                        maxLength={256}
                        value={name}
                        onChange={e => setName(e.target.value)}
                        required
                    />
                </label>

                <label>
                    Project Description
                    <textarea
                        maxLength={1000}
                        value={description}
                        onChange={e => setDescription(e.target.value)}
                        required
                    />
                </label>

                <label>
                    Involvement Sought
                    <textarea
                        maxLength={1000}
                        value={involvementSought}
                        onChange={e => setInvolvementSought(e.target.value)}
                        required
                    />
                </label>

                <div className="project-form-actions">
                    <button
                        type="submit"
                        className="btn-auth btn-save"
                        disabled={saving}
                    >
                        {saving ? 'Saving…' : isEdit ? 'Save Changes' : 'Add Project'}
                    </button>
                    <button
                        type="button"
                        className="btn-auth btn-cancel"
                        onClick={onCancel}
                        disabled={saving}
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}

export default ProjectForm;