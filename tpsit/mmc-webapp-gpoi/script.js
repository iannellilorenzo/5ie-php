document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('check-results-btn').addEventListener('click', checkResultsFromBtn);
  document.getElementById('create-doc-form').addEventListener('submit', createDoc);
});

function createDoc(event) {
  event.preventDefault();
  const docTitleFromForm = document.getElementById('docTitle').value;

  // Mostra la gif di caricamento
  document.getElementById('loading').style.display = 'block';

  // Nascondi il contenuto principale
  document.getElementById('main-content').style.display = 'none';

  // Invia la richiesta al backend
  fetch('index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      action: 'createDoc',
      docTitle: docTitleFromForm
    })
  })
  .then(response => response.json())
  .then(data => {
    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';

    if (!data.error) {
      localStorage.setItem('docId', data.docId);
      window.open(data.docUrl, '_blank');
      document.getElementById('check-results-btn').style.display = 'block';
    } else {
      // Mostra il contenuto principale in caso di errore
      document.getElementById('main-content').style.display = 'block';
    }
  })
  .catch(error => {
    console.error('Errore:', error);
    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';
    // Mostra il contenuto principale in caso di errore
    document.getElementById('main-content').style.display = 'block';
  });
}

function checkResults() {
  const docId = document.getElementById('docId').value;
  if (!docId) {
    alert('Per favore, inserisci un ID del documento.');
    return;
  }

  // Mostra la gif di caricamento
  document.getElementById('loading').style.display = 'block';

  // Nascondi il contenuto principale
  document.getElementById('main-content').style.display = 'none';

  // Invia la richiesta al backend
  fetch('index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      action: 'exportDoc',
      docId: docId
    })
  })
  .then(response => response.json())
  .then(data => {
    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';

    // Mostra il contenuto principale
    document.getElementById('main-content').style.display = 'block';

  })
  .catch(error => {
    console.error('Errore:', error);

    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';
    document.getElementById('output').style.display = 'block';

    // Mostra il contenuto principale
    document.getElementById('main-content').style.display = 'block';
  });
}

function checkResultsFromBtn() {
  const docId = localStorage.getItem('docId');
  if (!docId) {
    alert('No document ID found.');
    return;
  }

  document.getElementById('output').style.display = 'none';
  document.getElementById('main-content').style.display = 'none';
  document.getElementById('check-results-btn').style.display = 'none';

  // Mostra la gif di caricamento
  document.getElementById('loading').style.display = 'block';

  // Invia la richiesta al backend
  fetch('index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      action: 'exportDoc',
      docId: docId
    })
  })
  .then(response => response.text())
  .then(data => {
    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';
    document.getElementById('check-results-btn').style.display = 'none';
    document.getElementById('output').style.display = 'block';

    if (data.includes('compilare') || data.includes('Tabella')) {
      document.getElementById('output').innerText = data;
      document.getElementById('check-results-btn').style.display = 'block';
    } else {
      document.getElementById('output').innerText = 'Il documento google è stato modificato in base ai dati forniti, si prega di ricontrollare.'; 
    }
  })
  .catch(error => {
    console.error('Errore:', error);

    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';

    // Mostra il contenuto principale
    document.getElementById('main-content').style.display = 'block';
  });
}