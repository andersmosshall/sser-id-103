// Go to:
// https://www.skolverket.se/undervisning/gymnasieskolan/program-och-amnen-i-gymnasieskolan/hitta-program-och-amnen-i-gymnasieskolan-gy25?url=907561864%2Fsyllabuscw%2Fjsp%2Fsearchgy2025.htm%3FalphaSearchString%3D%26searchType%3DFREETEXT%26searchRange%3DPROGRAM%26subjectCategory%3D%26searchString%3D&sv.url=12.2b12d9b318c46bc9c3661
// The open terminal and run the following command js commnd.

(function() {
  /**
   * Helper function to safely escape a string for CSV format.
   * It handles null/undefined values, quotes fields containing commas or quotes,
   * and escapes existing quotes within the field.
   * @param {*} field The data to escape.
   * @returns {string} A CSV-safe string.
   */
  const escapeCsvField = (field) => {
    if (field === null || field === undefined) {
      return '';
    }
    const str = String(field);
    // Check if the field contains a comma, a double quote, or a newline
    if (str.includes(',') || str.includes('"') || str.includes('\n')) {
      // Escape any existing double quotes by doubling them and wrap the whole field in double quotes
      return `"${str.replace(/"/g, '""')}"`;
    }
    return str;
  };

  // 1. Define CSV headers and an array to store row data.
  const headers = ["Item", "Item code", "Type", "Parent code", "Link"];
  const csvRows = [headers];

  // 2. Find the main container for all the tables.
  const resultList = document.querySelector('.result_list');
  if (!resultList) {
    console.error('Error: Could not find the main data wrapper with class "result_list".');
    alert('Could not find the required data on the page. Aborting script.');
    return;
  }

  const tableWrappers = resultList.querySelectorAll('.table-wrapper');

  // 3. Iterate over each table to extract programme and focus data.
  tableWrappers.forEach((wrapper, index) => {
    const table = wrapper.querySelector('table.searchresult');
    if (!table) return;

    // --- Extract Programme Data (Parent Item) ---
    const programHeader = table.querySelector('thead > tr:first-child > th');
    if (!programHeader) {
      console.warn(`Skipping table at index ${index} as it has no valid programme header.`);
      return;
    }

    const programLinkElement = programHeader.querySelector('a');
    const headerText = programHeader.textContent;

    const programName = programLinkElement ? programLinkElement.textContent.trim() : 'N/A';
    const programLink = programLinkElement ? new URL(programLinkElement.getAttribute('href'), window.location.origin).href : '';

    // Use a regular expression to find the code after "Kod:"
    const codeMatch = headerText.match(/Kod:\s*([^\s)]+)/);
    const programCode = codeMatch ? codeMatch[1] : '';

    // Add programme row to our data
    csvRows.push([
      escapeCsvField(programName),
      escapeCsvField(programCode),
      'programme',
      '', // Parent code is empty for programmes
      escapeCsvField(programLink)
    ]);

    // --- Extract Focus Data (Child Items) ---
    const focusRows = table.querySelectorAll('tbody > tr');
    focusRows.forEach(row => {
      const focusNameCell = row.querySelector('th');
      const focusCodeCell = row.querySelector('td');

      // Ensure the row has the expected cells for a focus item
      if (!focusNameCell || !focusCodeCell) {
        return;
      }

      const focusLinkElement = focusNameCell.querySelector('a');
      const focusName = focusLinkElement ? focusLinkElement.textContent.trim() : 'N/A';
      const focusLink = focusLinkElement ? new URL(focusLinkElement.getAttribute('href'), window.location.origin).href : '';
      const focusCode = focusCodeCell.textContent.trim();

      // Add focus row to our data
      csvRows.push([
        escapeCsvField(focusName),
        escapeCsvField(focusCode),
        'focus',
        escapeCsvField(programCode), // Use the parent's code
        escapeCsvField(focusLink)
      ]);
    });
  });

  // 4. Convert the array of arrays into a single CSV string.
  const csvContent = csvRows.map(row => row.join(',')).join('\n');

  // 5. Create a Blob and trigger the download.
  // The \uFEFF is a BOM (Byte Order Mark) to ensure Excel opens UTF-8 files correctly.
  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  const fileName = "programmes_gy25.csv";

  link.setAttribute("href", url);
  link.setAttribute("download", fileName);
  link.style.visibility = 'hidden';

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  console.log(`Successfully generated and downloaded "${fileName}" with ${csvRows.length - 1} data rows.`);
})();
