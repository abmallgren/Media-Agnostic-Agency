import React from "react";
import type { FormEvent } from 'react';

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
    }
    return (
        <section className="intro">
            <p>
                Intro is a proof of concept that enables a client to register and browsing profile to "introduce" themselves to 
                small and medium-sized businesses (SMBs) which are participating in the program. When a participating user
                visits a participating SMB's website, they will be prompted to share their profile information with the SMB.
                This allows SMBs to get to know potential customers better and tailor their offerings accordingly.
            </p>
            <p>
                If a user would like to get started, download the Extension from the Chrome Web Store. Next, fill out the form
                below to create your profile.
            </p>
            <form onSubmit={handleSubmit}>
                <table>
                    <tr>
                        <td>Gender:</td><td><input type="text" onChange={ (e) => setGender(e.target.value) }></input></td>
                    </tr>
                    <tr>
                        <td>Age:</td><td><input type="text" onChange={(e) => setAge(e.target.value)}></input></td>
                    </tr>
                    <tr>
                        <td>Bio</td>
                    </tr>
                    <tr>
                        <td colSpan={2}><textarea onChange={(e) => setBio(e.target.value)}></textarea></td>
                    </tr>
                    <tr>
                        <td>Interests</td>
                    </tr>
                    <tr>
                        <td colSpan={2}><textarea onChange={(e) => setInterests(e.target.value)}></textarea></td>
                    </tr>
                    <tr>
                        <td className="button-td" colSpan={2}><button type="submit">Sync</button></td>
                    </tr>
                </table>
            </form>
        </section>
    );
};

export default Intro;