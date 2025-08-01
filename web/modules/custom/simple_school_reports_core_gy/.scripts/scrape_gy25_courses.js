// Go to:
// https://www.skolverket.se/undervisning/gymnasieskolan/program-och-amnen-i-gymnasieskolan/hitta-program-och-amnen-i-gymnasieskolan-gy25?url=907561864%2Fsyllabuscw%2Fjsp%2Fsearchgy2025.htm%3FalphaSearchString%3D%26searchType%3DFREETEXT%26searchRange%3DGRADE_SUBJECT%26subjectCategory%3D%26searchString%3D&sv.url=12.2b12d9b318c46bc9c3661
// The open terminal and run the following command js commnd.

(async function(){
  // Define the new headers for the CSV file
  const headers = ['Course', 'Course code', 'Subject', 'Subject code', 'Link', 'Points', 'Levels'];
  const csvData = [headers];
  console.log("🚀 Starting CSV export process...");

  // Select all the table wrappers within the main result list
  const tableWrappers = document.querySelectorAll('.result_list .table-wrapper');

  // Use a for...of loop to correctly handle asynchronous operations inside the loop
  for (const wrapper of tableWrappers) {
    const table = wrapper.querySelector('table.searchresult');
    if (!table) continue;

    // --- 1. Extract Basic Subject Information ---
    const headerCell = table.querySelector('thead tr:first-child th');
    if (!headerCell) continue;

    const subjectName = headerCell.querySelector('a').textContent.trim();
    const subjectCodeMatch = headerCell.textContent.match(/Kod:\s*(\S+)/);
    const subjectCode = subjectCodeMatch ? subjectCodeMatch[1] : '';
    console.log(`Processing Subject: ${subjectName} (${subjectCode})`);

    // --- 2. Fetch and Parse Points Data (once per subject) ---
    const pointsMap = {};
    const firstCourseLinkEl = table.querySelector('tbody tr th a');

    if (firstCourseLinkEl) {
      // Construct the full URL to fetch
      const fetchUrl = window.location.origin + firstCourseLinkEl.getAttribute('href');
      console.log(` -> Fetching points data from: ${fetchUrl}`);
      try {
        const response = await fetch(fetchUrl);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const htmlText = await response.text();

        // Use DOMParser to parse the fetched HTML without rendering it
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');

        // Find all course articles and extract points
        const courseArticles = doc.querySelectorAll('.courses-wrapper article');
        courseArticles.forEach(article => {
          const linkInArticle = article.querySelector('h3 a');
          if (linkInArticle) {
            const text = linkInArticle.textContent.trim();
            // Regex to capture level name and points, e.g., "Nivå 1a, 100 poäng"
            const match = text.match(/(.+?),\s*(\d+)\s*poäng/);
            if (match) {
              const levelName = match[1].trim();
              const points = match[2];
              pointsMap[levelName] = points;
            }
          }
        });
        console.log(` -> Found points for ${Object.keys(pointsMap).length} levels.`);
      } catch (error) {
        console.error(` -> Failed to fetch or parse points for ${subjectName}:`, error);
      }
    }

    // --- 3. Gather and Process All Course Data for the Subject ---
    const allRows = table.querySelectorAll('tbody tr');
    const courseDetails = Array.from(allRows).map(row => {
      const linkEl = row.querySelector('th a');
      const codeEl = row.querySelector('td');
      if (!linkEl || !codeEl) return null;
      return {
        levelName: linkEl.textContent.trim(),
        courseCode: codeEl.textContent.trim(),
        link: window.location.origin + linkEl.getAttribute('href')
      };
    }).filter(Boolean); // Filter out any null entries from malformed rows

    // --- 4. Build Final CSV Rows ---
    courseDetails.forEach(course => {
      // Determine the related levels (same logic as before)
      let relatedLevels = [];
      const lastChar = course.levelName.slice(-1);
      if (lastChar.match(/[a-zA-Z]/)) {
        relatedLevels = courseDetails
          .filter(item => item.levelName.slice(-1) === lastChar)
          .map(item => item.courseCode);
      } else {
        relatedLevels = courseDetails.map(item => item.courseCode);
      }

      // Look up the points from the map created in step 2
      const points = pointsMap[course.levelName] || 'N/A';

      // Construct the row for the CSV file
      const csvRow = [
        `${subjectName} - ${course.levelName}`, // Course
        course.courseCode,                      // Course code
        subjectName,                            // Subject
        subjectCode,                            // Subject code
        course.link,                            // Link
        points,                                 // Points
        relatedLevels.join(',')                 // Levels
      ];
      csvData.push(csvRow);
    });
  }

  // --- 5. Format and Download the CSV File ---
  if (csvData.length > 1) {
    // Helper function to correctly quote fields containing commas or quotes
    const escapeCsvCell = (cell) => {
      const strCell = String(cell == null ? '' : cell); // handle null/undefined
      if (strCell.includes(',') || strCell.includes('"') || strCell.includes('\n')) {
        return `"${strCell.replace(/"/g, '""')}"`; // Escape quotes by doubling them
      }
      return strCell;
    };

    const csvContent = csvData.map(row => row.map(escapeCsvCell).join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', 'course_catalog_gy25.csv');
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    console.log(`✅ Success! CSV file with ${csvData.length - 1} courses has been downloaded.`);
  } else {
    console.log("No course data was found to create a CSV file.");
  }
})();
