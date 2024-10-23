document.getElementById('create-doc-form').addEventListener('submit', function(event) {
  event.preventDefault();
  showLoading();

  const formData = new FormData(this);
  fetch('index.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.docId) {
      localStorage.setItem('docId', data.docId);
      document.getElementById('main-content').innerHTML = `
        <div class="alert alert-success" role="alert">
          Documento Google creato con successo! ID del documento: ${data.docId}
        </div>
      `;
    } else {
      alert(data.error);
    }
    hideLoading();
  })
  .catch(error => {
    console.error('Error:', error);
    hideLoading();
  });
});

function showLoading() {
  document.getElementById('main-content').style.display = 'none';
  document.getElementById('loading').style.display = 'block';
  document.getElementById('check-results-btn').style.display = 'block';
}

function hideLoading() {
  document.getElementById('loading').style.display = 'none';
  document.getElementById('check-results-btn').style.display = 'none';
}

function checkResults() {
  const docId = localStorage.getItem('docId');
  if (docId) {
    window.location.href = `results.php?docId=${docId}`;
  } else {
    alert('No document ID found.');
  }
  hideLoading();
}