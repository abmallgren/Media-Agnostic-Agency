import './App.css';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Navigation from './components/Navigation';
import Expo from './components/expo/Expo';
import Intro from './components/intro/Intro'
import Profile from './components/profile/Profile';
import Intelligence from './components/intelligence/Intelligence';
import Journal from './components/journal/Journal';
import Company from './components/company/Company';

function Home() {
    return (
        <Company />
    )
}

function App() {
    return (
        <BrowserRouter>
            <Navigation />
            <main className="app-content">
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/expo" element={<Expo />} />
                    <Route path="/intro" element={<Intro />} />"
                    <Route path="/intelligence" element={<Intelligence />} />
                    <Route path="/journal" element={<Journal />} />
                    <Route path="/profile" element={<Profile />} />
                </Routes>
            </main>
            {/* Fixed branding in bottom left */}
            <div className="branding-fixed">
                Media Agnostic Agency
            </div>
        </BrowserRouter>
    );
}

export default App;