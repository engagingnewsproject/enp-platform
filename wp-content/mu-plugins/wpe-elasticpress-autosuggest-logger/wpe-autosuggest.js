/**
 * Defines a custom data filter to be used by the window.epDataFilter
 * method in elasticpress plugin autosuggest.js module.
 *
 * @since 1.0.0
 * 
 * @param {object} data - AJAX request object
 * @param {string} searchTerm - user search term
 * @returns {object} filtered AJAX request object
 */ 
const wpeDataFilter = (data, searchTerm) => {
  // Get all hits that come back from the search
  const { hits } = data;
  if (hits) {
    // For each hit add the searchTerm to the permalink
    hits.hits.forEach(hit => {
      let url = hit._source.permalink;
      if (url) {
        // Check if url already contains a query string
        let separator = `${url.includes('?') ? '&' : '?'}`;
        hit._source.permalink = `${url}${separator}autosuggest-term=${searchTerm}`;
      }
    });
  }
  return data;
};

window.epDataFilter = wpeDataFilter;
