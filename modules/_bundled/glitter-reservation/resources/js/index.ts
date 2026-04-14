const moduleIdentifier = 'glitter-reservation';

if (typeof window !== 'undefined') {
    window.__GlitterReservation = window.__GlitterReservation ?? {
        identifier: moduleIdentifier,
    };
}

export {};
