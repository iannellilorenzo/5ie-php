document.getElementById('create-doc-form').addEventListener('submit', function(event) {
  event.preventDefault();

  const formData = new FormData(this);
  fetch('index.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.docId) {
      localStorage.setItem('docId', data.docId);
      window.open(data.docUrl, '_blank'); // Open the Google Doc URL in a new tab
      showLoading();
    } else {
      alert(data.error);
    }
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
    window.location.href = `index.php?docId=${docId}`;
  } else {
    alert('No document ID found.');
  }
  hideLoading();
}