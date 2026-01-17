// Listen for messages from the web page
window.addEventListener('message', (event) => {
    if (event.source !== window) return;
    if (event.data && event.data.type === 'Media_Agnostic_Introduction') {
        // Relay to extension background or popup
        chrome.runtime.sendMessage({ type: 'DO_SOMETHING', payload: event.data.payload });
    }
});

window.addEventListener('message', (event) => {
    if (event.source !== window) return;
    if (event.data && event.data.type === 'Media_Agnostic_Profile') {
        chome.storage.sync.set({ maaProfileData: event.data.payload }, () => { })
    }
});

// Optionally, listen for messages from the extension and forward to the page
chrome.runtime.onMessage.addListener((msg) => {
    window.postMessage({ type: 'FROM_EXTENSION', payload: msg }, '*');
});