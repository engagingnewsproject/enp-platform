class CachePluginWindowModifier {
    constructor(window) {
        this.window = window;
    }

    stripQueryParamFromPathname(queryParam) {
        const urlParams = this.#removeQueryParam(queryParam);

        return `${this.window.location.pathname}?${urlParams}`;
    }

    #removeQueryParam(queryParam) {
        const newUrl = new URL(this.window.location.href);
        let params = new URLSearchParams(newUrl.search);
        params.delete(queryParam);

        return params;
    }

    replaceWindowState(url) {
        this.window.history.replaceState(null, '', url);
    }
}

export default CachePluginWindowModifier;
