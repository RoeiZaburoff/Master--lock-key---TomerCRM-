(function (root) {
  var STATE_NAMES = {
    'alabama':'AL','alaska':'AK','arizona':'AZ','arkansas':'AR','california':'CA',
    'colorado':'CO','connecticut':'CT','delaware':'DE','florida':'FL','georgia':'GA',
    'hawaii':'HI','idaho':'ID','illinois':'IL','indiana':'IN','iowa':'IA','kansas':'KS',
    'kentucky':'KY','louisiana':'LA','maine':'ME','maryland':'MD','massachusetts':'MA',
    'michigan':'MI','minnesota':'MN','mississippi':'MS','missouri':'MO','montana':'MT',
    'nebraska':'NE','nevada':'NV','new hampshire':'NH','new jersey':'NJ','new mexico':'NM',
    'new york':'NY','north carolina':'NC','north dakota':'ND','ohio':'OH','oklahoma':'OK',
    'oregon':'OR','pennsylvania':'PA','rhode island':'RI','south carolina':'SC',
    'south dakota':'SD','tennessee':'TN','texas':'TX','utah':'UT','vermont':'VT',
    'virginia':'VA','washington':'WA','west virginia':'WV','wisconsin':'WI','wyoming':'WY'
  };

  var STATE_SORTED = Object.keys(STATE_NAMES).sort(function (a, b) { return b.length - a.length; });

  // Fields whose values are aggregated into notes/description
  var DESC_FIELDS = [
    /^(?:type)\s*[:\-]\s*(.+)/i,
    /^(?:job)\s*[:\-]\s*(.+)/i,
    /^(?:occu(?:pation)?)\s*[:\-]\s*(.+)/i,
    /^(?:notes?)\s*[:\-]\s*(.+)/i,
    /^(?:desc(?:ription)?)\s*[:\-]\s*(.+)/i,
    /^(?:pdl)\s*[:\-]\s*(.+)/i,
    /^(?:ref)\s*[:\-]\s*(.+)/i,
    /^(?:id)\s*[:\-#]?\s*(.+)/i
  ];

  // All recognised label prefixes — used to skip lines from unmapped pool
  var LABEL_RE = /^(?:full[\s_]?name|name|customer|n|phone|ph|tel|mobile|cell|contact|address|addr|location|loc|city|state|zip|postal|email|e-mail|mail|type|job|occu(?:pation)?|notes?|desc(?:ription)?|pdl|ref|id|job\s*ext(?:ension)?)\s*[:\-#]/i;

  var HEADER_RE = /services|locksmith|company\s*name|dispatch/i;

  function isLabel(line) { return LABEL_RE.test(line.trim()); }

  function extractFirst(text, patterns) {
    for (var i = 0; i < patterns.length; i++) {
      var m = text.match(patterns[i]);
      if (m && m[1] && m[1].trim()) return m[1].trim();
    }
    return '';
  }

  function normalizePhone(raw) {
    var extM = raw.match(/(?:,|ext\.?|extension|(?<!\w)x(?!\w))\s*(\d+)/i);
    var ext    = extM ? extM[1] : '';
    var base   = extM ? raw.slice(0, extM.index) : raw;
    var digits = base.replace(/\D/g, '');
    if (digits.length === 11 && digits[0] === '1') digits = digits.slice(1);
    if (digits.length !== 10) return raw.trim();
    var fmt = '(' + digits.slice(0, 3) + ') ' + digits.slice(3, 6) + '-' + digits.slice(6);
    return ext ? fmt + ' ext ' + ext : fmt;
  }

  function normalizeState(s) {
    s = s.trim();
    if (/^[A-Z]{2}$/i.test(s)) return s.toUpperCase();
    return STATE_NAMES[s.toLowerCase()] || s.toUpperCase();
  }

  function stripCountry(s) {
    return s.replace(/,?\s*united\s*states(?:\s*of\s*america)?\s*$/i, '').trim();
  }

  function splitAddress(street, tail, result) {
    result.address = street.trim();
    if (!tail) return;
    tail = stripCountry(tail.trim());
    if (!tail) return;

    var zipM = tail.match(/(\d{5}(?:-\d{4})?)\s*$/);
    if (zipM) {
      result.zip = zipM[1];
      tail = tail.slice(0, tail.length - zipM[0].length).trim().replace(/,\s*$/, '');
    }

    var stateNameM = tail.match(new RegExp(',?\\s*((?:' + STATE_SORTED.join('|') + '))\\s*$', 'i'));
    if (stateNameM) {
      result.state = normalizeState(stateNameM[1]);
      tail = tail.slice(0, tail.length - stateNameM[0].length).trim().replace(/,\s*$/, '');
      result.city = tail;
      return;
    }

    var abbrM = tail.match(/,?\s*([A-Z]{2})\s*$/i);
    if (abbrM) {
      result.state = abbrM[1].toUpperCase();
      tail = tail.slice(0, tail.length - abbrM[0].length).trim().replace(/,\s*$/, '');
      result.city = tail;
      return;
    }

    var commaM = tail.match(/^([^,]+),\s*(.+)$/);
    if (commaM) {
      result.city  = commaM[1].trim();
      result.state = normalizeState(commaM[2].trim());
    } else {
      result.city = tail;
    }
  }

  function parseAddress(raw, result) {
    // Labeled: "Address:", "Addr:", "Location:", "Loc:"
    var labelM = raw.match(/(?:address|addr|location|loc)\s*[:\-]\s*([^\n\r|]+)([\r\n]+([^\n\r|]+))?/i);
    if (labelM) {
      var street   = labelM[1].trim();
      var nextLine = labelM[3] ? labelM[3].trim() : '';
      if (/,/.test(street) && /\d{5}/.test(street)) {
        var parts = street.split(',');
        splitAddress(parts[0], parts.slice(1).join(','), result);
      } else if (nextLine) {
        splitAddress(street, nextLine, result);
      } else {
        splitAddress(street, '', result);
      }
      return;
    }

    // Inline comma form: "123 Main St, City, PA 15068[, United States]"
    var inlineM = raw.match(/(\d+[^,\n\r]+),([^,\n\r]+),\s*([^\n\r,]+\d{5}(?:-\d{4})?[^\n\r]*)/);
    if (inlineM) {
      splitAddress(inlineM[1], inlineM[2] + ',' + inlineM[3], result);
      return;
    }

    // No-comma street type pattern: "123 Main St Springfield IL 62701"
    var noCommaM = raw.match(/(\d+\s+[^\n\r]*?\b(?:street|st|avenue|ave|road|rd|drive|dr|lane|ln|boulevard|blvd|way|court|ct|place|pl|circle|cir|terrace|ter|trail|trl|parkway|pkwy|highway|hwy|route|rt)\.?)\s+([A-Za-z][A-Za-z\s]*?)\s+([A-Z]{2})\s+(\d{5}(?:-\d{4})?)\s*$/im);
    if (noCommaM) {
      result.address = noCommaM[1].trim();
      result.city    = noCommaM[2].trim();
      result.state   = noCommaM[3].toUpperCase();
      result.zip     = noCommaM[4];
      return;
    }

    // Multi-line: street on one line, "City, ST ZIP" on next
    var multiM = raw.match(/(\d[^\n\r]+)\n([^\n\r]*\d{5}[^\n\r]*)/);
    if (multiM) {
      splitAddress(multiM[1], multiM[2], result);
    }
  }

  function parse(raw) {
    var result = { name: '', phone: '', email: '', address: '', city: '', state: '', zip: '', notes: '' };

    try {
      result.name = extractFirst(raw, [
        /(?:full[\s_]?name|name|customer)\s*[:\-]\s*([^\n\r,|]+)/i,
        /(?:^|\n)n\s*[:\-]\s*([^\n\r,|]+)/i
      ]);
      if (!result.name) {
        var lines = raw.split(/[\r\n]+/);
        for (var ni = 0; ni < lines.length; ni++) {
          var nl = lines[ni].trim();
          if (!nl || /\d/.test(nl) || /@/.test(nl) || isLabel(nl) || HEADER_RE.test(nl)) continue;
          if (/^[A-Za-z]+(?:[\s\-'][A-Za-z]+)+$/.test(nl)) { result.name = nl; break; }
        }
      }

      var rawPhone = extractFirst(raw, [
        /(?:phone|ph|tel|mobile|cell|contact)\s*[:\-]?\s*((?:\+?1[\s.\-]?)?\(?\d{3}\)?[\s.\-]\d{3}[\s.\-]\d{4}(?:\s*(?:,|ext\.?|extension|(?<!\w)x(?!\w))\s*\d+)?)/i,
        /((?:\+?1[\s.\-]?)?\(?\d{3}\)?[\s.\-]\d{3}[\s.\-]\d{4}(?:\s*(?:,|ext\.?|extension|(?<!\w)x(?!\w))\s*\d+)?)/i,
        /(?:^|\n)\s*(\d{10})\s*(?:\n|$)/
      ]);
      if (rawPhone) result.phone = normalizePhone(rawPhone);

      var jobExtM = raw.match(/job\s*ext(?:ension)?\s*[:\-]\s*(\d+)/i);
      if (jobExtM && result.phone && !/ext/i.test(result.phone)) {
        result.phone += ' ext ' + jobExtM[1];
      }

      result.email = extractFirst(raw, [
        /(?:email|e-mail|mail)\s*[:\-]\s*([^\s\n\r]+@[^\s\n\r]+)/i,
        /\b([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})\b/
      ]);

      parseAddress(raw, result);

      var allLines  = raw.split(/[\r\n]+/);
      var descParts = [];
      var unmapped  = [];
      var seenFirst = false;

      var phoneDigits = result.phone ? result.phone.replace(/\D/g, '').slice(-10) : '';
      var addrBlock = [result.address, result.city, result.state, result.zip].filter(Boolean).join(' ').toLowerCase();

      for (var i = 0; i < allLines.length; i++) {
        var line = allLines[i].trim();
        if (!line) continue;

        // Aggregate desc-field labels
        var captured = false;
        for (var di = 0; di < DESC_FIELDS.length; di++) {
          var dm = line.match(DESC_FIELDS[di]);
          if (dm) { descParts.push(dm[1].trim()); captured = true; break; }
        }
        if (captured) continue;

        if (isLabel(line)) continue;
        if (/^job\s*ext(?:ension)?\s*[:\-]/i.test(line)) continue;

        if (result.name  && line === result.name)  continue;
        if (result.email && line === result.email) continue;
        if (phoneDigits && line.replace(/\D/g, '').slice(-10) === phoneDigits) continue;

        var lineLow = line.toLowerCase();
        if (result.address && lineLow === result.address.toLowerCase()) continue;
        if (addrBlock && lineLow === addrBlock) continue;
        if (result.city && result.state && /\d{5}/.test(line) &&
            line.toLowerCase().indexOf(result.city.toLowerCase()) !== -1) continue;
        if (/^united\s*states(?:\s*of\s*america)?$/i.test(line)) continue;

        // Header: first unmatched line that looks like a company/service header
        if (!seenFirst && HEADER_RE.test(line)) {
          descParts.unshift(line);
          seenFirst = true;
          continue;
        }
        seenFirst = true;

        unmapped.push(line);
      }

      result.notes = descParts.concat(unmapped).join('\n');
    } catch (e) {}

    return result;
  }

  root.LeadParser = { parse: parse };
}(window));
