(function () {
  const searchInput = document.getElementById('searchInput');
  const filterForm = document.getElementById('filterForm');

  if (searchInput && filterForm) {
    let typingTimer;
    const doneTypingInterval = 500;

    searchInput.addEventListener('input', () => {
      clearTimeout(typingTimer);
      typingTimer = setTimeout(() => {
        filterForm.submit();
      }, doneTypingInterval);
    });

    // Put cursor at the end of text after refresh
    const val = searchInput.value;
    searchInput.value = '';
    searchInput.focus();
    searchInput.value = val;
  }
})();
