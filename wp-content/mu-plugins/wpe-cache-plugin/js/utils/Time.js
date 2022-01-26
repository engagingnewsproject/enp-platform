'use strict';
class Time {
    static hours(h) {
        return h * 60 * 60 * 1000;
    }
    static minutes(m) {
        return m * 60 * 1000;
    }
    static days(d) {
        return d * 24 * 60 * 60 * 1000;
    }
}

export default Time;
