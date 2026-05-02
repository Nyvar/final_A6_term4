<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="?page=home">Expense Tracker</a>
            <div class="topbar">
                <span class="hamburger" onclick="toggleNav()">&#9776;</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="?page=home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=add_expense">Add Expense</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=view_expenses">View Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=reports">Reports</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<script>
function toggleNav() {
  const panel = document.getElementById("navPanel");
  panel.style.display = (panel.style.display === "block") ? "none" : "block";
}
</script>
