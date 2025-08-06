// Go to:
// https://www.skolverket.se/undervisning/gymnasieskolan/program-och-amnen-i-gymnasieskolan/hitta-program-och-amnen-i-gymnasieskolan-gy25?url=907561864%2Fsyllabuscw%2Fjsp%2Fsearchgy2025.htm%3FalphaSearchString%3D%26searchType%3DFREETEXT%26searchRange%3DGRADE_SUBJECT%26subjectCategory%3D%26searchString%3D&sv.url=12.2b12d9b318c46bc9c3661
// The open terminal and run the following command js commnd.

(async function() {
  /**
   * A helper function to pause execution for a specified duration.
   * @param {number} ms - The number of milliseconds to sleep.
   * @returns {Promise<void>}
   */
  const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

  /**
   * Fetches and parses the points for a given subject URL with a retry mechanism.
   * Now validates that all expected levels are found after parsing.
   * @param {string} url - The URL to fetch data from.
   * @param {string} subjectName - The name of the subject for logging purposes.
   * @param {string[]} expectedLevels - An array of level names that must be found.
   * @param {object} config - Configuration for retries and delay.
   * @returns {Promise<object>} A promise that resolves to a map of level names to points.
   */
  async function fetchAndParsePointsWithRetry(url, subjectName, expectedLevels, config) {
    const { MAX_RETRIES, RETRY_DELAY } = config;

    for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
      try {
        const response = await fetch(url);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const htmlText = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');

        const pointsMap = {};
        const courseArticles = doc.querySelectorAll('.courses-wrapper article');
        courseArticles.forEach(article => {
          const linkInArticle = article.querySelector('h3 a');
          if (linkInArticle) {
            const text = linkInArticle.textContent.trim();
            const match = text.match(/(.+?),\s*(\d+)\s*poäng/);
            if (match) {
              // Log has been moved from here for cleaner output
              pointsMap[match[1].trim()] = match[2];
            }
          }
        });

        // CRITICAL VALIDATION: Check if all expected levels were found.
        const parsedLevels = Object.keys(pointsMap);
        const missingLevels = expectedLevels.filter(level => !parsedLevels.includes(level));

        if (missingLevels.length > 0) {
          throw new Error(`Parsing failed to find all levels. Missing: ${missingLevels.join(', ')}`);
        }

        console.log(`✅ Success fetching and validating points for ${subjectName}.`);
        return pointsMap; // Success, exit the loop and return data

      } catch (error) {
        console.warn(`Attempt ${attempt}/${MAX_RETRIES} failed for ${subjectName}: ${error.message}`);
        if (attempt === MAX_RETRIES) {
          // This is the last attempt, re-throw the error to abort the script
          throw new Error(`Failed to fetch data for ${subjectName} after ${MAX_RETRIES} attempts.`);
        }
        // Wait before the next retry
        await sleep(RETRY_DELAY * attempt); // Increase delay for subsequent retries
      }
    }
  }

  // --- Configuration ---
  const BATCH_SIZE = 15; // How many subjects to fetch in parallel
  const MAX_RETRIES = 5;
  const RETRY_DELAY = 5000; // in milliseconds

  console.log(`🚀 Starting CSV export... (Batch Size: ${BATCH_SIZE}, Max Retries: ${MAX_RETRIES})`);

  const headers = ['Course', 'Course code', 'Subject', 'Subject code', 'Link', 'Points', 'Levels'];
  const tableWrappers = document.querySelectorAll('.result_list .table-wrapper');
  const pointsCache = new Map(); // Cache for storing fetched points data

  // --- 1. Collect all unique fetch jobs and their expected levels ---
  const fetchJobs = new Map();
  tableWrappers.forEach(wrapper => {
    const table = wrapper.querySelector('table.searchresult');
    const headerCell = table?.querySelector('thead tr:first-child th');
    const firstCourseLinkEl = table?.querySelector('tbody tr th a');

    if (headerCell && firstCourseLinkEl) {
      const subjectName = headerCell.querySelector('a').textContent.trim();
      const fetchUrl = window.location.origin + firstCourseLinkEl.getAttribute('href');

      // If seeing this subject's URL for the first time, initialize it
      if (!fetchJobs.has(fetchUrl)) {
        fetchJobs.set(fetchUrl, { subjectName, expectedLevels: [] });
      }

      // Add all level names from this table to the job's expectedLevels array
      const job = fetchJobs.get(fetchUrl);
      const courseRows = table.querySelectorAll('tbody tr');
      courseRows.forEach(row => {
        const levelName = row.querySelector('th a')?.textContent.trim();
        if (levelName && !job.expectedLevels.includes(levelName)) {
          job.expectedLevels.push(levelName);
        }
      });
    }
  });

  // --- 2. Execute fetch jobs in parallel batches ---
  const allJobPromises = Array.from(fetchJobs.entries()).map(([url, { subjectName, expectedLevels }]) =>
    async () => {
      await sleep(Math.random() * 2000); // Random delay to avoid hitting the server too hard
      const pointsData = await fetchAndParsePointsWithRetry(url, subjectName, expectedLevels, { MAX_RETRIES, RETRY_DELAY });
      pointsCache.set(url, pointsData);
    }
  );

  try {
    for (let i = 0; i < allJobPromises.length; i += BATCH_SIZE) {
      const batch = allJobPromises.slice(i, i + BATCH_SIZE);
      console.log(`Fetching batch ${Math.floor(i / BATCH_SIZE) + 1} of ${Math.ceil(allJobPromises.length / BATCH_SIZE)}...`);
      await Promise.all(batch.map(job => job()));
    }
  } catch (error) {
    console.error(`❌ SCRIPT ABORTED: ${error.message}`);
    alert(`Script aborted: Could not fetch all required data. Check the console for details.`);
    return; // Abort the function
  }

  console.log("👍 All data fetched successfully. Now building CSV...");

  // --- 3. Process tables and build CSV from cached data ---
  const csvData = [headers];
  tableWrappers.forEach(wrapper => {
    const table = wrapper.querySelector('table.searchresult');
    const headerCell = table?.querySelector('thead tr:first-child th');
    const firstCourseLinkEl = table?.querySelector('tbody tr th a');
    if (!headerCell || !firstCourseLinkEl) return;

    const subjectName = headerCell.querySelector('a').textContent.trim();
    const subjectCode = headerCell.textContent.match(/Kod:\s*(\S+)/)?.[1] || '';
    const fetchUrl = window.location.origin + firstCourseLinkEl.getAttribute('href');
    const pointsMap = pointsCache.get(fetchUrl);

    if (!pointsMap) {
      console.error(`❌ No points data found for ${subjectName}. This should not happen!`);
      return; // Skip this table if no points data is available
    }

    const courseDetails = Array.from(table.querySelectorAll('tbody tr')).map(row => {
      const linkEl = row.querySelector('th a');
      const codeEl = row.querySelector('td');
      if (!linkEl || !codeEl) return null;
      return {
        levelName: linkEl.textContent.trim(),
        courseCode: codeEl.textContent.trim(),
        link: window.location.origin + linkEl.getAttribute('href')
      };
    }).filter(Boolean);

    courseDetails.forEach(course => {
      const lastChar = course.levelName.slice(-1);
      const relatedLevels = lastChar.match(/[a-zA-Z]/)
        ? courseDetails.filter(item => item.levelName.slice(-1) === lastChar).map(item => item.courseCode)
        : courseDetails.map(item => item.courseCode);

      if (!pointsMap[course.levelName]) {
        console.error(`❌ No points found for ${course.levelName} in ${subjectName}.`);
        throw new Error(`❌ Missing points for ${course.levelName} in ${subjectName}.`);
      }

      csvData.push([
        `${subjectName} - ${course.levelName}`,
        course.courseCode,
        subjectName,
        subjectCode,
        course.link,
        pointsMap[course.levelName],
        relatedLevels.join(',')
      ]);
    });
  });

  // --- 4. Format and Download the CSV File ---
  const escapeCsvCell = (cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`;
  const csvContent = csvData.map(row => row.map(escapeCsvCell).join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'course_catalog_gy25.csv';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  console.log(`✅ Success! CSV file with ${csvData.length - 1} courses has been downloaded.`);
})();
