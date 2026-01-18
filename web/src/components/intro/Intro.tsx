import React from "react";
import { useEffect } from "react";
import type { FormEvent } from 'react';
import toast from 'react-hot-toast';

import './Intro.css'
function Intro() {
    const [gender, setGender] = React.useState('');
    const [age, setAge] = React.useState('');
    const [bio, setBio] = React.useState('');
    const [interests, setInterests] = React.useState('');

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();

        const profile = {
            gender: gender,
            age: age,
            bio: bio,
            interests: interests
        };

        window.postMessage({ type: 'Media_Agnostic_Profile', payload: profile }, '*');
        toast.success("Intro profile saved");
    }

    useEffect(() => {
        window.addEventListener('message', event => {
            if (event.source !== window) return;
            if (event.data.type === 'Media_Agnostic_Introduction_Profile') {
                const profile = event.data.payload;
                setGender(profile.gender);
                setAge(profile.age);
                setBio(profile.bio);
                setInterests(profile.interests);
            }
        });
    }, [])

    return (
        <section className="intro">
            <p>
                Intro is a proof of concept that enables a website user to register a browsing profile to "introduce" themselves to 
                small and medium-sized businesses (SMBs) which are participating in the program. When a participating website user
                visits a participating SMB's website, their profile is shared with the SMB to help them better understand their audience.
            </p>
            <p>
                If a user would like to get started, download the <a href="/assets/IntroExtension.zip">Extension</a> (currently under review by Google
                for submission to the Chrome Store). Next, fill out the form below
                to create your profile. When you visit a participating SMB's website, your profile will be shared with them.
            </p>
            <form onSubmit={handleSubmit}>
                <table>
                    <tbody>
                        <tr>
                            <td>Gender:</td><td><input type="text" value={gender} onChange={ (e) => setGender(e.target.value) }></input></td>
                        </tr>
                        <tr>
                            <td>Age:</td><td><input type="text" value={age} onChange={(e) => setAge(e.target.value)}></input></td>
                        </tr>
                        <tr>
                            <td>Bio</td>
                        </tr>
                        <tr>
                            <td colSpan={2}><textarea value={bio} onChange={(e) => setBio(e.target.value)}></textarea></td>
                        </tr>
                        <tr>
                            <td>Interests</td>
                        </tr>
                        <tr>
                            <td colSpan={2}><textarea value={interests} onChange={(e) => setInterests(e.target.value)}></textarea></td>
                        </tr>
                        <tr>
                            <td className="button-td" colSpan={2}><button type="submit">Sync</button></td>
                        </tr>
                     </tbody>
                </table>
            </form>
            <p>
                If an SMB would like to participant in the program, you may download 
                the <a href="/assets/MediaAgnosticIntro.zip">WordPress plugin</a> (currently in queue to be reviewed by WordPress),
                or download the <a href="/assets/media-agnostic-intro.js">JavaScript</a> (right click, "Save link as...") and add it to your website.
            </p>
        </section>
    );
};

export default Intro;