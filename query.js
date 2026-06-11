$(document).ready(function() {
  var pageSize = 30;
  var assumedMaxAge = 110;
  var currentPage = 0;
  var $names = $('ul#names > li.name');
  var occupationFilters = {};

  tinysort.defaults.order = 'desc';
  setupNames();
  setupControls();
  showPage(0);

  function setupNames() {
    $names.each(function(index) {
      var $name = $(this);
      $name.data('original-index', index);
      $name.data('search-term', $.trim($name.children('.searchTerm').text()));
      $name.data('matches', []);
      $name.data('search-offset', 0);
      $name.data('has-more', true);
      $name.data('loaded', false);
      $name.data('loading', false);
    });
  }

  function setupControls() {
    setupPager();
    setupOccupationFilters();
    updateFeatureCounts();
    $('#sportsPersonCheck,#orcidCheck,#peerageCheck').on('click', applyFilters);
    $('.sliderValue').on('change', applyFilters);
    $('#slider-range').on('slidestop', applyFilters);
  }

  function setupPager() {
    if ($names.length <= pageSize) {
      return;
    }

    $('#names').before(pagerHtml('top'));
    $('#names').after(pagerHtml('bottom'));

    $('.prevPage').on('click', function() {
      showPage(currentPage - 1);
    });
    $('.nextPage').on('click', function() {
      showPage(currentPage + 1);
    });
  }

  function pagerHtml(position) {
    return '<div class="pager pager-' + position + '" aria-label="Result pages">' +
        '<button type="button" class="pageButton prevPage">Previous</button>' +
        '<span class="pageStatus"></span>' +
        '<button type="button" class="pageButton nextPage">Next</button>' +
      '</div>';
  }

  function setupOccupationFilters() {
    $('.analyse').append(
      '<div id="occupationFilters" class="facetBlock">' +
        '<p>Occupations</p>' +
        '<div class="facetEmpty">Load results to see filters</div>' +
      '</div>'
    );
  }

  function showPage(page) {
    var totalPages = Math.max(1, Math.ceil($names.length / pageSize));
    currentPage = Math.max(0, Math.min(page, totalPages - 1));
    refreshPage();
    rebuildOccupationFilters();
    updateFeatureCounts();
    renderVisibleNames();
    reorderVisibleNames();
    loadVisibleNames();
  }

  function refreshPage() {
    var totalPages = Math.max(1, Math.ceil($names.length / pageSize));
    $names.hide();
    visibleNames().show();
    $('.pageStatus').text('Page ' + (currentPage + 1) + ' of ' + totalPages);
    $('.prevPage').prop('disabled', currentPage === 0);
    $('.nextPage').prop('disabled', currentPage >= totalPages - 1);
  }

  function visibleNames() {
    var start = currentPage * pageSize;
    return $names.slice(start, start + pageSize);
  }

  function loadVisibleNames() {
    var $visible = visibleNames();
    var completed = 0;

    if ($visible.length === 0) {
      updateProgress(100);
      return;
    }

    updateProgress(0);
    $visible.each(function(index, listItem) {
      setTimeout(function() {
        loadName($(listItem)).always(function() {
          completed++;
          updateProgress(Math.round((completed / $visible.length) * 100));
        });
      }, index * 500);
    });
  }

  function loadName($name) {
    var deferred = $.Deferred();

    if ($name.data('loaded') || $name.data('loading')) {
      return deferred.resolve().promise();
    }

    return loadMatches($name, false);
  }

  function loadMoreMatches($name) {
    return loadMatches($name, true);
  }

  function loadMatches($name, append) {
    var deferred = $.Deferred();

    if ($name.data('loading') || (append && !$name.data('has-more'))) {
      return deferred.resolve().promise();
    }

    $name.data('loading', true);
    $name.addClass('loading');
    renderLoadMoreControl($name);
    $.getJSON('query.php', {
      srsearch: $name.data('search-term'),
      offset: append ? $name.data('search-offset') : 0
    }).done(function(data) {
      var result = normalizeSearchResponse(data);
      var matches = append ? mergeMatches($name.data('matches') || [], result.matches) : result.matches;
      $name.data('loaded', true);
      $name.data('matches', matches);
      $name.data('search-offset', result.offset + result.limit);
      $name.data('has-more', result.hasMore);
      rebuildOccupationFilters();
      updateFeatureCounts();
      renderName($name);
      reorderVisibleNames();
      refreshPage();
    }).always(function() {
      $name.data('loading', false);
      $name.removeClass('loading');
      renderLoadMoreControl($name);
      deferred.resolve();
    });

    return deferred.promise();
  }

  function normalizeSearchResponse(data) {
    if ($.isArray(data)) {
      return {
        matches: data,
        offset: 0,
        limit: data.length,
        hasMore: false
      };
    }
    if (data === 'nee' || !data) {
      return {
        matches: [],
        offset: 0,
        limit: 0,
        hasMore: false
      };
    }
    return {
      matches: data.matches || [],
      offset: parseInt(data.offset, 10) || 0,
      limit: parseInt(data.limit, 10) || 0,
      hasMore: data.hasMore === true
    };
  }

  function mergeMatches(existingMatches, newMatches) {
    var seen = {};
    var merged = [];
    for (var i = 0; i < existingMatches.length; i++) {
      if (existingMatches[i].qitem) {
        seen[existingMatches[i].qitem] = true;
      }
      merged.push(existingMatches[i]);
    }
    for (var j = 0; j < newMatches.length; j++) {
      if (newMatches[j].qitem && seen[newMatches[j].qitem]) {
        continue;
      }
      if (newMatches[j].qitem) {
        seen[newMatches[j].qitem] = true;
      }
      merged.push(newMatches[j]);
    }
    return merged;
  }

  function rebuildOccupationFilters() {
    var nextFilters = {};

    visibleNames().each(function() {
      var matches = $(this).data('matches') || [];
      for (var i = 0; i < matches.length; i++) {
        if (!matchesBaseFilters(matches[i])) {
          continue;
        }

        var occupations = matches[i].occupations || [];
        for (var j = 0; j < occupations.length; j++) {
          var occupation = occupations[j];
          if (!occupation.id) {
            continue;
          }
          if (!nextFilters[occupation.id]) {
            nextFilters[occupation.id] = {
              id: occupation.id,
              label: occupation.label,
              checked: occupationFilters[occupation.id] ? occupationFilters[occupation.id].checked : true,
              count: 0
            };
          }
          nextFilters[occupation.id].count++;
        }
      }
    });

    occupationFilters = nextFilters;
    renderOccupationFilters();
  }

  function renderOccupationFilters() {
    var filters = occupationFilterList();
    var $container = $('#occupationFilters');
    $container.find('.facetOption,.facetEmpty').remove();

    if (filters.length === 0) {
      $container.append('<div class="facetEmpty">Load results to see filters</div>');
      return;
    }

    for (var i = 0; i < filters.length; i++) {
      var filter = filters[i];
      var id = 'occupation_' + filter.id;
      var $option = $('<div/>', {class: 'facetOption'});
      $('<input/>', {
        type: 'checkbox',
        id: id,
        checked: filter.checked,
        'data-occupation-id': filter.id
      }).appendTo($option);
      $('<label/>', {
        for: id,
        text: filter.label + ' (' + filter.count + ')'
      }).appendTo($option);
      $container.append($option);
    }

    $container.find('input[type="checkbox"]').on('change', function() {
      var id = $(this).data('occupation-id');
      occupationFilters[id].checked = this.checked;
      applyFilters();
    });
  }

  function occupationFilterList() {
    var filters = [];
    for (var id in occupationFilters) {
      if (occupationFilters.hasOwnProperty(id)) {
        filters.push(occupationFilters[id]);
      }
    }

    filters.sort(function(a, b) {
      if (b.count !== a.count) {
        return b.count - a.count;
      }
      return a.label.localeCompare(b.label);
    });

    return filters.slice(0, 30);
  }

  function applyFilters() {
    rebuildOccupationFilters();
    updateFeatureCounts();
    renderVisibleNames();
    reorderVisibleNames();
    refreshPage();
  }

  function updateFeatureCounts() {
    var counts = {
      sports: 0,
      orcid: 0,
      peerage: 0
    };

    visibleNames().each(function() {
      var matches = $(this).data('matches') || [];
      for (var i = 0; i < matches.length; i++) {
        if (!matchesAliveInWindow(matches[i]) || !matchesOccupationFilter(matches[i])) {
          continue;
        }
        if (matches[i].isSportsPerson === true) {
          counts.sports++;
        }
        if (matches[i].hasOrcid === true) {
          counts.orcid++;
        }
        if (matches[i].hasPeerageId === true) {
          counts.peerage++;
        }
      }
    });

    $('#sportsPersonCount').text('(' + counts.sports + ')');
    $('#orcidCount').text('(' + counts.orcid + ')');
    $('#peerageCount').text('(' + counts.peerage + ')');
  }

  function renderVisibleNames() {
    visibleNames().each(function() {
      renderName($(this));
    });
  }

  function renderName($name) {
    var matches = $name.data('matches') || [];
    var visibleMatches = filterMatches(matches);
    var $responseList = $name.children('.response');
    var categories = '';

    $name.children('.commonslink').remove();
    $name.children('.loadMoreMatches').remove();
    $responseList.empty();
    $name.removeAttr('image');
    $name.attr('weight', '-1');

    if (!visibleMatches.length) {
      renderLoadMoreControl($name);
      return;
    }

    for (var i = 0; i < visibleMatches.length; i++) {
      categories += visibleMatches[i].categories ? ' ' + visibleMatches[i].categories : '';
      appendMatch($name, $responseList, visibleMatches[i]);
    }

    renderCommonsLink($name, categories);
    tinysort($responseList.children(), {attr: 'weight'});
    renderLoadMoreControl($name);
  }

  function renderLoadMoreControl($name) {
    $name.children('.loadMoreMatches').remove();
    if (!$name.data('has-more')) {
      return;
    }

    var $button = $('<button/>', {
      type: 'button',
      class: 'pageButton loadMoreButton',
      text: $name.data('loading') ? 'Loading...' : 'Load more search results'
    }).prop('disabled', $name.data('loading') === true);

    $('<div/>', {class: 'loadMoreMatches'})
      .append($button)
      .insertAfter($name.children('.response'));

    $button.on('click', function() {
      loadMoreMatches($name);
    });
  }

  function reorderVisibleNames() {
    var visible = visibleNames().toArray();
    var pageEnd = (currentPage + 1) * pageSize;
    var $anchor = $names.eq(pageEnd);
    var $list = $('#names');

    visible.sort(function(a, b) {
      var aEmpty = nameHasNoVisibleMatches($(a)) ? 1 : 0;
      var bEmpty = nameHasNoVisibleMatches($(b)) ? 1 : 0;
      if (aEmpty !== bEmpty) {
        return aEmpty - bEmpty;
      }
      return $(a).data('original-index') - $(b).data('original-index');
    });

    for (var i = 0; i < visible.length; i++) {
      if ($anchor.length) {
        $(visible[i]).insertBefore($anchor);
      } else {
        $list.append(visible[i]);
      }
    }
  }

  function nameHasNoVisibleMatches($name) {
    return $name.data('loaded') && filterMatches($name.data('matches') || []).length === 0;
  }

  function filterMatches(matches) {
    var filtered = [];
    for (var i = 0; i < matches.length; i++) {
      if (matchesAliveInWindow(matches[i]) && matchesIncludeToggleFilters(matches[i]) && matchesOccupationFilter(matches[i])) {
        filtered.push(matches[i]);
      }
    }
    return filtered;
  }

  function matchesAliveInWindow(item) {
    var startYear = parseInt($('#amount1').val(), 10);
    var endYear = parseInt($('#amount2').val(), 10);
    var bounds = candidateLifeBounds(item);
    var earliestKnownYear = bounds.earliestKnownYear;
    var latestPossibleYear = bounds.latestKnownYear;

    if (latestPossibleYear === null && earliestKnownYear !== null) {
      latestPossibleYear = earliestKnownYear + assumedMaxAge;
    }

    return (latestPossibleYear === null || latestPossibleYear >= startYear) && (earliestKnownYear === null || earliestKnownYear <= endYear);
  }

  function matchesBaseFilters(item) {
    return matchesAliveInWindow(item) && matchesIncludeToggleFilters(item);
  }

  function matchesIncludeToggleFilters(item) {
    return ($('#sportsPersonCheck')[0].checked || item.isSportsPerson !== true) &&
      ($('#orcidCheck')[0].checked || item.hasOrcid !== true) &&
      ($('#peerageCheck')[0].checked || item.hasPeerageId !== true);
  }

  function matchesOccupationFilter(item) {
    var occupations = item.occupations || [];
    if (occupations.length === 0) {
      return true;
    }

    for (var i = 0; i < occupations.length; i++) {
      var filter = occupationFilters[occupations[i].id];
      if (!filter || filter.checked) {
        return true;
      }
    }
    return false;
  }

  function appendMatch($name, $responseList, item) {
    var $response = $('<li/>', {
      class: 'block-2',
      weight: item.sitelinks
    });

    $('<b/>').text(item.sitelinks).appendTo($response);
    $response.append(':');
    $('<a/>', {
      href: 'https://wikidata.org/wiki/' + item.qitem,
      target: '_blank',
      text: item.itemLabel || item.qitem
    }).appendTo($response);

    var $thumb = $('<span/>', {class: 'matchThumb'}).prependTo($response);

    appendDescriptor($response, item);

    if (item.image) {
      $thumb.css('background-image', 'url("' + item.image + '")');
      $name.attr('image', true);
    }
    if (item.isSportsPerson === true) {
      $response.addClass('isSportsPerson');
    }

    $responseList.append($response);
    if (parseInt(item.sitelinks, 10) > parseInt($name.attr('weight'), 10)) {
      $name.attr('weight', item.sitelinks);
    }
  }

  function appendDescriptor($response, item) {
    var years = yearRange(item);
    var description = displayDescription(item);
    if (years) {
      $response.append(' (' + years + ')');
    }
    if (description) {
      $('<span/>', {
        class: 'personDescription',
        text: description
      }).appendTo($response);
    }
  }

  function displayDescription(item) {
    var description = cleanDescription(item.description || '');
    var generated = generatedDescription(item);
    if (!description) {
      return generated;
    }
    if (generated) {
      return description + '; ' + generated;
    }
    return description;
  }

  function cleanDescription(description) {
    return $.trim(description
      .replace(/\s*\((?:c\.\s*)?-?\d{3,4}\s*[-\u2013]\s*-?\d{1,4}\)\s*/g, ' ')
      .replace(/\s*(?:c\.\s*)?-?\d{3,4}\s*[-\u2013]\s*-?\d{1,4}\s*/g, ' ')
      .replace(/\s*\((?:born|b\.|died|d\.)\s+-?\d{3,4}\)\s*/gi, ' ')
      .replace(/\s+/g, ' ')
      .replace(/^[\s,;:.-]+/, '')
      .replace(/[\s,;:.-]+$/, ''));
  }

  function generatedDescription(item) {
    var description = cleanDescription(item.description || '');
    var descriptionText = comparableText(description);
    var occupations = labelsFor(item.occupations).filter(function(label) {
      return !descriptionImpliesLabel(descriptionText, label);
    });
    var countries = labelsFor(item.countries).filter(function(label) {
      return !descriptionImpliesLabel(descriptionText, label);
    }).map(displayCountryLabel);
    countries = uniqueLabels(countries);
    var parts = [];
    if (countries.length) {
      parts.push(countries.join('/'));
    }
    if (occupations.length) {
      parts.push(occupations.join('/'));
    }
    return parts.join(' ');
  }

  function descriptionImpliesLabel(descriptionText, label) {
    var normalized = comparableText(label);
    if (!normalized) {
      return false;
    }
    if (descriptionText.indexOf(normalized) !== -1) {
      return true;
    }
    if (normalized === 'actor' || normalized === 'film actor' || normalized === 'stage actor' || normalized === 'television actor') {
      return /\b(actor|actress|performer)\b/.test(descriptionText);
    }
    var synonyms = labelSynonyms(normalized);
    for (var i = 0; i < synonyms.length; i++) {
      if (descriptionText.indexOf(synonyms[i]) !== -1) {
        return true;
      }
    }
    if (normalized === 'playwright') {
      return /\b(writer|dramatist|playwright)\b/.test(descriptionText);
    }
    if (normalized === 'bassist') {
      return /\b(bassist|bass player|bass guitarist)\b/.test(descriptionText);
    }
    if (normalized === 'cultural manager') {
      return /\b(cultural manager|culture manager)\b/.test(descriptionText);
    }
    if (normalized === 'economist') {
      return /\b(economist|economics)\b/.test(descriptionText);
    }
    if (normalized === 'film producer') {
      return /\b(film producer|producer)\b/.test(descriptionText);
    }
    if (normalized === 'association football player') {
      return /\b(association football player|football player|footballer|soccer player)\b/.test(descriptionText);
    }
    if (normalized === 'ice hockey player') {
      return /\b(ice hockey player|ice hockey coach|hockey player|hockey coach)\b/.test(descriptionText);
    }
    if (normalized === 'musician') {
      return /\b(musician|composer|instrumentalist|singer)\b/.test(descriptionText);
    }
    return false;
  }

  function labelSynonyms(normalized) {
    var synonymMap = {
      'kingdom of the netherlands': ['dutch', 'netherlands', 'holland'],
      'netherlands': ['dutch', 'holland'],
      'dutch republic': ['dutch', 'holland'],
      'united states': ['american', 'usa', 'u s', 'u.s.'],
      'united kingdom': ['british', 'english', 'scottish', 'welsh', 'irish', 'uk'],
      'united kingdom of great britain and ireland': ['british', 'english', 'scottish', 'welsh', 'irish', 'uk'],
      'england': ['english', 'british'],
      'scotland': ['scottish', 'british'],
      'wales': ['welsh', 'british'],
      'ireland': ['irish'],
      'france': ['french'],
      'kingdom of france': ['french'],
      'germany': ['german', 'german born'],
      'german empire': ['german'],
      'prussia': ['prussian', 'german'],
      'east germany': ['east german', 'german'],
      'west germany': ['west german', 'german'],
      'austria': ['austrian'],
      'austria-hungary': ['austro-hungarian', 'austrian', 'hungarian'],
      'switzerland': ['swiss'],
      'italy': ['italian'],
      'spain': ['spanish'],
      'portugal': ['portuguese'],
      'belgium': ['belgian'],
      'luxembourg': ['luxembourgish'],
      'kingdom of denmark': ['danish', 'denmark'],
      'denmark': ['danish'],
      'norway': ['norwegian'],
      'sweden': ['swedish'],
      'finland': ['finnish'],
      'iceland': ['icelandic'],
      'poland': ['polish'],
      'hungary': ['hungarian'],
      'romania': ['romanian'],
      'greece': ['greek'],
      'turkey': ['turkish'],
      'ottoman empire': ['ottoman', 'turkish'],
      'russia': ['russian'],
      'russian empire': ['russian'],
      'soviet union': ['soviet', 'ussr', 'russian'],
      'ukraine': ['ukrainian'],
      'belarus': ['belarusian'],
      'lithuania': ['lithuanian'],
      'latvia': ['latvian'],
      'estonia': ['estonian'],
      'czech republic': ['czech', 'czechia', 'czechoslovak'],
      'czechia': ['czech', 'czech republic', 'czechoslovak'],
      'czechoslovakia': ['czech', 'czechoslovak', 'slovak'],
      'slovakia': ['slovak'],
      'slovenia': ['slovenian'],
      'croatia': ['croatian'],
      'serbia': ['serbian'],
      'yugoslavia': ['yugoslav', 'serbian', 'croatian', 'slovenian'],
      'bosnia and herzegovina': ['bosnian', 'herzegovinian'],
      'bulgaria': ['bulgarian'],
      'albania': ['albanian'],
      'canada': ['canadian'],
      'australia': ['australian'],
      'new zealand': ['new zealand'],
      'japan': ['japanese'],
      'china': ['chinese'],
      'india': ['indian'],
      'indonesia': ['indonesian'],
      'philippines': ['filipino', 'philippine'],
      'south korea': ['south korean', 'korean'],
      'north korea': ['north korean', 'korean'],
      'israel': ['israeli'],
      'iran': ['iranian', 'persian'],
      'iraq': ['iraqi'],
      'egypt': ['egyptian'],
      'morocco': ['moroccan'],
      'tunisia': ['tunisian'],
      'algeria': ['algerian'],
      'south africa': ['south african'],
      'nigeria': ['nigerian'],
      'kenya': ['kenyan'],
      'ethiopia': ['ethiopian'],
      'mexico': ['mexican'],
      'brazil': ['brazilian'],
      'argentina': ['argentine', 'argentinian'],
      'chile': ['chilean'],
      'colombia': ['colombian'],
      'peru': ['peruvian'],
      'venezuela': ['venezuelan'],
      'uruguay': ['uruguayan']
    };

    return synonymMap[normalized] || [];
  }

  function displayCountryLabel(label) {
    var normalized = comparableText(label);
    var displayMap = {
      'kingdom of denmark': 'Denmark',
      'kingdom of the netherlands': 'Netherlands',
      'united kingdom of great britain and ireland': 'United Kingdom',
      'czech republic': 'Czechia'
    };

    return displayMap[normalized] || label;
  }

  function uniqueLabels(labels) {
    var seen = {};
    var unique = [];
    for (var i = 0; i < labels.length; i++) {
      var key = comparableText(labels[i]);
      if (!key || seen[key]) {
        continue;
      }
      seen[key] = true;
      unique.push(labels[i]);
    }
    return unique;
  }

  function comparableText(value) {
    return $.trim(String(value || '')
      .toLowerCase()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, ' ')
      .replace(/\s+/g, ' '));
  }

  function labelsFor(values) {
    var labels = [];
    values = values || [];
    for (var i = 0; i < values.length; i++) {
      if (values[i].label) {
        labels.push(values[i].label);
      }
    }
    return labels;
  }

  function yearRange(item) {
    var bounds = candidateLifeBounds(item);
    var birthYear = bounds.birthYear;
    var deathYear = bounds.deathYear;
    var baptismYear = bounds.baptismYear;
    var floruitYear = bounds.floruitYear;
    var workPeriodStartYear = bounds.workPeriodStartYear;
    var workPeriodEndYear = bounds.workPeriodEndYear;
    if (birthYear !== null && deathYear !== null) {
      return birthYear + '-' + deathYear;
    }
    if (birthYear !== null) {
      return 'b. ' + birthYear;
    }
    if (deathYear !== null) {
      return 'd. ' + deathYear;
    }
    if (baptismYear !== null) {
      return 'bapt. ' + baptismYear;
    }
    if (workPeriodStartYear !== null && workPeriodEndYear !== null) {
      return 'work ' + workPeriodStartYear + '-' + workPeriodEndYear;
    }
    if (workPeriodStartYear !== null) {
      return 'work from ' + workPeriodStartYear;
    }
    if (workPeriodEndYear !== null) {
      return 'work until ' + workPeriodEndYear;
    }
    if (floruitYear !== null) {
      return 'fl. ' + floruitYear;
    }
    return '';
  }

  function candidateLifeBounds(item) {
    var birthYear = yearFromDate(item.dateOfBirth);
    var deathYear = yearFromDate(item.dateOfDeath);
    var baptismYear = yearFromDate(item.dateOfBaptism);
    var floruitYear = yearFromDate(item.floruit);
    var workPeriodStartYear = yearFromDate(item.workPeriodStart);
    var workPeriodEndYear = yearFromDate(item.workPeriodEnd);
    var earliestKnownYear = firstYear([birthYear, baptismYear, workPeriodStartYear, floruitYear]);
    var latestKnownYear = firstYear([deathYear, workPeriodEndYear, floruitYear, workPeriodStartYear]);

    return {
      birthYear: birthYear,
      deathYear: deathYear,
      baptismYear: baptismYear,
      floruitYear: floruitYear,
      workPeriodStartYear: workPeriodStartYear,
      workPeriodEndYear: workPeriodEndYear,
      earliestKnownYear: earliestKnownYear,
      latestKnownYear: latestKnownYear
    };
  }

  function firstYear(years) {
    for (var i = 0; i < years.length; i++) {
      if (years[i] !== null) {
        return years[i];
      }
    }
    return null;
  }

  function yearFromDate(value) {
    if (!value) {
      return null;
    }
    var match = value.match(/^-?\d+/);
    return match ? parseInt(match[0], 10) : null;
  }

  function renderCommonsLink($name, categories) {
    var searchTerm = $name.data('search-term');
    var $link = $('<span/>', {class: 'commonslink'});
    $link.append(document.createTextNode('Search for '));
    $('<a/>', {
      href: 'https://commons.wikimedia.org/w/index.php?search=' + encodeURI('"' + searchTerm + '" ' + categories),
      target: '_blank',
      title: 'commons search for ' + searchTerm,
      text: '"' + searchTerm + '"'
    }).appendTo($link);
    $link.append(document.createTextNode(categories ? ' on Commons, minus categorized images' : ' on Commons'));
    $name.children('.response').before($link);
  }

  function updateProgress(value) {
    $('#progressbar').progressbar({ value: value });
  }
});
