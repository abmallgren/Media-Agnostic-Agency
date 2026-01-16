import { useState } from 'react';
import type { FormEvent } from 'react';
import './ContactForm.css'

interface ContactFormProps {
    projectId: number;
    onSent: () => void;
    onCancel: () => void;
}

function ContactForm({ projectId, onSent, onCancel }: ContactFormProps) {
    const [body, setBody] = useState('');
    const [sending, setSending] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!body.trim()) return;

        setSending(true);
        setError(null);

        try {
            const resp = await fetch('/api/contact', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    projectId,
                    body: body.trim(),
                }),
            });

            if (!resp.ok) {
                setError('Failed to send message.');
                return;
            }

            // SUCCESS: notify parent so it can hide the form
            onSent();
            // Optionally clear local state
            setBody('');
        } catch {
            setError('Failed to send message.');
        } finally {
            setSending(false);
        }
    };

    return (
            <form className="contact-form" onSubmit={handleSubmit}>
                <textarea
                    value={body}
                    onChange={e => setBody(e.target.value)}
                    maxLength={1000}
                    placeholder="Write your message..."
                />
                {error && <div className="contact-error">{error}</div>}
                <div className="contact-actions">
                    <button type="submit" className="btn-auth btn-send" disabled={sending}>
                        {sending ? 'Sending…' : 'Send message'}
                    </button>
                    <button
                        type="button"
                        className="btn-auth btn-cancel"
                        onClick={onCancel}
                        disabled={sending}
                    >
                        Cancel
                    </button>
                </div>
            </form>
     );
}

export default ContactForm;