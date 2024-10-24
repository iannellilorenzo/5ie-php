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
  document.getElementById('loading').innerHTML = '<img src="assets/loading.gif" alt="Loading..."><p>Generating document...</p>';
  
  setTimeout(() => {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('check-results-btn').style.display = 'block';
  }, 5000); // Show the button after 5 seconds
}

function hideLoading() {
  document.getElementById('loading').style.display = 'none';
  document.getElementById('check-results-btn').style.display = 'none';
}

function checkResults() {
  const docId = localStorage.getItem('docId');
  if (docId) {
    fetch('index.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `action=exportDoc&docId=${docId}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.result) {
        document.getElementById('output').innerText = data.result;
        hideLoading();
      } else {
        alert('Failed to get document content from data.');
        hideLoading();
      }
    })
    .catch(error => {
      console.error('There was a problem with the fetch operation:', error);
      alert('Failed to get document content.');
      hideLoading();
    });
  } else {
    alert('No document ID found.');
    hideLoading();
  }
}