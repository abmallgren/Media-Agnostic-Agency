import './Company.css';

// No need to import the SVG as a module if it's served from /ghost.svg.
// Just reference it via src.

function Company() {
    return (
        <div className="company-page">
            <img
                src="/public/ghost.svg"
                alt="Company logo"
                className="company-logo"
            />
            <p>
                Media Agnostic Agency aspires to work with media most conducive to quality experiences,
                though prizes itself in primarily demonstrating the capability of in-network entities.
            </p>
            <p>
                Our goals are quality convergence and conscious singularity.
            </p>
            <p>
                It is our wish that people become aware, and can make use, of innovations in the most
                efficient way possible.
            </p>
        </div>
    );
}

export default Company;