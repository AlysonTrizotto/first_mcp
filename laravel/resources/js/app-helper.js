// Helper para URLs dinâmicas
window.AppHelper = {
    // Obter a URL base da aplicação
    getBaseUrl: function() {
        return window.location.origin;
    },
    
    // Construir URL completa
    url: function(path) {
        const baseUrl = this.getBaseUrl();
        const cleanPath = path.startsWith('/') ? path : '/' + path;
        return baseUrl + cleanPath;
    },
    
    // Fazer requisições AJAX com URL correta
    request: function(method, path, data, options = {}) {
        const url = this.url(path);
        const defaultOptions = {
            method: method.toUpperCase(),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken
            }
        };
        
        if (data && method.toUpperCase() !== 'GET') {
            defaultOptions.body = JSON.stringify(data);
        }
        
        return fetch(url, Object.assign(defaultOptions, options));
    }
};

// Compatibilidade com o código existente
if (typeof window.axios !== 'undefined') {
    window.axios.defaults.baseURL = window.AppHelper.getBaseUrl();
}
