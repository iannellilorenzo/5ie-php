# MMC Webapp GPOI

## Installation

1. **Install dependencies**:
    Make sure to have PHP and Composer installed.

2. **Set up your Google API credentials**:
    - Place your `credentials.json` file in the root directory of the project.
    - Ensure you have a valid `token.json` file for authentication.

3. **Start the local server**:
   - Open your browser and navigate to [http://localhost:8000](http://localhost:8000).

## Usage

### Creating a Google Doc

1. Fill in the form with the title of the document.
2. Click the "Create Document" button.
3. The application will generate a Google Doc based on the template and open it in a new tab.
4. The document ID will be saved for future reference.

### Checking Results

1. Click the "Check Results" button.
2. The application will retrieve and display the results from the Google Doc.

### Viewing Family Information

1. Navigate to the family information section.
2. Click the "Tell me more" button to view detailed information about a family member in a modal.

## File Structure

- **`index.php`**: Main entry point of the application.
- **`script.js`**: JavaScript file containing the logic for creating Google Docs and checking results.
- **`style.css`**: CSS file for styling the application.

## Dependencies

- PHP
- Composer
- Google API Client Library for PHP
- Bootstrap 4

## Contributing

1. Fork the repository.
2. Create a new branch:

    ```bash
    git checkout -b feature-branch
    ```

3. Make your changes and commit them:

    ```bash
    git commit -am 'Add new feature'
    ```

4. Push the branch

    ```bash
    git push origin feature-branch
    ```

5. Create a new Pull Request

## Important Notes

The deletion of contents from the Google Doc files is done using a preset of the said document. The preset can be found [here](https://docs.google.com/document/d/1E3p8DabRGZLBlJjFBmLvV07bBeKQsZvwJ6H-3tJxQIg/).
