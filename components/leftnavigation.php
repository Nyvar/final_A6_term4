
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
   
    .left-nav {
      width: 280px;
      background: #fff;
      border-right: 1px solid #ddd;
      height: 100vh;
      display: flex;
      flex-direction: column;
      padding: 1rem;
      position: relative;
    }
 
    .accounts {
      margin: 1rem 0;
    }
    .filters .btn {
      margin-bottom: 0.2rem;
      transition: background-color 0.3s ease;
    }
    .filters .btn:hover {
      background-color: #2e7d32;
      color: #fff;
    }
  
    .fab {
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      background: #d32f2f;
      color: white;
      border: none;
      border-radius: 50%;
      width: 48px;
      height: 48px;
      font-size: 1.5rem;
      cursor: pointer;
    }
  </style>
  <div class="left-nav">
  

   
    <div class="accounts">
      <label class="form-label">All accounts</label>
      <select class="form-select">
        <option>Account 1</option>
        <option>Account 2</option>
      </select>
    </div>

  
    <div class="filters">
      <?php
        $filters = ["Day", "Week", "Month", "Year", "All", "Interval", "Choose date"];
        foreach ($filters as $filter) {
          echo "<button class='btn btn-light w-100'>$filter</button>";
        }
      ?>
    </div>

  

  

  
    <button class="fab">−</button>
</div>



  <!-- Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

