<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Luyfy - expense App</title>
  <link rel="stylesheet" href="../css/styleHomepage.css">
</head>
<body>

<!-- Header -->
<header>
  <div class="logo">
    <img src="../images/luyfy.png" alt="Luyfy Logo">
  </div>

  <nav>
    <a href="#">Personal Finance</a>
    <a href="#">Investments</a>
    <a href="#">Banks</a>
    <a href="#">Credit Card</a>
    <a href="#">Loans</a>
    <a href="#">Business</a>
    <a href="#">Noobie Guide</a>
  </nav>
  <div class="header-buttons">
    <a href="#" class="btn">Get the App</a>
    <a href="login.php" class="btn login">Login</a>
  </div>
</header>

<!-- Hero + Phone Showcase -->
<section class="hero-container">
  <div class="hero">
    <div class="hero-badges">
    <!-- First badge -->
    <div class="badge flower-wrap">
      <img src="../images/leftleaf.png" alt="Flower Left" class="wrap-img left">
      <span>5.8+ million downloads</span>
      <img src="../images/rightleaf.png" alt="Flower Right" class="wrap-img right">
    </div>

    <!-- Second badge with text + logos -->
    <div class="badge leaf-wrap">
      <img src="../images/leftleaf.png" alt="Leaf Left" class="wrap-img left">
      <span>Best expense app</span>
      <div class="store-logos">
        <img src="../images/apple.png" alt="Apple Store">
        <img src="../images/playstore.png" alt="Google Play">
      </div>
      <img src="../images/rightleaf.png" alt="Leaf Right" class="wrap-img right">
    </div>
  </div>
    <h1>STABILIZE YOUR FINANCES</h1>
    <p>Smart budget app that helps you track expenses, save, invest, and achieve financial enlightenment.</p>
    <div class="download-btns">
      <img src="../images/appledownload.png" alt="App Store" class="download-btn">
      <img src="../images/playstoredownload.png" alt="Google Play" class="download-btn">
    </div>

    <p>Trusted by 6700 people worldwide</p>
    <p>⭐⭐⭐⭐⭐ +1000 people enjoyed it</p>
  </div>

  
<section class="phone-showcase">
  <img src="../images/phone.png" alt="Phone" class="phone-img">

  <!-- Logos -->
  <img src="../images/logo1.png" class="floating-logo logo1">
  <img src="../images/logo2.png" class="floating-logo logo2">
  <img src="../images/logo3.png" class="floating-logo logo3">

  <!-- Coins -->
  <img src="../images/coin2.png" class="coin coin1">
  <img src="../images/coin2.png" class="coin coin2">
  <img src="../images/coin3.png" class="coin coin3">
</section>





  

  


  

  <div class="card-long guide">
    <h3>Noobie Guide + Hacks</h3>
    <p>Grow your money tree: learn mix seeds, grow debt free, become rich...</p>
    <a href="#">Start Learning</a>
  </div>
</div>
</section>

<!-- Testimonials -->
<section class="testimonials">
  <h2>Financial Stabilizer Rising</h2>
  <div class="stars">⭐⭐⭐⭐☆ 4.5</div>
  <div class="testimonial-list">
    <div class="testimonial">⭐⭐⭐⭐⭐ "Amazing app for budgeting" – Alice, Appstore</div>
    <div class="testimonial">⭐⭐⭐⭐⭐ "Keeps me on track" – Ben, Playstore</div>
    <div class="testimonial">⭐⭐⭐⭐⭐ "Love the interface" – Clara, Appstore</div>
    <div class="testimonial">⭐⭐⭐⭐⭐ "Best app for expenses" – David, Playstore</div>
    <div class="testimonial">⭐⭐⭐⭐⭐ "Simple and powerful" – Eva, Appstore</div>
    <div class="testimonial">⭐⭐⭐⭐⭐ "Highly recommend" – Frank, Playstore</div>
  </div>
</section>

<!-- News -->
<section class="news">
  <h2>Financial News</h2>
  <div class="news-container">
    <div class="news-item"><img src="news1.jpg"><div class="genre">Expense</div><h3>Track your daily spending</h3></div>
    <div class="news-item"><img src="news2.jpg"><div class="genre">Budget</div><h3>Smart budgeting tips</h3></div>
    <div class="news-item"><img src="news3.jpg"><div class="genre">Invest</div><h3>Investing for beginners</h3></div>
    <div class="news-item"><img src="news4.jpg"><div class="genre">Expense</div><h3>Cut unnecessary costs</h3></div>
    <div class="news-item"><img src="news5.jpg"><div class="genre">Budget</div><h3>Monthly planning guide</h3></div>
    <div class="news-item"><img src="news6.jpg"><div class="genre">Invest</div><h3>Grow your wealth</h3></div>
  </div>
  <div class="news-nav">
    <button>&lt; Prev</button>
    <button>Next &gt;</button>
  </div>
</section>

<!-- FAQ -->
<section class="faq">
  <h2>FAQ</h2>
  <div>
    <div class="question" onclick="toggleAnswer('q1')">What is Luyfy? > </div>
    <div id="q1" class="answer">Luyfy is an expense app</div>
  </div>
  <div>
    <div class="question" onclick="toggleAnswer('q2')">Is it safe? ></div>
    <div id="q2" class="answer">Yes, it uses secure methods.</div>
  </div>
  <div>
    <div class="question" onclick="toggleAnswer('q3')">Is it free? ></div>
    <div id="q3" class="answer">Basic version is free.</div>
  </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
  <h2>Get Updates</h2>
  <form>
    <input type="email" placeholder="Enter your email" />
    <br>
    <button>Submit</button>
  </form>
</section>

<!-- Footer -->
<footer>
  <div class="logo">💸 Luyfy</div>
  <div>Follow us on social media</div>
  <div>© 2026 Luyfy | Support | Terms | Policy</div>
</footer>



<script>
  function toggleAnswer(id) {
    var el = document.getElementById(id);
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
  }
  function closeFloating() {
    document.getElementById('floating').style.display = 'none';
  }
  let currentReview = 0;
const reviews = document.querySelectorAll('.review');
const dots = document.querySelectorAll('.dot');

function showReview(i) {
  document.querySelector('.reviews-inner').style.transform = `translateX(-${i * 100}%)`;
  dots.forEach(d => d.classList.remove('active'));
  dots[i].classList.add('active');
}

setInterval(() => {
  currentReview = (currentReview + 1) % reviews.length;
  showReview(currentReview);
}, 3000); // loop every 3s

document.querySelectorAll('.saving').forEach(card => {
  card.addEventListener('click', () => {
    // remove outline from all
    document.querySelectorAll('.saving').forEach(c => c.classList.remove('clicked'));
    // add to clicked one
    card.classList.add('clicked');
  });
});


</script>

</body>
</html>

