 window.addEventListener('message', event => {
    console.log('Listener');
     if (event.source !== window) return;
     if (event.data.type === 'Media_Agnostic_Introduction_Profile') {
        fetch('/wp-admin/admin-ajax.php?action=add_ma_intro', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                    url: window.location.href,
                    profile: JSON.stringify(event.data.payload)
                })
        })
     }
 });

window.postMessage({ type: 'Media_Agnostic_Introduction', payload: result.maProfile }, '*');