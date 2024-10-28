document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('check-results-btn').addEventListener('click', checkResults);
});

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
      action: 'exportGoogleDocAsHtml',
      docId: docId
    })
  })
  .then(response => response.json())
  .then(data => {
    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';

    // Mostra il contenuto principale
    document.getElementById('main-content').style.display = 'block';

    if (data.error) {
      alert('Errore: ' + data.error);
    } else {
      alert('Risultati: ' + JSON.stringify(data));
    }
  })
  .catch(error => {
    console.error('Errore:', error);
    alert('Si è verificato un errore durante il controllo dei risultati.');
    
    // Nascondi la gif di caricamento
    document.getElementById('loading').style.display = 'none';

    // Mostra il contenuto principale
    document.getElementById('main-content').style.display = 'block';
  });
}