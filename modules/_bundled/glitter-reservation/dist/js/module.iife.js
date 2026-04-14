(function (global) {
    var current = global.__GlitterReservation || {};
    global.__GlitterReservation = Object.assign({ identifier: 'glitter-reservation' }, current);
})(typeof window !== 'undefined' ? window : globalThis);
