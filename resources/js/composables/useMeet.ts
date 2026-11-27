export function ensureJitsiScript(): Promise<void> {
    return new Promise((resolve, reject) => {
        if ((window as any).JitsiMeetExternalAPI) {
            resolve();

            return;
        }

        const s = document.createElement('script');
        s.src = 'https://meet.jit.si/external_api.js';
        s.async = true;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Could not load Jitsi Meet.'));
        document.head.appendChild(s);
    });
}

export function mountJitsi(
    parent: HTMLElement,
    room: string,
    displayName: string,
): any {
    return new (window as any).JitsiMeetExternalAPI('meet.jit.si', {
        roomName: room,
        width: '100%',
        height: '100%',
        parentNode: parent,
        userInfo: { displayName },
    });
}
