// Go to:
// https://www.skolverket.se/undervisning/gymnasieskolan/program-och-amnen-i-gymnasieskolan/hitta-program-och-amnen-i-gymnasieskolan-gy25?url=907561864%2Fsyllabuscw%2Fjsp%2Fsearchgy2025.htm%3FalphaSearchString%3D%26searchType%3DFREETEXT%26searchRange%3DGRADE_SUBJECT%26subjectCategory%3D%26searchString%3D&sv.url=12.2b12d9b318c46bc9c3661
// The open terminal and run the following command js commnd.

(function(){
  // Define the headers for the CSV file
  const headers = ['Course', 'Course code', 'Subject', 'Subject code', 'Levels'];
  // Initialize an array to hold the rows of the CSV, starting with the headers
  const csvRows = [headers];

  // Select all the table wrappers within the main result list
  const tableWrappers = document.querySelectorAll('.result_list .table-wrapper');

  // Iterate over each table wrapper found
  tableWrappers.forEach(wrapper => {
    const table = wrapper.querySelector('table.searchresult');
    if (!table) return; // Skip if no valid table is found

    // --- Extract Subject Information ---
    const headerCell = table.querySelector('thead tr:first-child th');
    if (!headerCell) return; // Skip if the header cell isn't found

    const subjectName = headerCell.querySelector('a').textContent.trim();
    const headerText = headerCell.textContent;
    // Use a regular expression to find and extract the subject code
    const subjectCodeMatch = headerText.match(/Kod:\s*(\S+)/);
    const subjectCode = subjectCodeMatch ? subjectCodeMatch[1] : '';

    // --- Pre-process all course rows in the current table ---
    const courseData = [];
    // A single table can have multiple <tbody> elements, so this selector gets all rows
    const courseRows = table.querySelectorAll('tbody tr');

    courseRows.forEach(row => {
      const levelNameCell = row.querySelector('th a');
      const levelCodeCell = row.querySelector('td');

      if (levelNameCell && levelCodeCell) {
        // Store the clean data for each course in an object
        courseData.push({
          levelName: levelNameCell.textContent.trim(),
          courseCode: levelCodeCell.textContent.trim()
        });
      }
    });

    // --- Generate final CSV rows for this subject ---
    courseData.forEach(currentRow => {
      let relatedLevels = [];
      // Get the last character of the level name to check for a suffix (e.g., 'a', 'b')
      const lastChar = currentRow.levelName.slice(-1);

      // Check if the last character is a letter
      if (lastChar.match(/[a-zA-Z]/)) {
        // If it is, find all other courses in this subject with the same suffix
        relatedLevels = courseData
          .filter(item => item.levelName.slice(-1) === lastChar)
          .map(item => item.courseCode);
      } else {
        // If there's no suffix, include all courses for the subject
        relatedLevels = courseData.map(item => item.courseCode);
      }

      // Construct the row for the CSV file, ensuring values are quoted
      const csvRow = [
        `"${subjectName} - ${currentRow.levelName}"`, // Course
        `"${currentRow.courseCode}"`, // Course code
        `"${subjectName}"`, // Subject
        `"${subjectCode}"`, // Subject code
        `"${relatedLevels.join(',')}"` // Comma-separated list of related levels
      ];
      csvRows.push(csvRow.join(',')); // Join the array into a single CSV row string
    });
  });

  // --- Format and Download the CSV File ---
  if (csvRows.length > 1) { // Check if any data was actually found
    // Join all rows with a newline character to form the final CSV content
    const csvContent = csvRows.join('\n');
    // Create a Blob object which represents the raw data of the file
    const blob = new Blob([csvContent], {
      type: 'text/csv;charset=utf-8;'
    });

    // Create a temporary link element to trigger the download
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', 'course_catalog_gy25.csv');
    link.style.visibility = 'hidden';

    // Add the link to the page, click it, and then remove it
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } else {
    console.log("No course data found to create a CSV file.");
  }
})();
