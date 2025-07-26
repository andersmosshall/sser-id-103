// Go to:
// https://www.skolverket.se/undervisning/gymnasieskolan/program-och-amnen-i-gymnasieskolan/hitta-program-amnen-och-kurser-i-gymnasieskolan-gy11?alphaSearchString=&searchType=FREETEXT&searchRange=COURSE&subjectCategory=&searchString=
// The open terminal and run the following command js commnd.
(function(){
  // 1. Find the main container for all the course tables.
  const subjectResultDiv = document.querySelector('#subjectResultDiv');
  if (!subjectResultDiv) {
    console.error('Error: The container with ID "subjectResultDiv" was not found.');
    alert('Could not find the course data container on the page.');
    return;
  }

  // 2. Get all the individual table wrappers.
  const tableWrappers = subjectResultDiv.querySelectorAll('.table-wrapper');
  if (tableWrappers.length === 0) {
    console.warn('No course tables were found to export.');
    alert('No course data was found on the page.');
    return;
  }

  // 3. Define CSV headers.
  const csvHeaders = ['"Course"', '"Course code"', '"Subject"', '"Subject code"'];
  const csvRows = [csvHeaders.join(',')];

  // 4. Process each table to extract subject and course information.
  tableWrappers.forEach(wrapper => {
    const table = wrapper.querySelector('table.searchresult');
    if (!table) return;

    // --- Extract Subject Information from the table header ---
    const headerCell = table.querySelector('thead tr:first-child th');
    if (!headerCell) return;

    // Get subject name from the <a> tag.
    const subjectNameElement = headerCell.querySelector('a');
    const subjectName = subjectNameElement ? subjectNameElement.textContent.trim() : '';

    // Get subject code from the text node. Example: "Kod:MAT ..."
    const headerText = headerCell.textContent;
    const kodIndex = headerText.indexOf('Kod:');
    let subjectCode = '';

    if (kodIndex !== -1) {
      // Isolate the text after "Kod:"
      const textAfterKod = headerText.substring(kodIndex + 4).trim();
      // The code is the first part before any space.
      subjectCode = textAfterKod.split(' ')[0];
    }

    if (!subjectName || !subjectCode) {
      console.warn('Could not extract subject name or code from table:', table);
      return; // Skip this table if essential subject info is missing.
    }

    // --- Extract each course (row) from the table body ---
    const courseRows = table.querySelectorAll('tbody > tr');
    courseRows.forEach(row => {
      const courseNameElement = row.querySelector('th[scope="row"] a');
      const courseCodeElement = row.querySelector('td');

      if (courseNameElement && courseCodeElement) {
        // Trim whitespace and escape any double quotes within the data.
        const course = `"${courseNameElement.textContent.trim().replace(/"/g, '""')}"`;
        const courseCode = `"${courseCodeElement.textContent.trim().replace(/"/g, '""')}"`;
        const subject = `"${subjectName.replace(/"/g, '""')}"`;
        const subCode = `"${subjectCode.replace(/"/g, '""')}"`;

        csvRows.push([course, courseCode, subject, subCode].join(','));
      }
    });
  });

  // 5. Create the CSV file and trigger the download.
  if (csvRows.length <= 1) {
    alert('Data was found, but no course rows could be processed.');
    return;
  }

  // Join all rows with a newline character.
  const csvString = csvRows.join('\n');

  // Create a "Blob" (Binary Large Object) from the CSV string.
  const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });

  // Create a temporary link element to trigger the download.
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);

  link.setAttribute('href', url);
  link.setAttribute('download', 'course_catalog_gy11.csv');
  link.style.visibility = 'hidden';

  // Append, click, and then remove the link.
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
})();
