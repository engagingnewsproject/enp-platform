import { describe, expect, test } from '@jest/globals';

import wpe_autosuggest from '../wpe-autosuggest';

// Get access to private variables with rewire
const wpeDataFilter = wpe_autosuggest.__get__('wpeDataFilter');

describe('wpeDataFilter function', () => {
  const searchTerm = 'fanta';
  const url = 'url.com';
  const dataGenerator = (link) => {
    // Return test data object
    return {
      propery_one: 1,
      property_two: 2,
      hits: {hits: [{_source: {ID: 1, permalink: link}}]}
    }
  };

  test('it returns the original object if no hits are present', () => {
    const dataInput = {
      propery_one: 1,
      property_two: 2
    };

    expect(wpeDataFilter(dataInput, searchTerm)).toEqual(dataInput);
  });

  test('it returns the original object if no permalink is present', () => {
    const data = {
      propery_one: 1,
      property_two: 2,
      hits: {hits: [{_source: {ID: 1}}]}
    };
        
    expect(wpeDataFilter(data, searchTerm)).toEqual(data);
  });

  test('it adds the searchTerm query string to the permalink', () => {
    const dataInput = dataGenerator(url);
    const dataOutput = dataGenerator(`${url}?autosuggest-term=${searchTerm}`);
        
    expect(wpeDataFilter(dataInput, searchTerm)).toEqual(dataOutput);
  });

  test('it handles a url that already contains a query string', () => {
    const urlWithQuery = `${url}?s=sunkist`;
    const dataInput = dataGenerator(urlWithQuery);
    const dataOutput = dataGenerator(`${urlWithQuery}&autosuggest-term=${searchTerm}`);

    expect(wpeDataFilter(dataInput, searchTerm)).toEqual(dataOutput);
  });
});